<?php

namespace App\Livewire\Reports;

use App\Services\CalculationService;
use Carbon\CarbonInterface;
use Livewire\Component;

abstract class ReportComponent extends Component
{
    public string $projectId = '';

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        $this->from = today()->subDays(30)->toDateString();
        $this->to = today()->toDateString();
    }

    protected function selectedProjectId(): ?int
    {
        return $this->projectId === '' ? null : (int) $this->projectId;
    }

    /** @return array{0: CarbonInterface, 1: CarbonInterface} */
    protected function dateRange(CalculationService $calc): array
    {
        return $calc->reportDateRange($this->from, $this->to);
    }

    protected function printUrl(string $report): string
    {
        return route('reports.print', array_filter([
            'report' => $report,
            'project_id' => $this->projectId,
            'from' => $this->from,
            'to' => $this->to,
        ], fn (string $value) => $value !== ''));
    }
}
