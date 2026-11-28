<?php

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Livewire\Expenses\Form as ExpenseForm;
use App\Livewire\Expenses\Show as ExpenseShow;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Support\Npr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('expense index shows company expenses and summary', function () {
    $admin = tenantUser('expense-index@test.com');

    Expense::create([
        'expense_no' => 'EXP-'.today()->year.'-0001',
        'expense_date' => today()->toDateString(),
        'amount_paisa' => 1500000,
        'category' => ExpenseCategory::Office,
        'method' => PaymentMethod::Cash,
        'payee' => 'Office Vendor',
        'reference_no' => 'REF-1',
        'description' => 'Office supplies',
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('expenses.index'))
        ->assertOk()
        ->assertSee('Expenses')
        ->assertSee('EXP-'.today()->year.'-0001')
        ->assertSee(Npr::format(1500000), false);
});

test('expense can be recorded with mocked attachments', function () {
    $admin = tenantUser('expense-create@test.com');

    Livewire::actingAs($admin)
        ->test(ExpenseForm::class)
        ->set('expenseDate', today()->toDateString())
        ->set('amount', '2500')
        ->set('category', ExpenseCategory::Fuel->value)
        ->set('method', PaymentMethod::Cash->value)
        ->set('payee', 'Fuel Station')
        ->set('referenceNo', 'FUEL-1')
        ->set('description', 'Diesel')
        ->set('uploads', [
            ['name' => 'receipt.pdf', 'size' => 1024, 'mime' => 'application/pdf'],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $expense = Expense::query()->first();
    expect($expense)->not->toBeNull();
    expect($expense->amount_paisa)->toBe(250000);
    expect($expense->category)->toBe(ExpenseCategory::Fuel);
    expect($expense->payee)->toBe('Fuel Station');
    expect($expense->expense_no)->toStartWith('EXP-'.today()->year.'-');

    $attachment = ExpenseAttachment::query()->first();
    expect($attachment)->not->toBeNull();
    expect($attachment->expense_id)->toBe($expense->id);
    expect($attachment->filename)->toBe('receipt.pdf');
    expect($attachment->path_mock)->toStartWith('/mock/');
});

test('expense amount must be greater than zero', function () {
    $admin = tenantUser('expense-zero@test.com');

    Livewire::actingAs($admin)
        ->test(ExpenseForm::class)
        ->set('expenseDate', today()->toDateString())
        ->set('amount', '0')
        ->set('category', ExpenseCategory::Other->value)
        ->set('method', PaymentMethod::Cash->value)
        ->call('save')
        ->assertHasErrors(['amount']);

    expect(Expense::query()->count())->toBe(0);
});

test('expense can be updated and deleted', function () {
    $admin = tenantUser('expense-update@test.com');

    $expense = Expense::create([
        'expense_no' => 'EXP-'.today()->year.'-0001',
        'expense_date' => today()->toDateString(),
        'amount_paisa' => 100000,
        'category' => ExpenseCategory::Office,
        'method' => PaymentMethod::Cash,
        'payee' => 'Old Payee',
        'created_by' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test(ExpenseForm::class, ['expense' => $expense])
        ->set('amount', '2000')
        ->set('payee', 'New Payee')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $expense->refresh();
    expect($expense->amount_paisa)->toBe(200000);
    expect($expense->payee)->toBe('New Payee');

    Livewire::actingAs($admin)
        ->test(ExpenseShow::class, ['expense' => $expense])
        ->call('delete')
        ->assertRedirect(route('expenses.index'));

    expect(Expense::query()->count())->toBe(0);
});

test('expense is audited and scoped to company', function () {
    $admin = tenantUser('expense-audit@test.com');

    $expense = Expense::create([
        'expense_no' => 'EXP-'.today()->year.'-0001',
        'expense_date' => today()->toDateString(),
        'amount_paisa' => 100000,
        'category' => ExpenseCategory::Office,
        'method' => PaymentMethod::Cash,
        'created_by' => $admin->id,
    ]);

    expect(AuditLog::query()
        ->where('entity_type', Expense::class)
        ->where('entity_id', $expense->id)
        ->where('action', 'created')
        ->exists())->toBeTrue();

    $otherCompany = Company::create([
        'name' => 'Other Company',
        'pan' => '609999999',
        'vat' => '609999999',
        'address' => 'Kathmandu',
        'district' => 'Kathmandu',
        'fiscal_year' => '2083/84',
    ]);
    $other = tenantUser('expense-other@test.com', company: $otherCompany);
    $this->actingAs($other)
        ->get(route('expenses.show', $expense))
        ->assertNotFound();
});
