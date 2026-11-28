<?php

use App\Enums\CompanyRole;
use App\Enums\JobStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Livewire\Contractors\Form as ContractorForm;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Contractor;
use App\Models\ContractorPayment;
use App\Models\Project;
use App\Models\ProjectJob;
use App\Models\User;
use App\Services\CalculationService;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

function contractorPortalUser(Company $company, Contractor $contractor, string $email = 'portal.contractor@test.com'): User
{
    $user = User::create([
        'name' => $contractor->name,
        'email' => $email,
        'email_verified_at' => now(),
        'password' => 'password',
        'role' => Role::Contractor,
        'current_company_id' => $company->id,
    ]);

    CompanyUser::create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'role' => CompanyRole::Contractor,
        'joined_at' => now(),
    ]);

    $contractor->forceFill(['user_id' => $user->id])->save();

    return $user;
}

test('admin can create contractor portal login from contractor form', function () {
    $admin = tenantUser('admin-portal-create@test.com');
    $company = $admin->currentCompany;

    $contractor = Contractor::create([
        'name' => 'Ramesh Contractor',
        'email' => 'ramesh@contractor.test',
        'phone' => '9800000001',
    ]);

    Livewire::actingAs($admin)
        ->test(ContractorForm::class, ['contractor' => $contractor])
        ->set('name', 'Ramesh Contractor')
        ->set('email', 'ramesh@contractor.test')
        ->set('enable_portal_access', true)
        ->set('portal_password', 'password123')
        ->set('portal_password_confirmation', 'password123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('contractors.show', $contractor));

    $contractor->refresh();
    expect($contractor->user_id)->not->toBeNull();

    $user = User::find($contractor->user_id);
    expect($user)->not->toBeNull();
    expect($user->role)->toBe(Role::Contractor);
    expect($user->email)->toBe('ramesh@contractor.test');
    expect($user->email_verified_at)->not->toBeNull();
    expect(Hash::check('password123', $user->password))->toBeTrue();

    $membership = CompanyUser::query()
        ->where('company_id', $company->id)
        ->where('user_id', $user->id)
        ->first();

    expect($membership)->not->toBeNull();
    expect($membership->role)->toBe(CompanyRole::Contractor);
});

test('contractor portal login redirects to portal dashboard', function () {
    $company = tenantCompany();
    $contractor = Contractor::create(['name' => 'Portal Contractor', 'email' => 'pc@test.com']);
    contractorPortalUser($company, $contractor, 'pc@test.com');

    $response = $this->post(route('login.store'), [
        'email' => 'pc@test.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('portal.dashboard', absolute: false));
    $this->assertAuthenticated();
});

test('contractor portal shows only own payments and assigned jobs', function () {
    $admin = tenantUser('admin-portal-scope@test.com');
    $company = $admin->currentCompany;

    $project = Project::create([
        'code' => 'PRJ-001',
        'name' => 'Bridge Project',
        'status' => 'active',
        'client_name' => 'Client A',
        'contract_value_paisa' => 10000000,
        'location_ward' => '12',
        'location_municipality' => 'Lalitpur',
        'location_district' => 'Lalitpur',
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => $admin->id,
        'advance_paisa' => 0,
    ]);

    $contractorA = Contractor::create(['name' => 'Contractor A', 'email' => 'a@test.com']);
    $contractorB = Contractor::create(['name' => 'Contractor B', 'email' => 'b@test.com']);

    $jobA = ProjectJob::create([
        'project_id' => $project->id,
        'contractor_id' => $contractorA->id,
        'title' => 'Job A',
        'agreed_amount_paisa' => 500000,
        'status' => JobStatus::Todo,
    ]);
    $jobB = ProjectJob::create([
        'project_id' => $project->id,
        'contractor_id' => $contractorB->id,
        'title' => 'Job B',
        'agreed_amount_paisa' => 300000,
        'status' => JobStatus::Todo,
    ]);

    ContractorPayment::create([
        'project_id' => $project->id,
        'project_job_id' => $jobA->id,
        'contractor_id' => $contractorA->id,
        'payment_no' => 'CTP-2026-0001',
        'amount_paisa' => 200000,
        'paid_on' => today()->toDateString(),
        'method' => PaymentMethod::Cash,
        'created_by' => $admin->id,
    ]);
    ContractorPayment::create([
        'project_id' => $project->id,
        'project_job_id' => $jobB->id,
        'contractor_id' => $contractorB->id,
        'payment_no' => 'CTP-2026-0002',
        'amount_paisa' => 100000,
        'paid_on' => today()->toDateString(),
        'method' => PaymentMethod::Cash,
        'created_by' => $admin->id,
    ]);

    $portalUser = contractorPortalUser($company, $contractorA, 'contractor-a@test.com');

    $this->actingAs($portalUser)
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertSee('Contractor A')
        ->assertSee('Job A')
        ->assertDontSee('Job B')
        ->assertDontSee('CTP-2026-0002');

    $this->actingAs($portalUser)
        ->get(route('portal.payments'))
        ->assertOk()
        ->assertSee('CTP-2026-0001')
        ->assertDontSee('CTP-2026-0002');

    $this->actingAs($portalUser)
        ->get(route('portal.jobs'))
        ->assertOk()
        ->assertSee('Job A')
        ->assertDontSee('Job B');

    $calc = app(CalculationService::class);
    $summary = $calc->contractorPortalSummary($contractorA);
    expect($summary['agreed_paisa'])->toBe(500000);
    expect($summary['paid_paisa'])->toBe(200000);
    expect($summary['balance_paisa'])->toBe(300000);
});

test('contractor portal user cannot access admin routes', function () {
    $company = tenantCompany();
    $contractor = Contractor::create(['name' => 'Blocked Contractor', 'email' => 'blocked@test.com']);
    $portalUser = contractorPortalUser($company, $contractor, 'blocked@test.com');

    $this->actingAs($portalUser)->get(route('contractors.index'))->assertForbidden();
    $this->actingAs($portalUser)->get(route('payments.index'))->assertForbidden();
    $this->actingAs($portalUser)->get(route('projects.index'))->assertForbidden();
});

test('admin cannot access contractor portal routes', function () {
    $admin = tenantUser('admin-no-portal@test.com');

    $this->actingAs($admin)->get(route('portal.dashboard'))->assertForbidden();
    $this->actingAs($admin)->get(route('portal.payments'))->assertForbidden();
    $this->actingAs($admin)->get(route('portal.jobs'))->assertForbidden();
});

test('contractor cannot see another company contractor data', function () {
    $companyA = tenantCompany();
    $contractorA = Contractor::create(['name' => 'Company A Contractor', 'email' => 'ca@test.com']);
    $portalUser = contractorPortalUser($companyA, $contractorA, 'ca@test.com');

    $companyB = Company::create([
        'name' => 'Other Company',
        'pan' => '609999991',
        'vat' => '609999991',
        'address' => 'Kathmandu',
        'district' => 'Kathmandu',
        'fiscal_year' => '2083/84',
    ]);
    $contractorB = Contractor::withoutGlobalScopes()->create([
        'company_id' => $companyB->id,
        'name' => 'Company B Contractor',
        'email' => 'cb@test.com',
    ]);

    // Portal user is scoped to company A; company B contractor should never appear.
    $this->actingAs($portalUser)
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertSee('Company A Contractor')
        ->assertDontSee('Company B Contractor');
});

test('admin cannot assign portal email belonging to non-contractor user', function () {
    $admin = tenantUser('admin-conflict@test.com');

    User::create([
        'name' => 'Existing Admin',
        'email' => 'existing@test.com',
        'email_verified_at' => now(),
        'password' => 'password',
        'role' => Role::Admin,
    ]);

    $contractor = Contractor::create(['name' => 'Conflict Contractor', 'email' => 'existing@test.com']);

    Livewire::actingAs($admin)
        ->test(ContractorForm::class, ['contractor' => $contractor])
        ->set('name', 'Conflict Contractor')
        ->set('email', 'existing@test.com')
        ->set('enable_portal_access', true)
        ->set('portal_password', 'password123')
        ->set('portal_password_confirmation', 'password123')
        ->call('save')
        ->assertHasErrors(['email']);

    $contractor->refresh();
    expect($contractor->user_id)->toBeNull();
});
