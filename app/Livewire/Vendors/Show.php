<?php

namespace App\Livewire\Vendors;

use App\Models\Vendor;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Vendor detail')]
class Show extends Component
{
    public Vendor $vendor;

    public function mount(Vendor $vendor): void
    {
        $this->vendor = $vendor;
    }

    public function render()
    {
        return view('livewire.vendors.show', [
            'purchaseOrders' => $this->vendor->purchaseOrders()
                ->with('project')
                ->orderByDesc('order_date')
                ->get(),
            'totalPayablePaisa' => app(CalculationService::class)->vendorTotalPayable($this->vendor),
        ]);
    }
}
