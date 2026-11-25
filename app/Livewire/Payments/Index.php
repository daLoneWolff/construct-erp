<?php

namespace App\Livewire\Payments;

use App\Models\Contractor;
use App\Models\Project;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Contractor payments')]
class Index extends Component
{
    use WithPagination;

    public string $projectId = '';

    public string $contractorId = '';

    public string $from = '';

    public string $to = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['projectId', 'contractorId', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function render(CalculationService $calc)
    {
        $projectId = $this->selectedId($this->projectId);
        $contractorId = $this->selectedId($this->contractorId);

        return view('livewire.payments.index', [
            'payments' => $calc->filteredPayments($projectId, $contractorId, $this->from, $this->to),
            'summary' => $calc->paymentFilterSummary($projectId, $contractorId, $this->from, $this->to),
            'projects' => Project::query()->orderBy('code')->get(),
            'contractors' => Contractor::query()->orderBy('name')->get(),
        ]);
    }

    private function selectedId(string $value): ?int
    {
        return $value === '' ? null : (int) $value;
    }
}
