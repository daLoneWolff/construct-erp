<?php

namespace App\Livewire\Payments;

use App\Models\Project;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Project payments')]
class ProjectSummary extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function render(CalculationService $calc)
    {
        return view('livewire.payments.project-summary', [
            'summary' => $calc->projectPaymentSummary($this->project),
            'payments' => $calc->filteredPayments($this->project->id),
        ]);
    }
}
