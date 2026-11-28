<?php

use App\Enums\ProjectStatus;
use App\Livewire\Projects\Create as ProjectCreate;
use App\Livewire\Projects\Edit as ProjectEdit;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectPartner;
use App\Models\User;
use App\Support\Npr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function projectPartnerPayload(User $manager, array $overrides = []): array
{
    return array_merge([
        'code' => 'PRJ-JV-001',
        'name' => 'JV Test Project',
        'client_name' => 'Test Client',
        'contract_value_npr' => '1000000',
        'location_ward' => '1',
        'location_municipality' => 'Kathmandu',
        'location_district' => 'Kathmandu',
        'start_date' => today()->subDay()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => (string) $manager->id,
        'advance_npr' => '0',
        'status' => ProjectStatus::Active->value,
        'partners' => [],
    ], $overrides);
}

test('project can be created with JV partners', function () {
    $admin = tenantUser('jv-create@test.com');

    Livewire::actingAs($admin)
        ->test(ProjectCreate::class)
        ->set(projectPartnerPayload($admin, [
            'partners' => [
                ['name' => 'ABC Construction', 'share_percent' => '70'],
                ['name' => 'XYZ Infra', 'share_percent' => '30'],
            ],
        ]))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $project = Project::query()->where('code', 'PRJ-JV-001')->first();
    expect($project)->not->toBeNull();
    expect($project->partners)->toHaveCount(2);
    expect($project->partners->pluck('name')->all())->toBe(['ABC Construction', 'XYZ Infra']);
    expect((float) $project->partners->first()->share_percent)->toBe(70.0);
});

test('project JV partners must total 100 percent', function () {
    $admin = tenantUser('jv-total@test.com');

    Livewire::actingAs($admin)
        ->test(ProjectCreate::class)
        ->set(projectPartnerPayload($admin, [
            'partners' => [
                ['name' => 'ABC Construction', 'share_percent' => '70'],
                ['name' => 'XYZ Infra', 'share_percent' => '20'],
            ],
        ]))
        ->call('save')
        ->assertHasErrors(['partners']);

    expect(Project::query()->where('code', 'PRJ-JV-001')->exists())->toBeFalse();
});

test('project JV partner names must be unique within project', function () {
    $admin = tenantUser('jv-dup@test.com');

    Livewire::actingAs($admin)
        ->test(ProjectCreate::class)
        ->set(projectPartnerPayload($admin, [
            'partners' => [
                ['name' => 'ABC Construction', 'share_percent' => '60'],
                ['name' => 'ABC Construction', 'share_percent' => '40'],
            ],
        ]))
        ->call('save')
        ->assertHasErrors(['partners.1.name']);

    expect(Project::query()->where('code', 'PRJ-JV-001')->exists())->toBeFalse();
});

test('project JV partners can be updated and removed', function () {
    $admin = tenantUser('jv-update@test.com');

    $project = Project::create([
        'code' => 'PRJ-JV-002',
        'name' => 'JV Update Project',
        'client_name' => 'Client',
        'contract_value_paisa' => 100000000,
        'location_ward' => '1',
        'location_municipality' => 'Kathmandu',
        'location_district' => 'Kathmandu',
        'start_date' => today()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => $admin->id,
        'advance_paisa' => 0,
        'status' => ProjectStatus::Active,
    ]);
    $project->partners()->create(['name' => 'Old Partner', 'share_percent' => '100.00']);

    Livewire::actingAs($admin)
        ->test(ProjectEdit::class, ['project' => $project])
        ->set('partners', [
            ['name' => 'ABC Construction', 'share_percent' => '70'],
            ['name' => 'XYZ Infra', 'share_percent' => '30'],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $project->refresh();
    expect($project->partners()->pluck('name')->all())->toBe(['ABC Construction', 'XYZ Infra']);

    Livewire::actingAs($admin)
        ->test(ProjectEdit::class, ['project' => $project])
        ->set('partners', [])
        ->call('save')
        ->assertHasNoErrors();

    expect($project->partners()->count())->toBe(0);
});

test('project show displays JV partner allocation', function () {
    $admin = tenantUser('jv-show@test.com');

    $project = Project::create([
        'code' => 'PRJ-JV-003',
        'name' => 'JV Show Project',
        'client_name' => 'Client',
        'contract_value_paisa' => 10000000,
        'location_ward' => '1',
        'location_municipality' => 'Kathmandu',
        'location_district' => 'Kathmandu',
        'start_date' => today()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => $admin->id,
        'advance_paisa' => 0,
        'status' => ProjectStatus::Active,
    ]);
    $project->partners()->createMany([
        ['name' => 'ABC Construction', 'share_percent' => '70.00'],
        ['name' => 'XYZ Infra', 'share_percent' => '30.00'],
    ]);

    $this->actingAs($admin)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('JV Partners')
        ->assertSee('ABC Construction')
        ->assertSee('XYZ Infra')
        ->assertSee(Npr::format(7000000), false)
        ->assertSee(Npr::format(3000000), false);
});

test('project partner changes are audited and tenant scoped', function () {
    $admin = tenantUser('jv-audit@test.com');

    Livewire::actingAs($admin)
        ->test(ProjectCreate::class)
        ->set(projectPartnerPayload($admin, [
            'code' => 'PRJ-JV-004',
            'partners' => [
                ['name' => 'ABC Construction', 'share_percent' => '100'],
            ],
        ]))
        ->call('save')
        ->assertHasNoErrors();

    $partner = ProjectPartner::query()->where('name', 'ABC Construction')->first();
    expect($partner)->not->toBeNull();
    expect(AuditLog::query()
        ->where('entity_type', ProjectPartner::class)
        ->where('entity_id', $partner->id)
        ->where('action', 'created')
        ->exists())->toBeTrue();

    $projectId = $partner->project_id;

    $otherCompany = Company::create([
        'name' => 'Other Company',
        'pan' => '609999998',
        'vat' => '609999998',
        'address' => 'Kathmandu',
        'district' => 'Kathmandu',
        'fiscal_year' => '2083/84',
    ]);
    $other = tenantUser('jv-other@test.com', company: $otherCompany);
    $this->actingAs($other)
        ->get(route('projects.show', $projectId))
        ->assertNotFound();
});
