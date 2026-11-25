<?php

namespace App\Livewire\PurchaseOrders;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Purchase order detail')]
class Show extends Component
{
    public PurchaseOrder $order;

    public function mount(PurchaseOrder $po): void
    {
        $this->order = $po;
    }

    public function approve(): void
    {
        $this->authorize('approve');

        if ($this->order->status !== PurchaseOrderStatus::Pending) {
            session()->flash('error', __('Only a pending purchase order can be approved.'));

            return;
        }

        $this->order->update([
            'status' => PurchaseOrderStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        session()->flash('success', __('Purchase order approved.'));
    }

    public function markSent(): void
    {
        $this->authorize('manage');

        if ($this->order->status !== PurchaseOrderStatus::Approved) {
            session()->flash('error', __('Only an approved purchase order can be marked as sent.'));

            return;
        }

        $this->order->update(['status' => PurchaseOrderStatus::Sent]);

        session()->flash('success', __('Purchase order marked as sent.'));
    }

    public function render()
    {
        return view('livewire.purchase-orders.show', [
            'items' => $this->order->items()->with('boqItem')->get(),
            'totals' => app(CalculationService::class)->purchaseOrderTotals($this->order),
            'vatRate' => app(CalculationService::class)->vatRate(),
        ]);
    }
}
