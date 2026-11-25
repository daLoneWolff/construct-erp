<?php

use App\Enums\ProjectStatus;
use App\Enums\Role;
use App\Enums\RunningBillStatus;
use App\Livewire\RunningBills\Create as RunningBillCreate;
use App\Livewire\RunningBills\Show as RunningBillShow;
use App\Models\Project;
use App\Models\RunningBill;
use App\Models\User;
use App\Services\CalculationService;
use App\Support\Npr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function rbUser(string $email, Role $role): User
{
    return tenantUser($email, [
        'name' => ucfirst(str_replace('_', ' ', $role->value)),
        'role' => $role,
    ]);
}

function rbProject(int $managerId, string $code = 'PRJ-RB'): Project
{
    return Project::create([
        'code' => $code,
        'name' => 'Running Bill Smoke Project',
        'client_name' => 'Test Client',
        'contract_value_paisa' => 1000000000,
        'location_ward' => '12',
        'location_municipality' => 'Lalitpur',
        'location_district' => 'Lalitpur',
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => $managerId,
        'advance_paisa' => 500000000,
        'advance_adjusted_paisa' => 0,
        'status' => ProjectStatus::Active,
    ]);
}

function rbBill(Project $project, int $number = 1, int $advance = 100000000, RunningBillStatus $status = RunningBillStatus::Draft): RunningBill
{
    $totals = app(CalculationService::class)->runningBillTotals(250000000, $advance);

    return RunningBill::create([
        'code' => sprintf('RB-%03d', $number),
        'project_id' => $project->id,
        'bill_number' => $number,
        'period_from' => today()->subMonth()->toDateString(),
        'period_to' => today()->toDateString(),
        'work_certified_paisa' => 250000000,
        'advance_adjusted_paisa' => $advance,
        'net_payable_paisa' => $totals['net_payable_paisa'],
        'status' => $status,
    ]);
}

test('retention fields and displays are removed from projects and running bills', function () {
    $admin = rbUser('admin-rb-retention@smoke.test', Role::Admin);
    $project = rbProject($admin->id, 'PRJ-RB-RETENTION');
    $bill = rbBill($project, 1, 100000000, RunningBillStatus::Certified);

    expect(Schema::hasColumn('projects', 'retention_pct'))->toBeFalse()
        ->and(Schema::hasColumn('running_bills', 'retention_paisa'))->toBeFalse();

    $urls = [
        route('projects.show', $project),
        route('projects.edit', $project),
        route('projects.running-bills', $project),
        route('projects.running-bills.create', $project),
        route('running-bills.show', $bill),
        route('running-bills.print', $bill),
    ];

    foreach ($urls as $url) {
        $this->actingAs($admin)
            ->get($url)
            ->assertOk()
            ->assertDontSee('Retention');
    }
});

test('running bill create screen computes net payable after advance adjustment', function () {
    $admin = rbUser('admin-rb-create@smoke.test', Role::Admin);
    $project = rbProject($admin->id);

    Livewire::actingAs($admin)
        ->test(RunningBillCreate::class, ['project' => $project])
        ->assertSee(Npr::format(0))
        ->set('workCertified', '2500000')
        ->set('advanceAdjusted', '1000000')
        ->assertSee(Npr::format(150000000))
        ->call('save')
        ->assertHasNoErrors();

    $bill = RunningBill::firstOrFail();

    expect($bill->code)->toBe('RB-001')
        ->and($bill->bill_number)->toBe(1)
        ->and($bill->work_certified_paisa)->toBe(250000000)
        ->and($bill->advance_adjusted_paisa)->toBe(100000000)
        ->and($bill->net_payable_paisa)->toBe(150000000)
        ->and($bill->status)->toBe(RunningBillStatus::Draft)
        ->and($bill->certified_date)->toBeNull();

    expect($project->refresh()->advance_adjusted_paisa)->toBe(0);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => 'created',
        'entity_type' => RunningBill::class,
        'entity_id' => $bill->id,
    ]);
});

test('advance validation reserves draft claims and prevents negative net payable', function () {
    $admin = rbUser('admin-rb-advance@smoke.test', Role::Admin);
    $project = rbProject($admin->id, 'PRJ-RB2');

    Livewire::actingAs($admin)
        ->test(RunningBillCreate::class, ['project' => $project])
        ->set('workCertified', '2500000')
        ->set('advanceAdjusted', '1000000')
        ->call('save')
        ->assertHasNoErrors();

    expect(app(CalculationService::class)->runningBillAvailableAdvance($project->refresh()))->toBe(400000000);

    Livewire::actingAs($admin)
        ->test(RunningBillCreate::class, ['project' => $project])
        ->set('workCertified', '2500000')
        ->set('advanceAdjusted', '4500000')
        ->call('save')
        ->assertHasErrors('advanceAdjusted');

    Livewire::actingAs($admin)
        ->test(RunningBillCreate::class, ['project' => $project])
        ->set('workCertified', '2500000')
        ->set('advanceAdjusted', '2500001')
        ->call('save')
        ->assertHasErrors('advanceAdjusted');

    expect(RunningBill::query()->count())->toBe(1);
});

test('certifying a draft sets the date, moves the project advance, and cannot run twice', function () {
    $admin = rbUser('admin-rb-certify@smoke.test', Role::Admin);
    $project = rbProject($admin->id, 'PRJ-RB3');
    $bill = rbBill($project);

    Livewire::actingAs($admin)
        ->test(RunningBillShow::class, ['bill' => $bill])
        ->call('certify')
        ->assertHasNoErrors()
        ->call('certify')
        ->assertHasErrors('bill');

    expect($bill->refresh()->status)->toBe(RunningBillStatus::Certified)
        ->and($bill->certified_date->toDateString())->toBe(today()->toDateString())
        ->and($project->refresh()->advance_adjusted_paisa)->toBe(100000000);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'updated',
        'entity_type' => RunningBill::class,
        'entity_id' => $bill->id,
    ]);
});

test('admin can open running bill screens and the print view shows the formula', function () {
    $admin = rbUser('admin-rb@smoke.test', Role::Admin);
    $project = rbProject($admin->id, 'PRJ-RB4');
    $bill = rbBill($project, 1, 100000000, RunningBillStatus::Certified);
    $net = Npr::format(150000000);

    $this->actingAs($admin)
        ->get(route('projects.running-bills', $project))
        ->assertOk()
        ->assertSee('Running bills')
        ->assertSee('Create bill');

    $this->actingAs($admin)
        ->get(route('projects.running-bills.create', $project))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('running-bills.show', $bill))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('running-bills.print', $bill))
        ->assertOk()
        ->assertSee('RUNNING BILL')
        ->assertSee('Formula')
        ->assertSee($net);
});
