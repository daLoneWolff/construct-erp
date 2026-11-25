<?php

namespace App\Livewire\StockLedger;

use App\Models\StockLedger;
use App\Models\Warehouse;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Stock ledger')]
class Index extends Component
{
    use WithPagination;

    public ?Warehouse $warehouse = null;

    public function mount(?Warehouse $wh = null): void
    {
        $this->warehouse = $wh;
    }

    public function render(CalculationService $calc)
    {
        $warehouses = Warehouse::query()
            ->with('project')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $itemCounts = $calc->warehouseItemCounts($warehouses->pluck('id')->all());
        $warehouses = $warehouses->map(fn (Warehouse $warehouse) => [
            'warehouse' => $warehouse,
            'item_count' => $itemCounts[$warehouse->id] ?? 0,
        ]);

        $entries = $this->warehouse === null
            ? collect()
            : StockLedger::query()
                ->where('warehouse_id', $this->warehouse->id)
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->paginate(25);

        return view('livewire.stock-ledger.index', [
            'warehouses' => $warehouses,
            'selectedWarehouse' => $this->warehouse,
            'entries' => $entries,
            'balances' => $this->warehouse === null ? collect() : $calc->stockBalances($this->warehouse),
        ]);
    }
}
