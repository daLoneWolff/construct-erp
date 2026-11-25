<?php

namespace App\Livewire\RunningBills;

use App\Enums\RunningBillStatus;
use App\Models\Project;
use App\Models\RunningBill;
use App\Services\CalculationService;
use App\Support\DocumentNumber;
use App\Support\Npr;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('New running bill')]
class Create extends Component
{
    public Project $project;

    public string $periodFrom = '';

    public string $periodTo = '';

    public string $workCertified = '';

    public string $advanceAdjusted = '0';

    public string $notes = '';

    public function mount(Project $project): void
    {
        $this->authorize('certify');

        $this->project = $project;
        $this->periodFrom = today()->subMonth()->startOfMonth()->toDateString();
        $this->periodTo = today()->toDateString();
    }

    public function save(): void
    {
        $this->authorize('certify');

        $this->validate([
            'periodFrom' => ['required', 'date'],
            'periodTo' => ['required', 'date', 'after_or_equal:periodFrom'],
            'workCertified' => ['required', 'numeric', 'min:0.01'],
            'advanceAdjusted' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $calc = app(CalculationService::class);
        $workCertifiedPaisa = Npr::toPaisa($this->workCertified);
        $advanceAdjustedPaisa = Npr::toPaisa($this->advanceAdjusted);
        $totals = $calc->runningBillTotals(
            $workCertifiedPaisa,
            $advanceAdjustedPaisa
        );

        $available = $calc->runningBillAvailableAdvance($this->project);

        if ($advanceAdjustedPaisa > $available) {
            $this->addError('advanceAdjusted', __('Advance adjusted exceeds the remaining advance for this project.'));
        }

        if ($totals['net_payable_paisa'] < 0) {
            $this->addError('advanceAdjusted', __('Advance adjusted cannot exceed the certified work.'));
        }

        if ($this->getErrorBag()->any()) {
            return;
        }

        $billNumber = (int) $this->project->runningBills()->max('bill_number') + 1;

        $bill = RunningBill::create([
            'code' => DocumentNumber::runningBill(),
            'project_id' => $this->project->id,
            'bill_number' => $billNumber,
            'period_from' => $this->periodFrom,
            'period_to' => $this->periodTo,
            'work_certified_paisa' => $workCertifiedPaisa,
            'advance_adjusted_paisa' => $advanceAdjustedPaisa,
            'net_payable_paisa' => $totals['net_payable_paisa'],
            'certified_date' => null,
            'status' => RunningBillStatus::Draft,
            'notes' => $this->notes ?: null,
        ]);

        session()->flash('success', __('Running bill saved as a draft.'));

        $this->redirect(route('running-bills.show', $bill), navigate: true);
    }

    public function render(CalculationService $calc)
    {
        $totals = $calc->runningBillTotals(
            Npr::toPaisa($this->workCertified),
            Npr::toPaisa($this->advanceAdjusted)
        );

        return view('livewire.running-bills.create', [
            'totals' => $totals,
            'workCertifiedPaisa' => Npr::toPaisa($this->workCertified),
            'availableAdvancePaisa' => $calc->runningBillAvailableAdvance($this->project),
            'nextBillNumber' => (int) $this->project->runningBills()->max('bill_number') + 1,
        ]);
    }
}
