<?php

use App\Enums\ProjectStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Contractor;
use App\Models\ContractorPayment;
use App\Models\Project;
use App\Models\ProjectJob;
use App\Models\User;
use App\Services\CalculationService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function contractorCoreUser(): User
{
    return User::create([
        'name' => 'Core Admin',
        'email' => 'core-admin@smoke.test',
        'email_verified_at' => now(),
        'password' => 'password',
        'role' => Role::Admin,
    ]);
}

function contractorCoreProject(int $managerId, string $code): Project
{
    return Project::create([
        'code' => $code,
        'name' => 'Core Project '.$code,
        'client_name' => 'Test Client',
        'contract_value_paisa' => 1000000000,
        'location_ward' => '12',
        'location_municipality' => 'Lalitpur',
        'location_district' => 'Lalitpur',
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => $managerId,
        'advance_paisa' => 0,
        'status' => ProjectStatus::Active,
    ]);
}

function contractorCoreContractor(string $name): Contractor
{
    return Contractor::create([
        'name' => $name,
        'phone' => '9841000301',
        'address' => 'Lalitpur',
        'pan' => '123456789',
        'is_active' => true,
        'notes' => 'Core test payee.',
    ]);
}

function contractorCoreJob(Project $project, ?Contractor $contractor, int $agreedPaisa): ProjectJob
{
    return ProjectJob::create([
        'project_id' => $project->id,
        'contractor_id' => $contractor?->id,
        'title' => 'Bricklaying '.$agreedPaisa,
        'description' => 'Core test job.',
        'agreed_amount_paisa' => $agreedPaisa,
        'status' => 'todo',
    ]);
}

function contractorCorePayment(
    ProjectJob $job,
    Contractor $contractor,
    string $paymentNo,
    int $amountPaisa,
    string $paidOn
): ContractorPayment {
    return ContractorPayment::create([
        'payment_no' => $paymentNo,
        'project_id' => $job->project_id,
        'project_job_id' => $job->id,
        'contractor_id' => $contractor->id,
        'amount_paisa' => $amountPaisa,
        'paid_on' => $paidOn,
        'method' => 'bank_transfer',
        'reference_no' => 'REF-'.$paymentNo,
        'note' => 'Core test payment.',
    ]);
}

function contractorCoreFixture(): array
{
    $user = contractorCoreUser();
    $projectOne = contractorCoreProject($user->id, 'PRJ-CORE-1');
    $projectTwo = contractorCoreProject($user->id, 'PRJ-CORE-2');
    $first = contractorCoreContractor('Dhan Bahadur');
    $second = contractorCoreContractor('Krishna Plumbing');

    $jobOne = contractorCoreJob($projectOne, $first, 200000000);
    $jobTwo = contractorCoreJob($projectOne, $second, 100000000);
    $jobUnassigned = contractorCoreJob($projectOne, null, 50000000);
    $jobProjectTwo = contractorCoreJob($projectTwo, $first, 300000000);

    contractorCorePayment($jobOne, $first, 'CTP-2026-0001', 80000000, '2026-10-01');
    contractorCorePayment($jobOne, $first, 'CTP-2026-0002', 40000000, '2026-10-15');
    contractorCorePayment($jobTwo, $second, 'CTP-2026-0003', 100000000, '2026-10-20');
    contractorCorePayment($jobProjectTwo, $first, 'CTP-2026-0004', 120000000, '2026-09-10');

    return compact(
        'user',
        'projectOne',
        'projectTwo',
        'first',
        'second',
        'jobOne',
        'jobTwo',
        'jobUnassigned',
        'jobProjectTwo'
    );
}

test('job balance is agreed amount minus every recorded payment', function () {
    $calc = app(CalculationService::class);
    $user = contractorCoreUser();
    $project = contractorCoreProject($user->id, 'PRJ-CORE-BAL');
    $contractor = contractorCoreContractor('Sita Finishes');
    $job = contractorCoreJob($project, $contractor, 300000000);

    contractorCorePayment($job, $contractor, 'CTP-2026-0001', 100000000, '2026-10-01');
    contractorCorePayment($job, $contractor, 'CTP-2026-0002', 75000000, '2026-10-10');

    expect($calc->jobPaidPaisa($job))->toBe(175000000)
        ->and($calc->jobBalancePaisa($job))->toBe(125000000)
        ->and($job->paid_amount_paisa)->toBe(175000000)
        ->and($job->balance_paisa)->toBe(125000000);
});

test('overpayment is rejected and an edited payment can be excluded from the capacity check', function () {
    $calc = app(CalculationService::class);
    $fixture = contractorCoreFixture();
    $job = $fixture['jobOne'];
    $contractor = $fixture['first'];

    $editedPayment = contractorCorePayment($job, $contractor, 'CTP-2026-0005', 75000000, '2026-10-25');

    expect($calc->remainingPaymentCapacityPaisa($job))->toBe(5000000)
        ->and($calc->paymentWouldOverpay($job, 5000001))->toBeTrue()
        ->and($calc->paymentWouldOverpay($job, 5000000))->toBeFalse()
        ->and($calc->remainingPaymentCapacityPaisa($job, $editedPayment->id))->toBe(80000000)
        ->and($calc->paymentWouldOverpay($job, 80000000, $editedPayment->id))->toBeFalse();
});

