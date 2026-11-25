<?php

namespace App\Livewire\Requisitions;

use App\Enums\RequisitionStatus;
use App\Models\MaterialRequisition;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Requisition detail')]
class Show extends Component
{
    public MaterialRequisition $requisition;

    public function mount(MaterialRequisition $mr): void
    {
        $this->requisition = $mr;
    }

    public function approve(): void
    {
        $this->authorize('approve');

        if ($this->requisition->status !== RequisitionStatus::Pending) {
            session()->flash('error', __('Only a pending requisition can be approved.'));

            return;
        }

        $this->requisition->update([
            'status' => RequisitionStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        session()->flash('success', __('Requisition approved.'));
    }

    public function reject(): void
    {
        $this->authorize('approve');

        if ($this->requisition->status !== RequisitionStatus::Pending) {
            session()->flash('error', __('Only a pending requisition can be rejected.'));

            return;
        }

        $this->requisition->update([
            'status' => RequisitionStatus::Rejected,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        session()->flash('success', __('Requisition rejected.'));
    }

    public function generatePo()
    {
        $this->authorize('approve');

        if ($this->requisition->status !== RequisitionStatus::Approved) {
            session()->flash('error', __('Only an approved requisition can generate a purchase order.'));

            return null;
        }

        if ($this->requisition->purchaseOrder()->exists()) {
            session()->flash('error', __('A purchase order already exists for this requisition.'));

            return null;
        }

        return $this->redirect(
            route('purchase-orders.create', ['requisition' => $this->requisition->id]),
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.requisitions.show', [
            'items' => $this->requisition->items()->with('boqItem')->get(),
        ]);
    }
}
