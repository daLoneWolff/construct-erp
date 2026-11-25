<?php

namespace App\Livewire\Receipts;

use App\Enums\ReceiptStatus;
use App\Models\MaterialReceipt;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Material receipts')]
class Index extends Component
{
    use WithPagination;

    public string $status = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $receipts = MaterialReceipt::query()
            ->with(['purchaseOrder.vendor', 'warehouse', 'receivedBy'])
            ->withCount('items')
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->orderByDesc('received_date')
            ->orderByDesc('code')
            ->paginate(25);

        return view('livewire.receipts.index', [
            'receipts' => $receipts,
            'statuses' => ReceiptStatus::cases(),
        ]);
    }
}
