<?php

namespace App\Livewire\PurchaseOrders;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Purchase orders')]
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
        $orders = PurchaseOrder::query()
            ->with(['project', 'vendor'])
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->orderByDesc('order_date')
            ->orderByDesc('code')
            ->paginate(25);

        return view('livewire.purchase-orders.index', [
            'orders' => $orders,
            'statuses' => PurchaseOrderStatus::cases(),
        ]);
    }
}
