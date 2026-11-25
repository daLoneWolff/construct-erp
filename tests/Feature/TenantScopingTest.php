<?php

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function tenantProject(Company $company, User $manager, string $code, string $name): Project
{
    return Project::create([
        'company_id' => $company->id,
        'code' => $code,
        'name' => $name,
        'client_name' => 'Test Client',
        'contract_value_paisa' => 100000000,
        'location_ward' => '1',
        'location_municipality' => 'Lalitpur',
        'location_district' => 'Lalitpur',
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => $manager->id,
        'advance_paisa' => 0,
        'status' => 'active',
    ]);
}

function otherCompany(): Company
{
    return Company::create([
        'name' => 'Beta Builders Pvt. Ltd.',
        'pan' => '998877665',
        'vat' => '998877665',
        'address' => 'Ward 5, Kathmandu',
        'district' => 'Kathmandu',
        'fiscal_year' => '2083/84',
    ]);
}

test('model queries and route bindings are scoped to the current company', function () {
    $alpha = tenantCompany(['name' => 'Alpha Builders Pvt. Ltd.']);
    $alphaOwner = tenantUser('alpha-owner@tenant.test', [], $alpha);
    $alphaProject = tenantProject($alpha, $alphaOwner, 'PRJ-ALPHA', 'Alpha Tower');

    $beta = otherCompany();
    app(TenantService::class)->setCurrentCompany($beta);
    $betaOwner = tenantUser('beta-owner@tenant.test', [], $beta);
    $betaProject = tenantProject($beta, $betaOwner, 'PRJ-BETA', 'Beta Tower');

    app(TenantService::class)->setCurrentCompany($alpha);

    expect(Project::query()->pluck('id')->all())->toBe([$alphaProject->id])
        ->and(Project::find($betaProject->id))->toBeNull();

    $this->actingAs($alphaOwner)
        ->get(route('projects.index'))
        ->assertOk()
        ->assertSee('Alpha Tower')
        ->assertDontSee('Beta Tower');

    $this->actingAs($alphaOwner)
        ->get(route('projects.show', $alphaProject))
        ->assertOk()
        ->assertSee('Alpha Tower');

    $this->actingAs($alphaOwner)
        ->get(route('projects.show', $betaProject))
        ->assertNotFound();

    $this->actingAs($alphaOwner)
        ->get(route('print.project-payments', $betaProject))
        ->assertNotFound();
});

test('users without a resolved company are sent to onboarding', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('companies.create'));
});

test('members without owner or admin company role are forbidden', function () {
    $company = tenantCompany();
    $member = tenantUser(
        'tenant-member@tenant.test',
        [],
        $company,
        CompanyRole::Member
    );

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('tenant writes are stamped with the company and audited with it', function () {
    $company = tenantCompany();
    $owner = tenantUser('audit-owner@tenant.test', [], $company);

    $this->actingAs($owner);

    $project = tenantProject($company, $owner, 'PRJ-AUDIT', 'Audit Tower');

    expect($project->company_id)->toBe($company->id);

    $this->assertDatabaseHas('audit_logs', [
        'company_id' => $company->id,
        'user_id' => $owner->id,
        'action' => 'created',
        'entity_type' => Project::class,
        'entity_id' => $project->id,
    ]);
});
