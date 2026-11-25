<?php

namespace App\Livewire\Reports;

use App\Enums\PurchaseOrderStatus;
use App\Services\CalculationService;
use Livewire\Attributes\Title;

#[Title('PO status report')]
class PoStatus extends ReportComponent
{
    public function render(CalculationService $calc)
    {
        [$from, $to] = $this->dateRange($calc);
        $data = $calc->poStatusReport($this->selectedProjectId(), $from, $to);

        return view('livewire.reports.po-status', array_merge($data, [
            'statusOptions' => PurchaseOrderStatus::cases(),
            'printUrl' => $this->printUrl('po-status'),
        ]));
    }
}
