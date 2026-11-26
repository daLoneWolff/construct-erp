<?php

namespace App\Livewire\Contractors;

use App\Models\Contractor;
use App\Models\Project;
use App\Services\CalculationService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Contractor ledger')]
class Show extends Component
{
    public Contractor $contractor;

    public string $projectId = '';

    public string $from = '';

    public string $to = '';

    public function mount(Contractor $contractor): void
    {
        $this->contractor = $contractor;
    }

    public function render(CalculationService $calc)
    {
        $projectId = $this->projectId === '' ? null : (int) $this->projectId;

        $ledger = $calc->contractorLedger(
            $this->contractor,
            $projectId,
            $calc->paymentFilterDate($this->from),
            $calc->paymentFilterDate($this->to)
        );

        /** @var Collection<int, array<string, mixed>> $ledgerJobs */
        $ledgerJobs = $ledger['jobs'];

        return view('livewire.contractors.show', [
            'ledger' => $ledger,
            'projects' => Project::query()->orderBy('code')->get(),
            'hasPayableTasks' => $ledgerJobs->contains(fn (array $row): bool => (int) ($row['balance_paisa'] ?? 0) > 0),
        ]);
    }
}
