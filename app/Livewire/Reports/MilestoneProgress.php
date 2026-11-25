<?php

namespace App\Livewire\Reports;

use App\Enums\MilestoneStatus;
use App\Services\CalculationService;
use Livewire\Attributes\Title;

#[Title('Milestone progress report')]
class MilestoneProgress extends ReportComponent
{
    public function render(CalculationService $calc)
    {
        $data = $calc->milestoneProgressReport($this->selectedProjectId());

        return view('livewire.reports.milestone-progress', array_merge($data, [
            'statusOptions' => MilestoneStatus::cases(),
            'printUrl' => $this->printUrl('milestone-progress'),
        ]));
    }
}
