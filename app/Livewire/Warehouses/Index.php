<?php

namespace App\Livewire\Warehouses;

use App\Models\Warehouse;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Warehouses')]
class Index extends Component
{
    public function render(CalculationService $calc)
    {
        $warehouses = Warehouse::query()
            ->with('project')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $itemCounts = $calc->warehouseItemCounts($warehouses->pluck('id')->all());

        return view('livewire.warehouses.index', [
            'warehouses' => $warehouses->map(fn (Warehouse $warehouse) => [
                'warehouse' => $warehouse,
                'item_count' => $itemCounts[$warehouse->id] ?? 0,
            ]),
        ]);
    }
}
