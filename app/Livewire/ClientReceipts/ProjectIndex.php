<?php

namespace App\Livewire\ClientReceipts;

use App\Models\Project;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Client receipts')]
class ProjectIndex extends Component
{
    use WithPagination;

    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function render(CalculationService $calc)
    {
        return view('livewire.client-receipts.project-index', [
            'summary' => $calc->projectClientMoneySummary($this->project),
            'receipts' => $calc->filteredClientReceipts($this->project->id),
        ]);
    }
}
