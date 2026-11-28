<?php

namespace App\Livewire\Expenses;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Services\CalculationService;
use App\Support\Npr;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Expense')]
class Form extends Component
{
    public ?Expense $expense = null;

    public string $expenseDate = '';

    public string $amount = '';

    public string $category = 'other';

    public string $method = 'cash';

    public string $payee = '';

    public string $referenceNo = '';

    public string $description = '';

    /** @var array<int, array{name: string, size: int, mime: string}> */
    public array $uploads = [];

    public function mount(?Expense $expense = null): void
    {
        $this->authorize('manage');

        $this->expense = $expense;

        if ($expense !== null) {
            $this->expenseDate = $expense->expense_date->toDateString();
            $this->amount = Npr::toInput($expense->amount_paisa);
            $this->category = $expense->category->value;
            $this->method = $expense->method->value;
            $this->payee = (string) ($expense->payee ?? '');
            $this->referenceNo = (string) ($expense->reference_no ?? '');
            $this->description = (string) ($expense->description ?? '');
        } else {
            $this->expenseDate = today()->toDateString();
        }
    }

    public function removeUpload(int $index): void
    {
        unset($this->uploads[$index]);
        $this->uploads = array_values($this->uploads);
    }

    public function removeExistingAttachment(int $attachmentId): void
    {
        $this->authorize('manage');

        if ($this->expense === null) {
            return;
        }

        $attachment = ExpenseAttachment::query()
            ->where('expense_id', $this->expense->id)
            ->find($attachmentId);

        if ($attachment !== null) {
            $attachment->delete();
            $this->expense->unsetRelation('attachments');
        }
    }

    public function save(CalculationService $calc): void
    {
        $this->authorize('manage');

        $this->uploads = array_slice($this->uploads, 0, 8);

        $this->validate([
            'expenseDate' => ['required', 'date'],
            'amount' => ['required', 'string'],
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'payee' => ['nullable', 'string', 'max:255'],
            'referenceNo' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'uploads' => ['nullable', 'array', 'max:8'],
            'uploads.*.name' => ['required', 'string', 'max:255'],
            'uploads.*.size' => ['required', 'integer', 'min:0'],
            'uploads.*.mime' => ['required', 'string', 'max:100'],
        ]);

        $amountPaisa = Npr::toPaisa($this->amount);
        if ($amountPaisa <= 0) {
            $this->addError('amount', __('Enter an expense amount greater than zero.'));

            return;
        }

        $attributes = [
            'expense_date' => $this->expenseDate,
            'amount_paisa' => $amountPaisa,
            'category' => $this->category,
            'method' => $this->method,
            'payee' => $this->payee ?: null,
            'reference_no' => $this->referenceNo ?: null,
            'description' => $this->description ?: null,
        ];

        if ($this->expense !== null) {
            $this->expense->update($attributes);
            $expense = $this->expense;
            session()->flash('success', __('Expense updated.'));
        } else {
            $attributes['expense_no'] = $calc->nextExpenseNo(Carbon::parse($this->expenseDate));
            $attributes['created_by'] = auth()->id();
            $expense = Expense::create($attributes);
            session()->flash('success', __('Expense recorded.'));
        }

        foreach ($this->uploads as $upload) {
            $attachment = $expense->attachments()->create([
                'filename' => $upload['name'],
                'size_bytes' => (int) $upload['size'],
                'mime' => $upload['mime'],
                'path_mock' => '',
            ]);

            $attachment->update([
                'path_mock' => '/mock/'.sprintf(
                    '0000000%d-0000-4000-8000-%012d',
                    $attachment->id % 10,
                    $attachment->id
                ),
            ]);
        }

        $this->uploads = [];

        $this->redirect(route('expenses.show', $expense), navigate: true);
    }

    public function render()
    {
        $this->expense?->loadMissing('attachments');

        return view('livewire.expenses.form', [
            'isEdit' => $this->expense !== null,
            'categories' => ExpenseCategory::cases(),
            'methods' => PaymentMethod::cases(),
            'existingAttachments' => $this->expense?->attachments ?? collect(),
        ]);
    }
}
