<?php

namespace App\Livewire\Dsr;

use App\Enums\DsrStatus;
use App\Models\Project;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Daily site reports')]
class Index extends Component
{
    use WithPagination;

    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function approve(int $dsrId): void
    {
        $this->authorize('approve');

        $report = $this->project->dailySiteReports()->findOrFail($dsrId);

        if ($report->status !== DsrStatus::Submitted) {
            return;
        }

        $report->update([
            'status' => DsrStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        session()->flash('success', __('Site report for :date approved.', [
            'date' => $report->report_date->format('d/m/Y'),
        ]));
    }

    public function reject(int $dsrId): void
    {
        $this->authorize('approve');

        $report = $this->project->dailySiteReports()->findOrFail($dsrId);

        $report->update([
            'status' => DsrStatus::Rejected,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        session()->flash('success', __('Site report for :date rejected.', [
            'date' => $report->report_date->format('d/m/Y'),
        ]));
    }

    public function render()
    {
        $reports = $this->project->dailySiteReports()
            ->with('submittedBy')
            ->orderByDesc('report_date')
            ->paginate(25);

        return view('livewire.dsr.index', [
            'reports' => $reports,
            'canCreate' => true,
        ]);
    }
}
