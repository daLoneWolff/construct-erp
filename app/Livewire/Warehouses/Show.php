<?php

namespace App\Livewire\Warehouses;

use App\Models\Warehouse;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Warehouse')]
class Show extends Component
{
    public Warehouse $warehouse;

    public function mount(Warehouse $wh): void
    {
        $this->warehouse = $wh;
    }

    public function render(CalculationService $calc)
    {
        return view('livewire.warehouses.show', [
            'balances' => $calc->stockBalances($this->warehouse),
            'receipts' => $this->warehouse->receipts()
                ->with('purchaseOrder')
                ->orderByDesc('received_date')
                ->get(),
            'issues' => $this->warehouse->issues()
                ->with('project')
                ->orderByDesc('issued_date')
                ->get(),
        ]);
    }
}
