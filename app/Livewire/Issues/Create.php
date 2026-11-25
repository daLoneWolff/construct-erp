<?php

namespace App\Livewire\Issues;

use App\Enums\StockTransactionType;
use App\Models\BoqItem;
use App\Models\Employee;
use App\Models\MaterialIssue;
use App\Models\Project;
use App\Models\StockLedger;
use App\Models\Warehouse;
use App\Services\CalculationService;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Issue material')]
class Create extends Component
{
    public string $projectId = '';

    public string $warehouseId = '';

    public string $issuedToEmployeeId = '';

    public string $issuedDate = '';

    public string $notes = '';

    /** @var array<int, array{description: string, unit: string, quantity: int|string, boq_item_id: int|string}> */
    public array $items = [];

    public function mount(): void
    {
        $this->authorize('receive');

        $this->issuedDate = today()->toDateString();
        $this->items = [$this->blankItem()];
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

    public function updatedProjectId(): void
    {
        if ($this->projectId === '') {
            return;
        }

        $warehouse = Warehouse::query()->where('project_id', $this->projectId)->value('id');

        if ($warehouse !== null) {
            $this->warehouseId = (string) $warehouse;
        }

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
    }

    public function save(): void
    {
        $this->authorize('receive');

        $this->items = array_values(array_filter(
            $this->items,
            fn ($row) => filled($row['description'] ?? null) && (float) ($row['quantity'] ?? 0) > 0
        ));

        $this->validate([
            'projectId' => ['required', Rule::exists('projects', 'id')],
            'warehouseId' => ['required', Rule::exists('warehouses', 'id')],
            'issuedToEmployeeId' => ['required', Rule::exists('employees', 'id')],
            'issuedDate' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['required', 'string', 'max:20'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.boq_item_id' => ['nullable', Rule::exists('boq_items', 'id')],
        ]);

        $boqIds = collect($this->items)->pluck('boq_item_id')->filter();

        if (BoqItem::query()->forProject($this->projectId)->whereIn('id', $boqIds)->count() !== $boqIds->count()) {
            $this->addError('items', __('Every BOQ item must belong to the selected project.'));

            return;
        }

        $warehouse = Warehouse::query()->findOrFail($this->warehouseId);
        $calculator = app(CalculationService::class);

        $demand = collect($this->items)
            ->groupBy(fn (array $row) => $row['description'].'|'.$row['unit'])
            ->map(fn ($rows) => $rows->sum(fn (array $row) => (float) $row['quantity']));

        foreach ($demand as $key => $quantity) {
            [$description, $unit] = explode('|', (string) $key, 2);
            $balance = $calculator->stockBalance($warehouse, $description, $unit);

            if ($quantity > $balance + 0.0001) {
                $this->addError('items', __('Insufficient stock for :item (:balance :unit available).', [
                    'item' => $description,
                    'balance' => $balance,
                    'unit' => $unit,
                ]));
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $issue = DB::transaction(function () use ($calculator, $warehouse) {
            $issue = MaterialIssue::create([
                'code' => DocumentNumber::issue(),
                'project_id' => (int) $this->projectId,
                'warehouse_id' => $warehouse->id,
                'issued_to_employee_id' => (int) $this->issuedToEmployeeId,
                'issued_date' => $this->issuedDate,
                'notes' => $this->notes ?: null,
            ]);

            foreach ($this->items as $row) {
                $quantity = (float) $row['quantity'];

                $issue->items()->create([
                    'description' => $row['description'],
                    'unit' => $row['unit'],
                    'quantity' => $quantity,
                    'boq_item_id' => filled($row['boq_item_id'] ?? null) ? (int) $row['boq_item_id'] : null,
                ]);

                $balance = $calculator->stockBalance($warehouse, $row['description'], $row['unit']);

                StockLedger::create([
                    'warehouse_id' => $warehouse->id,
                    'item_description' => $row['description'],
                    'unit' => $row['unit'],
                    'in_qty' => 0,
                    'out_qty' => $quantity,
                    'balance_qty' => $balance - $quantity,
                    'transaction_type' => StockTransactionType::Issue,
                    'reference_type' => 'MaterialIssue',
                    'reference_id' => $issue->id,
                    'transaction_date' => $this->issuedDate,
                ]);
            }

            return $issue;
        });

        session()->flash('success', __('Issue :code saved. Stock ledger updated.', ['code' => $issue->code]));

        $this->redirect(route('issues.index'), navigate: true);
    }

    public function render(CalculationService $calc)
    {
        $selectedWarehouse = $this->warehouseId === ''
            ? null
            : Warehouse::query()->find($this->warehouseId);

        return view('livewire.issues.create', [
            'projects' => Project::query()->where('status', 'active')->orderBy('code')->get(['id', 'code', 'name']),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name', 'location']),
            'employees' => Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'boqItems' => BoqItem::query()->forProject($this->projectId)->orderBy('code')->get(['id', 'code', 'description', 'unit']),
            'currentBalances' => $selectedWarehouse === null ? collect() : $calc->stockBalances($selectedWarehouse),
        ]);
    }

    /** @return array{description: string, unit: string, quantity: int|string, boq_item_id: int|string} */
    private function blankItem(): array
    {
        return ['description' => '', 'unit' => 'bag', 'quantity' => '', 'boq_item_id' => ''];
    }
}
