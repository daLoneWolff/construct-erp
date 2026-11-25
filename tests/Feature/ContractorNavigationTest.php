<?php

use App\Enums\CompanyRole;
use App\Enums\ProjectStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Contractor;
use App\Models\ContractorPayment;
use App\Models\Project;
use App\Models\ProjectJob;
use App\Models\User;
use App\Support\Npr;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function contractorNavAdmin(?int $companyId = null): User
{
    return User::create([
        'name' => 'Navigation Admin',
        'email' => 'nav-admin@smoke.test',
        'email_verified_at' => now(),
        'password' => 'password',
        'role' => Role::Admin,
        'current_company_id' => $companyId,
    ]);
}

function contractorNavFixture(): array
{
    $company = Company::create([
        'name' => 'Himalaya Navigation Builders',
        'pan' => '123456789',
        'vat' => '123456789',
        'address' => 'Lalitpur',
        'district' => 'Lalitpur',
        'fiscal_year' => '2083/84',
        'is_demo' => true,
    ]);

    $admin = contractorNavAdmin($company->id);

    $company->forceFill(['owner_user_id' => $admin->id])->save();

    CompanyUser::create([
        'company_id' => $company->id,
        'user_id' => $admin->id,
        'role' => CompanyRole::Owner,
        'joined_at' => now(),
    ]);

    $project = Project::create([
        'company_id' => $company->id,
        'code' => 'PRJ-NAV-1',
        'name' => 'Navigation Tower',
        'client_name' => 'Navigation Client',
        'contract_value_paisa' => 1000000000,
        'location_ward' => '12',
        'location_municipality' => 'Lalitpur',
        'location_district' => 'Lalitpur',
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => $admin->id,
        'advance_paisa' => 5000000,
        'status' => ProjectStatus::Active,
    ]);

    $contractor = Contractor::create([
        'company_id' => $company->id,
        'name' => 'Navigation Mason',
        'phone' => '9841000777',
        'address' => 'Lalitpur',
        'pan' => '112233445',
        'is_active' => true,
    ]);

    $job = ProjectJob::create([
        'company_id' => $company->id,
        'project_id' => $project->id,
        'contractor_id' => $contractor->id,
        'title' => 'Navigation bricklaying',
        'agreed_amount_paisa' => 10000000,
        'status' => 'todo',
    ]);

    ContractorPayment::create([
        'company_id' => $company->id,
        'payment_no' => 'CTP-2026-0001',
        'project_id' => $project->id,
        'project_job_id' => $job->id,
        'contractor_id' => $contractor->id,
        'amount_paisa' => 2500000,
        'paid_on' => '2026-10-05',
        'method' => 'bank_transfer',
        'reference_no' => 'REF-NAV-1',
    ]);

    return compact('company', 'admin', 'project', 'contractor', 'job');
}

test('sidebar leads with overview navigation and keeps support records visible', function () {
    $fixture = contractorNavFixture();

    config()->set('demo.reset_enabled', true);

    $this->actingAs($fixture['admin'])
        ->get('/')
        ->assertOk()
        ->assertSee('Overview')
        ->assertSee('Projects')
        ->assertSee('Payments')
        ->assertSee('Contractors')
        ->assertDontSee('Site delivery')
        ->assertDontSee('Project milestones')
        ->assertDontSee('Site reports (DSR)')
        ->assertDontSee('Support')
        ->assertSee('Attendance')
        ->assertSee('Purchase orders')
        ->assertSee('Reports')
        ->assertDontSee('Demo')
        ->assertSee('Reset demo');
});

test('milestones and DSR remain reachable from the project dashboard', function () {
    $fixture = contractorNavFixture();
    $project = $fixture['project'];
    $admin = $fixture['admin'];

    $this->actingAs($admin)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('Milestones')
        ->assertSee('DSR')
        ->assertSee(route('projects.milestones', $project), false)
        ->assertSee(route('projects.dsr', $project), false);

    $this->actingAs($admin)->get(route('projects.milestones', $project))->assertOk();
    $this->actingAs($admin)->get(route('projects.dsr', $project))->assertOk();
});

test('dashboard focuses on money received and contractor balances', function () {
    $fixture = contractorNavFixture();

    $this->actingAs($fixture['admin'])
        ->get('/')
        ->assertOk()
        ->assertSee('Financial overview')
        ->assertSee('Himalaya Navigation Builders')
        ->assertSee('Client advances received')
        ->assertSee('Recorded project advances')
        ->assertSee(Npr::format(5000000), false)
        ->assertSee('Agreed task value')
        ->assertSee('Still owed to contractors')
        ->assertSee('Navigation Tower')
        ->assertSee('Navigation Mason')
        ->assertSee(Npr::format(10000000), false)
        ->assertSee(Npr::format(2500000), false)
        ->assertSee(Npr::format(7500000), false)
        ->assertDontSee('Paid to contractors')
        ->assertDontSee('Recent payments')
        ->assertDontSee('Support snapshot')
        ->assertDontSee('Manpower on site')
        ->assertDontSee('Shortage lines')
        ->assertDontSee('Open purchase orders');
});

test('attendance procurement warehouse and running bills stay reachable', function () {
    $fixture = contractorNavFixture();

    $urls = [
        route('attendance.index'),
        route('employees.index'),
        route('vendors.index'),
        route('purchase-orders.index'),
        route('ledger.index'),
        route('reports.project-summary'),
        route('projects.running-bills', $fixture['project']),
    ];

    foreach ($urls as $url) {
        $this->actingAs($fixture['admin'])->get($url)->assertOk();
    }
});
