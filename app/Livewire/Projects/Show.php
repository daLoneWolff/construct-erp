<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Project dashboard')]
class Show extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function render(CalculationService $calc)
    {
        return view('livewire.projects.show', [
            'dashboard' => $calc->projectDashboard($this->project),
            'paymentSummary' => $calc->projectPaymentSummary($this->project),
            'jvSummary' => $calc->projectJvSummary($this->project),
        ]);
    }
}
