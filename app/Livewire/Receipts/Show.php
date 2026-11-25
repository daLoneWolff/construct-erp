<?php

namespace App\Livewire\Receipts;

use App\Models\MaterialReceipt;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Material receipt')]
class Show extends Component
{
    public MaterialReceipt $receipt;

    public function mount(MaterialReceipt $receipt): void
    {
        $this->receipt = $receipt;
    }

    public function render()
    {
        return view('livewire.receipts.show', [
            'items' => $this->receipt->items()->with('purchaseOrderItem')->get(),
        ]);
    }
}