test('project, contractor and overall summaries roll up the same balances', function () {
    $calc = app(CalculationService::class);
    $fixture = contractorCoreFixture();

    $projectOne = $calc->projectPaymentSummary($fixture['projectOne']);
    $projectTwo = $calc->projectPaymentSummary($fixture['projectTwo']);
    $overall = $calc->overallPaymentSummary();
    $firstLedger = $calc->contractorLedger($fixture['first']);
    $secondLedger = $calc->contractorLedger($fixture['second']);

    expect($projectOne['agreed_paisa'])->toBe(350000000)
        ->and($projectOne['paid_paisa'])->toBe(220000000)
        ->and($projectOne['balance_paisa'])->toBe(130000000)
        ->and($projectOne['job_count'])->toBe(3)
        ->and($projectOne['contractor_count'])->toBe(2)
        ->and($projectOne['unassigned_job_count'])->toBe(1)
        ->and($projectTwo['balance_paisa'])->toBe(180000000)
        ->and($overall['agreed_paisa'])->toBe(650000000)
        ->and($overall['paid_paisa'])->toBe(340000000)
        ->and($overall['balance_paisa'])->toBe(310000000)
        ->and($overall['contractor_count'])->toBe(2)
        ->and($firstLedger['agreed_paisa'])->toBe(500000000)
        ->and($firstLedger['paid_paisa'])->toBe(240000000)
        ->and($firstLedger['balance_paisa'])->toBe(260000000)
        ->and($secondLedger['balance_paisa'])->toBe(0);
});

test('contractor ledger filters payments without changing the all-time balance', function () {
    $calc = app(CalculationService::class);
    $fixture = contractorCoreFixture();

    $filtered = $calc->contractorLedger(
        $fixture['first'],
        $fixture['projectOne']->id,
        Carbon::parse('2026-10-01'),
        Carbon::parse('2026-10-31')
    );

    expect($filtered['jobs'])->toHaveCount(1)
        ->and($filtered['agreed_paisa'])->toBe(200000000)
        ->and($filtered['paid_paisa'])->toBe(120000000)
        ->and($filtered['balance_paisa'])->toBe(80000000)
        ->and($filtered['filtered_paid_paisa'])->toBe(120000000)
        ->and($filtered['payment_count'])->toBe(2);
});

test('payment export rows use AD dates, plain NPR amounts and filters', function () {
    $calc = app(CalculationService::class);
    $fixture = contractorCoreFixture();

    $rows = $calc->paymentExportRows($fixture['projectOne']->id);
    $contractorRows = $calc->paymentExportRows($fixture['projectOne']->id, $fixture['first']->id);

    expect($calc->paymentExportHeaders())->toBe([
        'Payment No',
        'Date',
        'Project',
        'Job',
        'Contractor',
        'Method',
        'Reference',
        'Amount NPR',
        'Note',
        'Company',
        'Company PAN',
    ])
        ->and($rows)->toHaveCount(3)
        ->and($rows->first()['date'])->toBe('01/10/2026')
        ->and($rows->first()['amount_npr'])->toBe('800000.00')
        ->and($rows->first()['contractor'])->toBe('Dhan Bahadur')
        ->and($contractorRows)->toHaveCount(2)
        ->and($calc->nextContractorPaymentNo(Carbon::parse('2026-10-01')))->toBe('CTP-2026-0005');
});

test('contractor, job and payment writes are audited', function () {
    $user = contractorCoreUser();
    $project = contractorCoreProject($user->id, 'PRJ-CORE-AUDIT');
    $contractor = contractorCoreContractor('Audited Contractor');
    $job = contractorCoreJob($project, $contractor, 100000000);
    $payment = contractorCorePayment($job, $contractor, 'CTP-2026-0001', 25000000, '2026-10-01');

    expect(AuditLog::where('entity_type', Contractor::class)->where('action', 'created')->count())->toBe(1)
        ->and(AuditLog::where('entity_type', ProjectJob::class)->where('action', 'created')->count())->toBe(1)
        ->and(AuditLog::where('entity_type', ContractorPayment::class)->where('action', 'created')->count())->toBe(1);

    $job->update(['title' => 'Audited Job Updated']);
    $payment->delete();

    expect(AuditLog::where('entity_type', ProjectJob::class)->where('action', 'updated')->count())->toBe(1)
        ->and(AuditLog::where('entity_type', ContractorPayment::class)->where('action', 'deleted')->count())->toBe(1);
});

test('the database rejects non-positive contractor payment amounts', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('The payment amount CHECK constraint is PostgreSQL-specific.');
    }

    $job = contractorCoreFixture()['jobOne'];
    $contractor = $job->contractor;

    expect(fn () => DB::table('contractor_payments')->insert([
        'payment_no' => 'CTP-2026-0999',
        'project_id' => $job->project_id,
        'project_job_id' => $job->id,
        'contractor_id' => $contractor->id,
        'amount_paisa' => 0,
        'paid_on' => '2026-11-22',
        'method' => 'cash',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});
