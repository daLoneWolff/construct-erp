<?php

namespace App\Livewire\ProjectJobs;

use App\Models\ProjectJob;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Task detail')]
class Show extends Component
{
    public ProjectJob $job;

    public function mount(ProjectJob $job): void
    {
        $this->job = $job;
    }

    public function render(CalculationService $calc)
    {
        $this->job->loadMissing(['project', 'milestone', 'contractor']);

        return view('livewire.project-jobs.show', [
            'paidPaisa' => $calc->jobPaidPaisa($this->job),
            'balancePaisa' => $calc->jobBalancePaisa($this->job),
            'payments' => $calc->jobPayments($this->job),
        ]);
    }
}
