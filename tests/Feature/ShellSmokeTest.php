<?php

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

function makeAdminUser(?int $employeeId = null, ?Company $company = null): User
{
    return tenantUser('admin@smoke.test', [
        'name' => 'Admin',
        'employee_id' => $employeeId,
    ], $company);
}

test('guests are redirected to the login screen', function () {
    $this->get('/')->assertRedirect('/login');
    $this->get('/login')->assertOk();
});

test('the admin can open the dashboard', function () {
    $this->actingAs(makeAdminUser())
        ->get('/')
        ->assertOk()
        ->assertSee('Financial overview')
        ->assertSee('Admin');
});

test('the dashboard shows the seeded company and its projects', function () {
    $company = tenantCompany([
        'name' => 'Himalaya Builders Pvt. Ltd.',
        'pan' => '601234567',
        'vat' => '601234567',
        'address' => 'Ward 12, Lalitpur',
        'district' => 'Lalitpur',
        'fiscal_year' => '2083/84',
        'is_demo' => true,
    ]);

    $admin = makeAdminUser(null, $company);

    config()->set('demo.reset_enabled', true);

    $project = Project::create([
        'company_id' => $company->id,
        'code' => 'PRJ-SMOKE',
        'name' => 'Smoke Test Tower',
        'client_name' => 'Test Client',
        'contract_value_paisa' => 100000000,
        'location_ward' => '1',
        'location_municipality' => 'Lalitpur',
        'location_district' => 'Lalitpur',
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => $admin->id,
        'advance_paisa' => 0,
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->get('/')
        ->assertOk()
        ->assertSee('Himalaya Builders')
        ->assertSee('Smoke Test Tower')
        ->assertDontSee('Demo')
        ->assertSee('Reset demo');

    $this->actingAs($admin)->get(route('projects.index'))->assertOk()->assertSee('PRJ-SMOKE');
    $this->actingAs($admin)->get(route('projects.show', $project))->assertOk()->assertSee('Smoke Test Tower');
    $this->actingAs($admin)->get(route('projects.create'))->assertOk();
    $this->actingAs($admin)->get(route('projects.edit', $project))->assertOk();
});

test('the demo role switcher is gone', function () {
    expect(Route::has('demo.switch-role'))->toBeFalse();

    $this->actingAs(makeAdminUser())
        ->post('/demo/switch-role', ['role' => 'admin'])
        ->assertNotFound();
});
