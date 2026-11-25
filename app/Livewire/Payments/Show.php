<?php

namespace App\Livewire\Payments;

use App\Models\ContractorPayment;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Payment detail')]
class Show extends Component
{
    public ContractorPayment $payment;

    public function mount(ContractorPayment $payment): void
    {
        $this->payment = $payment;
    }

    public function delete(): void
    {
        $this->authorize('manage');

        $this->payment->delete();

        session()->flash('success', __('Payment deleted.'));

        $this->redirect(route('payments.index'), navigate: true);
    }

    public function render()
    {
        $this->payment->loadMissing(['project', 'job', 'contractor', 'createdBy']);

        return view('livewire.payments.show');
    }
}
