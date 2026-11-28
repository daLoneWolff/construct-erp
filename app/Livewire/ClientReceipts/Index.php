<?php

namespace App\Livewire\ClientReceipts;

use App\Models\Project;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Client receipts')]
class Index extends Component
{
    use WithPagination;

    public string $projectId = '';

    public string $from = '';

    public string $to = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['projectId', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function render(CalculationService $calc)
    {
        $projectId = $this->projectId === '' ? null : (int) $this->projectId;

        return view('livewire.client-receipts.index', [
            'receipts' => $calc->filteredClientReceipts($projectId, $this->from, $this->to),
            'summary' => $calc->clientReceiptFilterSummary($projectId, $this->from, $this->to),
            'projects' => Project::query()->orderBy('code')->get(),
        ]);
    }
}
