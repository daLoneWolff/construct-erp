<?php

namespace App\Livewire\ClientReceipts;

use App\Models\ClientReceipt;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Client receipt')]
class Show extends Component
{
    public ClientReceipt $clientReceipt;

    public function mount(ClientReceipt $clientReceipt): void
    {
        $this->clientReceipt = $clientReceipt;
    }

    public function delete(): void
    {
        $this->authorize('manage');

        $project = $this->clientReceipt->project;
        $this->clientReceipt->delete();

        session()->flash('success', __('Client receipt deleted.'));

        $this->redirect(route('projects.client-receipts', $project), navigate: true);
    }

    public function render()
    {
        $this->clientReceipt->loadMissing(['project', 'runningBill', 'creator']);

        return view('livewire.client-receipts.show', [
            'receipt' => $this->clientReceipt,
        ]);
    }
}
