<?php

namespace App\Livewire\Reconciliation;

use App\Models\Project;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Material reconciliation')]
class Index extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function render(CalculationService $calc)
    {
        $rows = $calc->reconciliation($this->project);

        return view('livewire.reconciliation.index', [
            'project' => $this->project,
            'rows' => $rows,
            'summary' => [
                'over_consumed' => $rows->where('status', 'over_consumed')->count(),
                'shortage' => $rows->where('status', 'shortage')->count(),
                'ok' => $rows->where('status', 'ok')->count(),
            ],
        ]);
    }
}
