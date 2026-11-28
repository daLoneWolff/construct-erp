<?php

use App\Enums\ClientReceiptType;
use App\Enums\PaymentMethod;
use App\Enums\ProjectStatus;
use App\Enums\RunningBillStatus;
use App\Livewire\ClientReceipts\Form as ClientReceiptForm;
use App\Livewire\ClientReceipts\ProjectIndex as ProjectClientReceipts;
use App\Models\ClientReceipt;
use App\Models\Project;
use App\Models\RunningBill;
use App\Models\User;
use App\Support\Npr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function clientReceiptProject(User $manager, string $code = 'PRJ-CR-1', int $contractValuePaisa = 100000000, int $advancePaisa = 0): Project
{
    return Project::create([
        'code' => $code,
        'name' => 'Client Receipt Project '.$code,
        'client_name' => 'Test Client',
        'contract_value_paisa' => $contractValuePaisa,
        'location_ward' => '1',
        'location_municipality' => 'Kathmandu',
        'location_district' => 'Kathmandu',
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => $manager->id,
        'advance_paisa' => $advancePaisa,
        'status' => ProjectStatus::Active,
    ]);
}

test('project show displays contract value and collected from client against full contract value', function () {
    $admin = tenantUser('client-receipt-admin@test.com');
    $project = clientReceiptProject($admin, contractValuePaisa: 50000000, advancePaisa: 10000000);

    ClientReceipt::create([
        'project_id' => $project->id,
        'receipt_no' => 'CR-'.today()->year.'-0001',
        'received_on' => today()->toDateString(),
        'amount_paisa' => 10000000,
        'type' => ClientReceiptType::Advance,
        'method' => PaymentMethod::BankTransfer,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('Client money')
        ->assertSee('Contract value')
        ->assertSee('Collected from client')
        ->assertSee('Remaining to collect')
        ->assertSee(Npr::format(50000000), false)
        ->assertSee(Npr::format(10000000), false)
        ->assertSee(Npr::format(40000000), false);
});

test('client receipt can be recorded against full contract value', function () {
    $admin = tenantUser('client-receipt-record@test.com');
    $project = clientReceiptProject($admin, contractValuePaisa: 100000000);

    Livewire::actingAs($admin)
        ->test(ClientReceiptForm::class, ['project' => $project])
        ->set('amount', '25000')
        ->set('receivedOn', today()->toDateString())
        ->set('type', ClientReceiptType::Advance->value)
        ->set('method', PaymentMethod::Cash->value)
        ->set('referenceNo', 'ADV-1')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $receipt = ClientReceipt::query()->first();
    expect($receipt)->not->toBeNull();
    expect($receipt->amount_paisa)->toBe(2500000);
    expect($receipt->project_id)->toBe($project->id);
    expect($receipt->type)->toBe(ClientReceiptType::Advance);
});

test('client receipt cannot exceed remaining amount to collect for project', function () {
    $admin = tenantUser('client-receipt-over@test.com');
    $project = clientReceiptProject($admin, contractValuePaisa: 10000000);

    Livewire::actingAs($admin)
        ->test(ClientReceiptForm::class, ['project' => $project])
        ->set('amount', '150000')
        ->set('receivedOn', today()->toDateString())
        ->set('type', ClientReceiptType::Other->value)
        ->set('method', PaymentMethod::Cash->value)
        ->call('save')
        ->assertHasErrors(['amount']);

    expect(ClientReceipt::query()->count())->toBe(0);
});

test('bill-linked client receipt cannot exceed running bill remaining receivable', function () {
    $admin = tenantUser('client-receipt-bill@test.com');
    $project = clientReceiptProject($admin, contractValuePaisa: 100000000);
    $bill = RunningBill::create([
        'code' => 'RB-'.today()->year.'-0001',
        'project_id' => $project->id,
        'bill_number' => 1,
        'period_from' => today()->subMonth()->toDateString(),
        'period_to' => today()->toDateString(),
        'work_certified_paisa' => 5000000,
        'advance_adjusted_paisa' => 0,
        'net_payable_paisa' => 5000000,
        'status' => RunningBillStatus::Certified,
    ]);

    Livewire::actingAs($admin)
        ->test(ClientReceiptForm::class, ['project' => $project])
        ->set('runningBillId', (string) $bill->id)
        ->set('amount', '60000')
        ->set('receivedOn', today()->toDateString())
        ->set('type', ClientReceiptType::RunningBill->value)
        ->set('method', PaymentMethod::BankTransfer->value)
        ->call('save')
        ->assertHasErrors(['amount']);
});

test('project client receipts page shows receipts and summary', function () {
    $admin = tenantUser('client-receipt-index@test.com');
    $project = clientReceiptProject($admin, contractValuePaisa: 100000000);

    ClientReceipt::create([
        'project_id' => $project->id,
        'receipt_no' => 'CR-'.today()->year.'-0001',
        'received_on' => today()->toDateString(),
        'amount_paisa' => 2500000,
        'type' => ClientReceiptType::Advance,
        'method' => PaymentMethod::Cash->value,
        'created_by' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test(ProjectClientReceipts::class, ['project' => $project])
        ->assertSee('CR-'.today()->year.'-0001')
        ->assertSee(Npr::format(2500000), false)
        ->assertSee(Npr::format(100000000), false);
});
