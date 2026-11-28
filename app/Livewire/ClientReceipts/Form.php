<?php

namespace App\Livewire\ClientReceipts;

use App\Enums\ClientReceiptType;
use App\Enums\PaymentMethod;
use App\Models\ClientReceipt;
use App\Models\Project;
use App\Models\RunningBill;
use App\Services\CalculationService;
use App\Support\Npr;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Client receipt')]
class Form extends Component
{
    public ?ClientReceipt $receipt = null;

    public ?Project $project = null;

    public string $projectId = '';

    public string $runningBillId = '';

    public string $amount = '';

    public string $receivedOn = '';

    public string $type = 'advance';

    public string $method = 'cash';

    public string $referenceNo = '';

    public string $note = '';

    public function mount(?Project $project = null, ?ClientReceipt $clientReceipt = null): void
    {
        $this->authorize('manage');

        $this->project = $project;
        $this->receipt = $clientReceipt;

        if ($clientReceipt !== null) {
            $this->projectId = (string) $clientReceipt->project_id;
            $this->runningBillId = $clientReceipt->running_bill_id === null ? '' : (string) $clientReceipt->running_bill_id;
            $this->amount = Npr::toInput($clientReceipt->amount_paisa);
            $this->receivedOn = $clientReceipt->received_on->toDateString();
            $this->type = $clientReceipt->type->value;
            $this->method = $clientReceipt->method->value;
            $this->referenceNo = (string) ($clientReceipt->reference_no ?? '');
            $this->note = (string) ($clientReceipt->note ?? '');
            $this->project = $clientReceipt->project;
        } elseif ($project !== null) {
            $this->projectId = (string) $project->id;
            $this->receivedOn = today()->toDateString();
        } else {
            $this->receivedOn = today()->toDateString();
        }

        $runningBillId = request()->query('running_bill');
        if ($runningBillId !== null && $this->runningBillId === '') {
            $bill = RunningBill::query()->find((int) $runningBillId);
            if ($bill !== null) {
                $this->projectId = (string) $bill->project_id;
                $this->project = $bill->project;
                $this->runningBillId = (string) $bill->id;
                if ($this->type === 'advance') {
                    $this->type = ClientReceiptType::RunningBill->value;
                }
            }
        }
    }

    public function updatedProjectId(): void
    {
        $this->runningBillId = '';
    }

    public function save(CalculationService $calc): void
    {
        $this->authorize('manage');

        $this->validate([
            'projectId' => ['required', 'integer', Rule::exists('projects', 'id')],
            'runningBillId' => ['nullable', 'integer', Rule::exists('running_bills', 'id')],
            'amount' => ['required', 'string'],
            'receivedOn' => ['required', 'date'],
            'type' => ['required', Rule::enum(ClientReceiptType::class)],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'referenceNo' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $amountPaisa = Npr::toPaisa($this->amount);
        if ($amountPaisa <= 0) {
            $this->addError('amount', __('Enter a receipt amount greater than zero.'));

            return;
        }

        $project = Project::query()->findOrFail((int) $this->projectId);

        $runningBill = null;
        if ($this->runningBillId !== '') {
            $runningBill = RunningBill::query()
                ->where('project_id', $project->id)
                ->find((int) $this->runningBillId);

            if ($runningBill === null) {
                $this->addError('runningBillId', __('Select a running bill from this project.'));

                return;
            }

            if ($calc->receiptWouldOverpayBill($runningBill, $amountPaisa, $this->receipt?->id)) {
                $this->addError('amount', __('Amount exceeds the remaining receivable for this running bill.'));

                return;
            }
        }

        $summary = $calc->projectClientMoneySummary($project);
        $collectedExcludingThis = $summary['collected_paisa'];
        if ($this->receipt !== null && (int) $this->receipt->project_id === (int) $project->id) {
            $collectedExcludingThis -= (int) $this->receipt->amount_paisa;
        } elseif ($this->receipt !== null) {
            // editing and moving project: exclude from old already reflected in old project, no-op here
        }

        $remainingToCollect = max(0, (int) $project->contract_value_paisa - $collectedExcludingThis);
        if ($amountPaisa > $remainingToCollect) {
            $this->addError('amount', __('Amount exceeds the remaining amount to collect for this project.'));

            return;
        }

        $attributes = [
            'project_id' => $project->id,
            'running_bill_id' => $runningBill?->id,
            'receipt_no' => $this->receipt?->receipt_no ?? $calc->nextClientReceiptNo(Carbon::parse($this->receivedOn)),
            'received_on' => $this->receivedOn,
            'amount_paisa' => $amountPaisa,
            'type' => $this->type,
            'method' => $this->method,
            'reference_no' => $this->referenceNo ?: null,
            'note' => $this->note ?: null,
        ];

        if ($this->receipt !== null) {
            $this->receipt->update($attributes);
            $receipt = $this->receipt;
            session()->flash('success', __('Client receipt updated.'));
        } else {
            $attributes['created_by'] = auth()->id();
            $receipt = ClientReceipt::create($attributes);
            session()->flash('success', __('Client receipt recorded.'));
        }

        $this->redirect(route('client-receipts.show', $receipt), navigate: true);
    }

    public function render(CalculationService $calc)
    {
        $selectedProject = $this->projectId !== ''
            ? Project::query()->find((int) $this->projectId)
            : $this->project;

        $runningBills = $selectedProject
            ? RunningBill::query()->where('project_id', $selectedProject->id)->orderByDesc('bill_number')->get()
            : collect();

        $summary = $selectedProject ? $calc->projectClientMoneySummary($selectedProject) : null;

        if ($summary !== null) {
            $enteredPaisa = Npr::toPaisa($this->amount);
            $enteredPaisa = $enteredPaisa > 0 ? $enteredPaisa : 0;

            $collectedPaisa = (int) $summary['collected_paisa'];
            if ($this->receipt !== null && (int) $this->receipt->project_id === (int) $selectedProject->id) {
                $collectedPaisa -= (int) $this->receipt->amount_paisa;
            }

            $collectedPaisa += $enteredPaisa;

            $summary['collected_paisa'] = $collectedPaisa;
            $summary['remaining_to_collect_paisa'] = max(0, (int) $summary['contract_value_paisa'] - $collectedPaisa);
        }

        return view('livewire.client-receipts.form', [
            'isEdit' => $this->receipt !== null,
            'projects' => Project::query()->orderBy('code')->get(),
            'runningBills' => $runningBills,
            'clientMoney' => $summary,
            'types' => ClientReceiptType::cases(),
            'methods' => PaymentMethod::cases(),
        ]);
    }
}
