<?php

namespace App\Livewire\Reports;

use App\Services\CalculationService;
use Livewire\Attributes\Title;

#[Title('Reconciliation report')]
class Reconciliation extends ReportComponent
{
    public function mount(): void
    {
        parent::mount();

        $this->projectId = (string) (app(CalculationService::class)->reportProjects()->first()?->id ?? '');
    }

    public function render(CalculationService $calc)
    {
        $data = $calc->reconciliationReport($this->selectedProjectId());

        return view('livewire.reports.reconciliation', array_merge($data, [
            'printUrl' => $this->printUrl('reconciliation'),
        ]));
    }
}
