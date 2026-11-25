<?php

namespace App\Livewire\Dsr;

use App\Enums\DsrStatus;
use App\Models\DailySiteReport;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Site report')]
class Show extends Component
{
    public DailySiteReport $dsr;

    public function mount(DailySiteReport $dsr): void
    {
        $this->dsr = $dsr;
    }

    public function approve(): void
    {
        $this->authorize('approve');

        if ($this->dsr->status !== DsrStatus::Submitted) {
            return;
        }

        $this->dsr->update([
            'status' => DsrStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        session()->flash('success', __('Site report approved.'));
    }

    public function reject(): void
    {
        $this->authorize('approve');

        $this->dsr->update([
            'status' => DsrStatus::Rejected,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        session()->flash('success', __('Site report rejected.'));
    }

    public function render()
    {
        $this->dsr->load([
            'project',
            'submittedBy',
            'approvedBy',
            'manpower',
            'equipment.equipment',
            'materials.boqItem',
            'photos',
        ]);

        return view('livewire.dsr.show', [
            'report' => $this->dsr,
        ]);
    }
}
