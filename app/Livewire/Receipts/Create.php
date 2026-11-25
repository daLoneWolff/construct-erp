<?php

namespace App\Livewire\Receipts;

use App\Enums\PurchaseOrderStatus;
use App\Enums\ReceiptStatus;
use App\Enums\StockTransactionType;
use App\Enums\WarehouseType;
use App\Models\Employee;
use App\Models\MaterialReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockLedger;
use App\Models\Warehouse;
use App\Services\CalculationService;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Receive material')]
class Create extends Component
{
    public PurchaseOrder $po;

    public string $warehouseId = '';

    public string $receivedDate = '';

    public string $receivedByEmployeeId = '';

    public string $notes = '';

    /** @var array<int, array{purchase_order_item_id: int|string, description: string, unit: string, ordered_qty: int|string, received_qty: int|string, rejected_qty: int|string}> */
    public array $items = [];

    public function mount(PurchaseOrder $po): void
    {
        $this->authorize('receive');

        $this->po = $po->load('items');
        $this->warehouseId = (string) (
            Warehouse::query()->where('project_id', $po->project_id)->value('id')
            ?? Warehouse::query()->where('type', WarehouseType::Central->value)->value('id')
            ?? ''
        );
        $this->receivedDate = today()->toDateString();
        $this->receivedByEmployeeId = (string) (
            auth()->user()->employee_id
            ?? Employee::query()->value('id')
            ?? ''
        );

        foreach ($po->items as $item) {
            $this->items[] = [
                'purchase_order_item_id' => $item->id,
                'description' => $item->description,
                'unit' => $item->unit,
                'ordered_qty' => (float) $item->quantity,
                'received_qty' => max(0, (float) $item->quantity - (float) $item->received_qty),
                'rejected_qty' => 0,
            ];
        }

        if (! $this->canReceive()) {
            session()->flash('error', __('Only approved or sent purchase orders can be received against.'));
        }
    }

    public function save(): void
    {
        $this->authorize('receive');

        if (! $this->canReceive()) {
            $this->addError('po', __('Only approved or sent purchase orders can be received against.'));

            return;
        }

        $this->validate([
            'warehouseId' => ['required', Rule::exists('warehouses', 'id')],
            'receivedDate' => ['required', 'date'],
            'receivedByEmployeeId' => ['required', Rule::exists('employees', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.received_qty' => ['required', 'numeric', 'min:0'],
            'items.*.rejected_qty' => ['required', 'numeric', 'min:0'],
        ]);

        $allowedIds = $this->po->items->pluck('id')->all();
        $hasAcceptedQty = false;

        foreach ($this->items as $index => $row) {
            $item = PurchaseOrderItem::query()->find((int) $row['purchase_order_item_id']);

            if ($item === null || ! in_array($item->id, $allowedIds, true)) {
                $this->addError("items.{$index}.received_qty", __('Unknown purchase order line.'));

                continue;
            }

            $received = (float) $row['received_qty'];
            $rejected = (float) $row['rejected_qty'];

            if ($rejected > $received) {
                $this->addError("items.{$index}.rejected_qty", __('Rejected quantity cannot exceed received quantity.'));
            }

            if ($received > (float) $item->quantity - (float) $item->received_qty + 0.0001) {
                $this->addError("items.{$index}.received_qty", __('Received quantity exceeds the outstanding order quantity.'));
            }

            if ($received - $rejected > 0) {
                $hasAcceptedQty = true;
            }
        }

        if (! $hasAcceptedQty) {
            $this->addError('items', __('At least one line must have an accepted quantity.'));
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $warehouse = Warehouse::query()->findOrFail($this->warehouseId);
        $calculator = app(CalculationService::class);

        $receipt = DB::transaction(function () use ($warehouse, $calculator) {
            $receipt = MaterialReceipt::create([
                'code' => DocumentNumber::receipt(),
                'purchase_order_id' => $this->po->id,
                'warehouse_id' => $warehouse->id,
                'received_date' => $this->receivedDate,
                'received_by_employee_id' => (int) $this->receivedByEmployeeId,
                'notes' => $this->notes ?: null,
                'status' => ReceiptStatus::Confirmed,
            ]);

            foreach ($this->items as $row) {
                /** @var PurchaseOrderItem $poItem */
                $poItem = PurchaseOrderItem::query()->findOrFail((int) $row['purchase_order_item_id']);
                $received = (float) $row['received_qty'];
                $rejected = (float) $row['rejected_qty'];
                $accepted = $received - $rejected;

                $receipt->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'ordered_qty' => $poItem->quantity,
                    'received_qty' => $received,
                    'rejected_qty' => $rejected,
                ]);

                if ($accepted <= 0) {
                    continue;
                }

                $poItem->increment('received_qty', $accepted);

                $balance = $calculator->stockBalance($warehouse, $poItem->description, $poItem->unit);

                StockLedger::create([
                    'warehouse_id' => $warehouse->id,
                    'item_description' => $poItem->description,
                    'unit' => $poItem->unit,
                    'in_qty' => $accepted,
                    'out_qty' => 0,
                    'balance_qty' => $balance + $accepted,
                    'transaction_type' => StockTransactionType::Receipt,
                    'reference_type' => 'MaterialReceipt',
                    'reference_id' => $receipt->id,
                    'transaction_date' => $this->receivedDate,
                ]);
            }

            $this->po->load('items');

            $fullyReceived = $this->po->items->every(
                fn (PurchaseOrderItem $item) => (float) $item->received_qty >= (float) $item->quantity - 0.0001
            );

            $this->po->update([
                'status' => $fullyReceived
                    ? PurchaseOrderStatus::Closed
                    : PurchaseOrderStatus::PartiallyReceived,
            ]);

            return $receipt;
        });

        session()->flash('success', __('Receipt :code confirmed. Stock ledger updated.', ['code' => $receipt->code]));

        $this->redirect(route('receipts.show', $receipt), navigate: true);
    }

    public function render(CalculationService $calc)
    {
        $selectedWarehouse = $this->warehouseId === ''
            ? null
            : Warehouse::query()->find($this->warehouseId);

        return view('livewire.receipts.create', [
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name', 'location']),
            'employees' => Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'canReceive' => $this->canReceive(),
            'currentBalances' => $selectedWarehouse === null ? collect() : $calc->stockBalances($selectedWarehouse),
        ]);
    }

    private function canReceive(): bool
    {
        return in_array($this->po->status, [
            PurchaseOrderStatus::Approved,
            PurchaseOrderStatus::Sent,
            PurchaseOrderStatus::PartiallyReceived,
        ], true);
    }
}
