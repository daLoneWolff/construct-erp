<?php

namespace App\Livewire\ProjectJobs;

use App\Models\Project;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Project jobs')]
class Index extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->redirectRoute('projects.milestones', ['project' => $project], navigate: true);
    }

    public function render(CalculationService $calc)
    {
        return view('livewire.project-jobs.index', [
            'summary' => $calc->projectPaymentSummary($this->project),
        ]);
    }
}
