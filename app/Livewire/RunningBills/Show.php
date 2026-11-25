<?php

namespace App\Livewire\RunningBills;

use App\Enums\RunningBillStatus;
use App\Models\Project;
use App\Models\RunningBill;
use App\Services\CalculationService;
use App\Support\NumberToWords;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Running bill')]
class Show extends Component
{
    public RunningBill $bill;

    public function mount(RunningBill $bill): void
    {
        $this->bill = $bill;
    }

    public function certify(): void
    {
        $this->authorize('certify');

        if ($this->bill->status !== RunningBillStatus::Draft) {
            $this->addError('bill', __('Only draft bills can be certified.'));

            return;
        }

        $calc = app(CalculationService::class);
        $alreadyCertified = false;
        $advanceUnavailable = false;

        DB::transaction(function () use ($calc, &$alreadyCertified, &$advanceUnavailable) {
            $bill = RunningBill::query()->whereKey($this->bill->id)->lockForUpdate()->firstOrFail();

            if ($bill->status !== RunningBillStatus::Draft) {
                $alreadyCertified = true;

                return;
            }

            $project = Project::query()->whereKey($bill->project_id)->lockForUpdate()->firstOrFail();
            $available = $calc->runningBillAvailableAdvance($project, $bill->id);

            if ($bill->advance_adjusted_paisa > $available) {
                $advanceUnavailable = true;

                return;
            }

            $bill->update([
                'status' => RunningBillStatus::Certified,
                'certified_date' => today()->toDateString(),
            ]);

            $project->increment('advance_adjusted_paisa', $bill->advance_adjusted_paisa);

            $this->bill = $bill->refresh();
        });

        if ($alreadyCertified) {
            $this->addError('bill', __('Only draft bills can be certified.'));

            return;
        }

        if ($advanceUnavailable) {
            $this->addError('bill', __('The advance is no longer available for this bill.'));

            return;
        }

        session()->flash('success', __('Running bill certified.'));
    }

    public function render(CalculationService $calc)
    {
        $project = $this->bill->project;
        $totals = $calc->runningBillTotals(
            $this->bill->work_certified_paisa,
            $this->bill->advance_adjusted_paisa
        );

        return view('livewire.running-bills.show', [
            'project' => $project,
            'totals' => $totals,
            'workCertifiedPaisa' => $this->bill->work_certified_paisa,
            'amountInWords' => NumberToWords::rupees($totals['net_payable_paisa']),
            'canCertify' => $this->bill->status === RunningBillStatus::Draft,
        ]);
    }
}
