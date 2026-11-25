<?php

namespace App\Livewire\PurchaseOrders;

use App\Enums\PurchaseOrderStatus;
use App\Enums\RequisitionStatus;
use App\Models\BoqItem;
use App\Models\MaterialRequisition;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Services\CalculationService;
use App\Support\DocumentNumber;
use App\Support\Npr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('New purchase order')]
class Create extends Component
{
    public string $projectId = '';

    public string $vendorId = '';

    public string $tdsRate = '';

    public string $orderDate = '';

    public string $expectedDelivery = '';

    public string $notes = '';

    /** @var array<int, array{description: string, unit: string, quantity: int|string, rate_npr: string, boq_item_id: int|string}> */
    public array $items = [];

    public ?int $requisitionId = null;

    public function mount(?int $requisition = null): void
    {
        $this->authorize('manage');

        $this->orderDate = today()->toDateString();
        $this->expectedDelivery = today()->addDays(7)->toDateString();
        $this->items = [$this->blankItem()];

        $requisitionId = $requisition ?? request()->integer('requisition');

        if ($requisitionId !== 0) {
            $this->loadRequisition(MaterialRequisition::with('items.boqItem')->findOrFail($requisitionId));
        }
    }

    public function addItem(): void
    {
        $this->items[] = $this->blankItem();
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedVendorId(string $value): void
    {
        $vendor = Vendor::query()->find($value);

        $this->tdsRate = $vendor
            ? number_format((float) $vendor->tds_rate, 2, '.', '')
            : '';
    }

    public function updatedProjectId(): void
    {
        foreach ($this->items as $index => $item) {
            $this->items[$index]['boq_item_id'] = '';
        }
    }

    public function fillFromBoq(int $index): void
    {
        $item = BoqItem::query()
            ->forProject($this->projectId)
            ->find($this->items[$index]['boq_item_id'] ?? null);

        if ($item === null) {
            return;
        }

        $this->items[$index]['description'] = $item->description;
        $this->items[$index]['unit'] = $item->unit;
        $this->items[$index]['rate_npr'] = Npr::toInput($item->rate_paisa);
    }

    public function save(): void
    {
        $this->authorize('manage');

        $this->items = array_values(array_filter(
            $this->items,
            fn ($row) => filled($row['description'] ?? null)
                && (float) ($row['quantity'] ?? 0) > 0
                && ($row['rate_npr'] ?? '') !== ''
        ));

        $this->validate([
            'projectId' => ['required', Rule::exists('projects', 'id')],
            'vendorId' => ['required', Rule::exists('vendors', 'id')],
            'orderDate' => ['required', 'date'],
            'expectedDelivery' => ['nullable', 'date', 'after_or_equal:orderDate'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['required', 'string', 'max:20'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.rate_npr' => ['required', 'numeric', 'min:0'],
            'items.*.boq_item_id' => ['nullable', Rule::exists('boq_items', 'id')],
        ]);

        $project = Project::query()->findOrFail($this->projectId);
        $vendor = Vendor::query()->findOrFail($this->vendorId);

        $boqIds = collect($this->items)->pluck('boq_item_id')->filter();

        if (BoqItem::query()->forProject($project)->whereIn('id', $boqIds)->count() !== $boqIds->count()) {
            $this->addError('items', __('Every BOQ item must belong to the selected project.'));

            return;
        }

        $calculator = app(CalculationService::class);
        $totals = $calculator->poTotals($this->linePayload(), (float) $vendor->tds_rate);

        $order = DB::transaction(function () use ($project, $vendor, $calculator, $totals) {
            $order = PurchaseOrder::create([
                'code' => DocumentNumber::purchaseOrder(),
                'project_id' => $project->id,
                'vendor_id' => $vendor->id,
                'material_requisition_id' => $this->requisitionId,
                'order_date' => $this->orderDate,
                'expected_delivery' => $this->expectedDelivery ?: null,
                'status' => PurchaseOrderStatus::Pending,
                'subtotal_paisa' => $totals['subtotal_paisa'],
                'vat_paisa' => $totals['vat_paisa'],
                'tds_paisa' => $totals['tds_paisa'],
                'total_paisa' => $totals['net_payable_paisa'],
                'notes' => $this->notes ?: null,
            ]);

            foreach ($this->items as $row) {
                $ratePaisa = Npr::toPaisa($row['rate_npr']);

                $order->items()->create([
                    'description' => $row['description'],
                    'unit' => $row['unit'],
                    'quantity' => (float) $row['quantity'],
                    'rate_paisa' => $ratePaisa,
                    'amount_paisa' => $calculator->lineAmount((float) $row['quantity'], $ratePaisa),
                    'boq_item_id' => filled($row['boq_item_id'] ?? null) ? (int) $row['boq_item_id'] : null,
                    'received_qty' => 0,
                ]);
            }

            if ($this->requisitionId !== null) {
                MaterialRequisition::query()
                    ->where('id', $this->requisitionId)
                    ->where('status', RequisitionStatus::Approved)
                    ->update(['status' => RequisitionStatus::PoGenerated]);
            }

            return $order;
        });

        session()->flash('success', __('Purchase order :code saved.', ['code' => $order->code]));

        $this->redirect(route('purchase-orders.show', $order), navigate: true);
    }

    public function totals(): array
    {
        return app(CalculationService::class)->poTotals(
            $this->linePayload(),
            (float) ($this->selectedVendor()?->tds_rate ?? 0)
        );
    }

    public function selectedVendor(): ?Vendor
    {
        return $this->vendorId !== '' ? Vendor::query()->find((int) $this->vendorId) : null;
    }

    public function render()
    {
        return view('livewire.purchase-orders.create', [
            'projects' => Project::query()->where('status', 'active')->orderBy('code')->get(['id', 'code', 'name']),
            'vendors' => Vendor::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'pan', 'tds_rate']),
            'boqItems' => BoqItem::query()->forProject($this->projectId)->orderBy('code')->get(['id', 'code', 'description', 'unit', 'rate_paisa']),
            'selectedVendor' => $this->selectedVendor(),
            'totals' => $this->totals(),
            'vatRate' => app(CalculationService::class)->vatRate(),
        ]);
    }

    /** @return array<int, array{quantity: float, rate_paisa: int}> */
    private function linePayload(): array
    {
        return collect($this->items)
            ->filter(fn ($row) => (float) ($row['quantity'] ?? 0) > 0 && ($row['rate_npr'] ?? '') !== '')
            ->map(fn ($row) => [
                'quantity' => (float) $row['quantity'],
                'rate_paisa' => Npr::toPaisa($row['rate_npr']),
            ])
            ->values()
            ->all();
    }

    /** @return array{description: string, unit: string, quantity: int|string, rate_npr: string, boq_item_id: int|string} */
    private function blankItem(): array
    {
        return ['description' => '', 'unit' => 'bag', 'quantity' => '', 'rate_npr' => '', 'boq_item_id' => ''];
    }

    private function loadRequisition(MaterialRequisition $requisition): void
    {
        if (! in_array($requisition->status, [RequisitionStatus::Approved, RequisitionStatus::PoGenerated], true)
            || $requisition->purchaseOrder()->exists()) {
            session()->flash('error', __('That requisition cannot generate another purchase order.'));

            return;
        }

        $this->requisitionId = $requisition->id;
        $this->projectId = (string) $requisition->project_id;
        $this->notes = $requisition->notes ?? '';
        $this->items = [];
        $this->expectedDelivery = $requisition->required_by->toDateString();

        foreach ($requisition->items as $item) {
            $this->items[] = [
                'description' => $item->description,
                'unit' => $item->unit,
                'quantity' => (float) $item->quantity,
                'rate_npr' => $item->boqItem ? Npr::toInput($item->boqItem->rate_paisa) : '',
                'boq_item_id' => $item->boq_item_id ? (string) $item->boq_item_id : '',
            ];
        }
    }
}
