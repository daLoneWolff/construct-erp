<?php

namespace App\Livewire\Payments;

use App\Enums\PaymentMethod;
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

    public string $jobId = '';

    public string $amount = '';

    public string $paidOn = '';

    public string $method = 'cash';

    public string $referenceNo = '';

    public string $note = '';

    public function mount(?ProjectJob $job = null, ?ContractorPayment $payment = null): void
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

        if ($job === null) {
            abort(404);
        }

        $this->jobId = (string) $job->id;
        $this->paidOn = today()->toDateString();
        $this->method = PaymentMethod::Cash->value;
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

        return view('livewire.payments.form', [
            'isEdit' => $this->payment !== null,
            'jobs' => $this->payment === null
                ? ProjectJob::query()->with(['project', 'contractor'])->orderBy('project_id')->orderBy('title')->get()
                : collect(),
            'selectedJob' => $selectedJob,
            'payee' => $this->payment?->contractor ?? $selectedJob?->contractor,
            'remainingPaisa' => $selectedJob
                ? $calc->remainingPaymentCapacityPaisa($selectedJob, $this->payment?->id)
                : 0,
            'methods' => PaymentMethod::cases(),
            'paymentNo' => $this->payment?->payment_no ?? $calc->nextContractorPaymentNo($paidOn),
        ]);
    }
}
