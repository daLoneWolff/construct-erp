<?php

namespace App\Livewire\Reports;

use App\Services\CalculationService;
use Livewire\Attributes\Title;

#[Title('Project summary report')]
class ProjectSummary extends ReportComponent
{
    public function render(CalculationService $calc)
    {
        $data = $calc->projectSummaryReport($this->selectedProjectId());

        return view('livewire.reports.project-summary', array_merge($data, [
            'printUrl' => $this->printUrl('project-summary'),
        ]));
    }
}
