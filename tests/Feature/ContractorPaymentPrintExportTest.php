<?php

use App\Enums\ProjectStatus;
use App\Models\Company;
use App\Models\Contractor;
use App\Models\ContractorPayment;
use App\Models\Project;
use App\Models\ProjectJob;
use App\Models\User;
use App\Services\CalculationService;
use App\Support\Npr;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function contractorPrintAdmin(Company $company): User
{
    return tenantUser('print-admin@smoke.test', [
        'name' => 'Print Admin',
    ], $company);
}

function contractorPrintCompany(): Company
{
    return tenantCompany([
        'name' => 'Himalaya Print Builders',
        'pan' => '123456789',
        'vat' => '123456789',
        'address' => 'Lalitpur',
        'district' => 'Lalitpur',
        'fiscal_year' => '2083/84',
    ]);
}

function contractorPrintProject(int $managerId, string $code): Project
{
    return Project::create([
        'code' => $code,
        'name' => 'Print Project '.$code,
        'client_name' => 'Print Client',
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

function contractorPrintContractor(string $name, string $pan): Contractor
{
    return Contractor::create([
        'name' => $name,
        'phone' => '9841000901',
        'address' => 'Lalitpur',
        'pan' => $pan,
        'is_active' => true,
        'notes' => 'Print test payee.',
    ]);
}

function contractorPrintJob(Project $project, ?Contractor $contractor, int $agreedPaisa, string $title): ProjectJob
{
    return ProjectJob::create([
        'project_id' => $project->id,
        'contractor_id' => $contractor?->id,
        'title' => $title,
        'description' => 'Print test job.',
        'agreed_amount_paisa' => $agreedPaisa,
        'status' => 'todo',
        'starts_on' => today()->subWeek()->toDateString(),
        'due_on' => today()->addWeek()->toDateString(),
    ]);
}

function contractorPrintPayment(
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
        'note' => 'Print test payment.',
    ]);
}

function contractorPrintFixture(): array
{
    $company = contractorPrintCompany();
    $admin = contractorPrintAdmin($company);
    $projectOne = contractorPrintProject($admin->id, 'PRJ-PRINT-1');
    $projectTwo = contractorPrintProject($admin->id, 'PRJ-PRINT-2');
    $first = contractorPrintContractor('Dhan Bahadur Mason', '112233445');
    $second = contractorPrintContractor('Krishna Plumbing Works', '223344556');

    $jobOne = contractorPrintJob($projectOne, $first, 200000000, 'Bricklaying and masonry');
    $jobTwo = contractorPrintJob($projectOne, $second, 100000000, 'Plumbing work');
    $jobUnassigned = contractorPrintJob($projectOne, null, 50000000, 'Site clearing');
    $jobProjectTwo = contractorPrintJob($projectTwo, $first, 300000000, 'Bridge deck works');

    $payments = [
        contractorPrintPayment($jobOne, $first, 'CTP-2026-0001', 80000000, '2026-10-01'),
        contractorPrintPayment($jobOne, $first, 'CTP-2026-0002', 40000000, '2026-10-15'),
        contractorPrintPayment($jobTwo, $second, 'CTP-2026-0003', 100000000, '2026-10-20'),
        contractorPrintPayment($jobProjectTwo, $first, 'CTP-2026-0004', 120000000, '2026-09-10'),
    ];

    return compact(
        'admin',
        'company',
        'projectOne',
        'projectTwo',
        'first',
        'second',
        'jobOne',
        'jobTwo',
        'jobUnassigned',
        'jobProjectTwo',
        'payments'
    );
}

test('admin can open all three print views with expected markers', function () {
    $fixture = contractorPrintFixture();

    $this->actingAs($fixture['admin'])
        ->get(route('print.payment', $fixture['payments'][0]))
        ->assertOk()
        ->assertSee('Himalaya Print Builders')
        ->assertSee('PAYMENT RECEIPT')
        ->assertSee('CTP-2026-0001')
        ->assertSee('Dhan Bahadur Mason')
        ->assertSee(Npr::format(80000000), false)
        ->assertSee('window.print()', false);

    $this->actingAs($fixture['admin'])
        ->get(route('print.contractor-ledger', $fixture['first']))
        ->assertOk()
        ->assertSee('CONTRACTOR LEDGER')
        ->assertSee('Dhan Bahadur Mason')
        ->assertSee('CTP-2026-0001')
        ->assertSee('CTP-2026-0004')
        ->assertSee(Npr::format(500000000), false)
        ->assertSee(Npr::format(240000000), false)
        ->assertSee('window.print()', false);

    $this->actingAs($fixture['admin'])
        ->get(route('print.project-payments', $fixture['projectOne']))
        ->assertOk()
        ->assertSee('PROJECT PAYMENT SUMMARY')
        ->assertSee('PRJ-PRINT-1')
        ->assertSee('Dhan Bahadur Mason')
        ->assertSee('Krishna Plumbing Works')
        ->assertSee('CTP-2026-0003')
        ->assertDontSee('CTP-2026-0004')
        ->assertSee('window.print()', false);
});

test('contractor print ledger applies filters without changing all-time totals', function () {
    $fixture = contractorPrintFixture();

    $this->actingAs($fixture['admin'])
        ->get(route('print.contractor-ledger', [
            'contractor' => $fixture['first']->id,
            'project' => $fixture['projectOne']->id,
            'from' => '2026-10-15',
            'to' => '2026-10-15',
        ]))
        ->assertOk()
        ->assertSee('Print Project PRJ-PRINT-1')
        ->assertSee('15/10/2026')
        ->assertSee('CTP-2026-0002')
        ->assertDontSee('CTP-2026-0001')
        ->assertDontSee('CTP-2026-0004')
        ->assertSee(Npr::format(40000000), false)
        ->assertSee(Npr::format(200000000), false)
        ->assertSee(Npr::format(120000000), false);
});

test('payment csv export streams headers and filtered rows', function () {
    $fixture = contractorPrintFixture();

    $response = $this->actingAs($fixture['admin'])
        ->get(route('payments.export', [
            'project' => $fixture['projectOne']->id,
            'contractor' => $fixture['first']->id,
            'from' => '2026-10-01',
            'to' => '2026-10-15',
        ]))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    expect($response->headers->get('content-disposition'))
        ->toContain('contractor-payments-'.today()->toDateString().'.csv');

    $content = $response->streamedContent();
    $lines = array_values(array_filter(
        explode("\n", $content),
        fn (string $line): bool => trim($line) !== ''
    ));
    $rows = array_map(fn (string $line): array => str_getcsv($line), $lines);

    expect($rows[0])->toBe(app(CalculationService::class)->paymentExportHeaders())
        ->and($rows)->toHaveCount(3)
        ->and(array_column(array_slice($rows, 1), 0))->toBe(['CTP-2026-0001', 'CTP-2026-0002'])
        ->and($rows[1][7])->toBe('800000.00')
        ->and($rows[1][2])->toBe('Print Project PRJ-PRINT-1')
        ->and($content)->not->toContain('रू')
        ->and($content)->not->toContain('CTP-2026-0004');

    $allContent = $this->actingAs($fixture['admin'])
        ->get(route('payments.export'))
        ->assertOk()
        ->streamedContent();

    $allRows = array_values(array_filter(
        explode("\n", $allContent),
        fn (string $line): bool => trim($line) !== ''
    ));

    expect($allRows)->toHaveCount(5);
});

test('print and export endpoints require authentication', function () {
    $fixture = contractorPrintFixture();
    $urls = [
        route('print.payment', $fixture['payments'][0]),
        route('print.contractor-ledger', $fixture['first']),
        route('print.project-payments', $fixture['projectOne']),
        route('payments.export'),
    ];

    foreach ($urls as $url) {
        $this->get($url)->assertRedirect('/login');
    }
});

test('print and csv actions are reachable from the payment screens', function () {
    $fixture = contractorPrintFixture();

    $this->actingAs($fixture['admin'])
        ->get(route('payments.index'))
        ->assertOk()
        ->assertSee('Export CSV');

    $this->actingAs($fixture['admin'])
        ->get(route('payments.show', $fixture['payments'][0]))
        ->assertOk()
        ->assertSee('Print receipt');

    $this->actingAs($fixture['admin'])
        ->get(route('contractors.show', $fixture['first']))
        ->assertOk()
        ->assertSee('Print ledger');

    $this->actingAs($fixture['admin'])
        ->get(route('projects.payments', $fixture['projectOne']))
        ->assertOk()
        ->assertSee('Print summary');
});
