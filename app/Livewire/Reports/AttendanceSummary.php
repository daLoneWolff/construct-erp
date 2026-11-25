<?php

namespace App\Livewire\Reports;

use App\Services\CalculationService;
use Livewire\Attributes\Title;

#[Title('Attendance summary report')]
class AttendanceSummary extends ReportComponent
{
    public function mount(): void
    {
        parent::mount();

        $this->projectId = (string) (app(CalculationService::class)->reportProjects()->first()?->id ?? '');
    }

    public function render(CalculationService $calc)
    {
        [$from, $to] = $this->dateRange($calc);
        $data = $calc->attendanceReport($this->selectedProjectId(), $from, $to);

        return view('livewire.reports.attendance-summary', array_merge($data, [
            'printUrl' => $this->printUrl('attendance-summary'),
        ]));
    }
}
