<?php

namespace App\Livewire\Payments;

use App\Enums\PaymentMethod;
use App\Models\Contractor;
use App\Models\ContractorPayment;
use App\Models\ProjectJob;
use App\Services\CalculationService;
use App\Support\Npr;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Payment record')]
class Form extends Component
{
    public ?ContractorPayment $payment = null;

    public ?Contractor $contextContractor = null;

    public string $projectFilter = '';

    public string $jobId = '';

    public string $amount = '';

    public string $paidOn = '';

    public string $method = 'cash';

    public string $referenceNo = '';

    public string $note = '';

    public function mount(?ProjectJob $job = null, ?ContractorPayment $payment = null, ?Contractor $contractor = null): void
    {
        $this->authorize('manage');

        if ($payment !== null) {
            $this->payment = $payment;
            $this->jobId = (string) $payment->project_job_id;
            $this->amount = Npr::toInput($payment->amount_paisa);
            $this->paidOn = $payment->paid_on->toDateString();
            $this->method = $payment->method->value;
            $this->referenceNo = $payment->reference_no ?? '';
            $this->note = $payment->note ?? '';

            return;
        }

        if ($job !== null) {
            $this->jobId = (string) $job->id;
            $this->paidOn = today()->toDateString();
            $this->method = PaymentMethod::Cash->value;

            return;
        }

        if ($contractor !== null) {
            $this->contextContractor = $contractor;
            $this->projectFilter = (string) request()->query('project', '');
            $this->paidOn = today()->toDateString();
            $this->method = PaymentMethod::Cash->value;

            return;
        }

        abort(404);
    }

    public function save(): void
    {
        $this->authorize('manage');

        $this->validate([
            'jobId' => ['required', 'integer', 'exists:project_jobs,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paidOn' => ['required', 'date'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'referenceNo' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $calc = app(CalculationService::class);
        $job = $this->payment?->job ?? ProjectJob::query()->with('contractor')->findOrFail((int) $this->jobId);
        $amountPaisa = Npr::toPaisa($this->amount);

        if ($amountPaisa <= 0) {
            $this->addError('amount', __('Enter a payment amount greater than zero.'));

            return;
        }

        if ($this->contextContractor !== null && (int) $job->contractor_id !== (int) $this->contextContractor->id) {
            $this->addError('jobId', __('Select a task assigned to this contractor.'));

            return;
        }

        if ($this->payment === null && $job->contractor_id === null) {
            $this->addError('jobId', __('Assign a contractor to this job before recording a payment.'));

            return;
        }

        if ($calc->paymentWouldOverpay($job, $amountPaisa, $this->payment?->id)) {
            $this->addError('amount', __('Amount exceeds the remaining unpaid balance for this job.'));

            return;
        }

        $attributes = [
            'payment_no' => $this->payment?->payment_no ?? $calc->nextContractorPaymentNo(Carbon::parse($this->paidOn)),
            'project_id' => $job->project_id,
            'project_job_id' => $job->id,
            'contractor_id' => $this->payment?->contractor_id ?? $job->contractor_id,
            'amount_paisa' => $amountPaisa,
            'paid_on' => $this->paidOn,
            'method' => $this->method,
            'reference_no' => $this->referenceNo ?: null,
            'note' => $this->note ?: null,
        ];

        if ($this->payment !== null) {
            $this->payment->update($attributes);
            $payment = $this->payment;
            session()->flash('success', __('Payment updated.'));
        } else {
            $attributes['created_by'] = auth()->id();
            $payment = ContractorPayment::create($attributes);
            session()->flash('success', __('Payment recorded.'));
        }

        $this->redirect(route('payments.show', $payment), navigate: true);
    }

    public function render(CalculationService $calc)
    {
        $selectedJob = $this->payment?->job
            ?? ProjectJob::query()->with(['project', 'contractor'])->find((int) $this->jobId);
        $paidOn = $calc->paymentFilterDate($this->paidOn) ?? today();

        $jobs = collect();

        if ($this->payment === null) {
            $jobsQuery = ProjectJob::query()
                ->with(['project', 'contractor'])
                ->orderBy('project_id')
                ->orderBy('title');

            if ($this->contextContractor !== null) {
                $jobsQuery->where('contractor_id', $this->contextContractor->id);

                if ($this->projectFilter !== '') {
                    $jobsQuery->where('project_id', (int) $this->projectFilter);
                }
            }

            $jobs = $jobsQuery->get();

            if ($this->contextContractor !== null) {
                $jobs = $jobs
                    ->filter(fn (ProjectJob $job): bool => $calc->remainingPaymentCapacityPaisa($job) > 0)
                    ->values();
            }
        }

        $remainingPaisa = $selectedJob
            ? $calc->remainingPaymentCapacityPaisa($selectedJob, $this->payment?->id)
            : 0;
        $enteredPaisa = Npr::toPaisa($this->amount);
        $remainingAfterPaisa = max(0, $remainingPaisa - $enteredPaisa);

        return view('livewire.payments.form', [
            'isEdit' => $this->payment !== null,
            'jobs' => $jobs,
            'selectedJob' => $selectedJob,
            'payee' => $this->payment?->contractor ?? $selectedJob?->contractor ?? $this->contextContractor,
            'remainingPaisa' => $remainingPaisa,
            'remainingAfterPaisa' => $remainingAfterPaisa,
            'methods' => PaymentMethod::cases(),
            'paymentNo' => $this->payment?->payment_no ?? $calc->nextContractorPaymentNo($paidOn),
            'contextContractor' => $this->contextContractor,
        ]);
    }
}
