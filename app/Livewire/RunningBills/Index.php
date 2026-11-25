<?php

namespace App\Livewire\RunningBills;

use App\Enums\RunningBillStatus;
use App\Models\Project;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Running bills')]
class Index extends Component
{
    use WithPagination;

    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function render(CalculationService $calc)
    {
        $query = $this->project->runningBills()->orderBy('bill_number');

        return view('livewire.running-bills.index', [
            'project' => $this->project,
            'bills' => (clone $query)->paginate(25),
            'summary' => [
                'certified_paisa' => (int) (clone $query)
                    ->whereIn('status', [RunningBillStatus::Certified, RunningBillStatus::Paid])
                    ->sum('work_certified_paisa'),
                'advance_available_paisa' => $calc->runningBillAvailableAdvance($this->project),
            ],
        ]);
    }
}
