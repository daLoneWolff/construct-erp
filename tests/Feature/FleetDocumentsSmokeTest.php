<?php

use App\Enums\ProjectStatus;
use App\Enums\Role;
use App\Livewire\Documents\Index as DocumentsIndex;
use App\Livewire\Equipment\Form as EquipmentForm;
use App\Livewire\Equipment\Show as EquipmentShow;
use App\Livewire\Vehicles\Form as VehicleForm;
use App\Livewire\Vehicles\Index as VehiclesIndex;
use App\Livewire\Vehicles\Show as VehicleShow;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\EquipmentUsage;
use App\Models\Project;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function fleetUser(string $email, Role $role): User
{
    return tenantUser($email, [
        'name' => ucfirst(str_replace('_', ' ', $role->value)),
        'role' => $role,
    ]);
}

function fleetProject(int $managerId, string $code = 'PRJ-FLT'): Project
{
    return Project::create([
        'code' => $code,
        'name' => 'Fleet Smoke Project',
        'client_name' => 'Test Client',
        'contract_value_paisa' => 500000000,
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

test('fleet and document screens load with admin write controls', function () {
    $admin = fleetUser('admin-fleet@smoke.test', Role::Admin);
    $project = fleetProject($admin->id);
    $equipment = Equipment::create([
        'code' => 'EQ-901',
        'name' => 'JCB 3DX Backhoe',
        'type' => 'Excavator',
        'ownership' => 'owned',
        'daily_rate_paisa' => 1800000,
        'status' => 'on_site',
        'current_project_id' => $project->id,
    ]);
    $vehicle = Vehicle::create([
        'registration_no' => 'BA 12 PA 3456',
        'type' => 'Tipper',
        'make_model' => 'Tata 2518 Tipper',
        'bluebook_expiry' => today()->addDays(200)->toDateString(),
        'insurance_expiry' => today()->addDays(150)->toDateString(),
        'pollution_expiry' => today()->addDays(40)->toDateString(),
        'status' => 'on_site',
        'current_project_id' => $project->id,
    ]);

    $this->actingAs($admin)
        ->get(route('equipment.index'))
        ->assertOk()
        ->assertSee('JCB 3DX Backhoe')
        ->assertSee('Add equipment');

    $this->actingAs($admin)
        ->get(route('equipment.show', $equipment))
        ->assertOk()
        ->assertSee('JCB 3DX Backhoe')
        ->assertSee('Update assignment');

    $this->actingAs($admin)->get(route('vehicles.index'))->assertOk()->assertSee('BA 12 PA 3456');
    $this->actingAs($admin)->get(route('vehicles.show', $vehicle))->assertOk()->assertSee('Document expiry');
    $this->actingAs($admin)->get(route('documents.index'))->assertOk()->assertSee('Documents')->assertSee('Upload document');
    $this->actingAs($admin)->get(route('equipment.create'))->assertOk();
    $this->actingAs($admin)->get(route('equipment.edit', $equipment))->assertOk();
    $this->actingAs($admin)->get(route('vehicles.create'))->assertOk();
    $this->actingAs($admin)->get(route('vehicles.edit', $vehicle))->assertOk();
});

test('equipment can be created, edited, assigned, and reviewed through its usage log', function () {
    $admin = fleetUser('admin-equip@smoke.test', Role::Admin);
    $project = fleetProject($admin->id, 'PRJ-EQP');

    Livewire::actingAs($admin)
        ->test(EquipmentForm::class)
        ->assertSet('code', 'EQ-001')
        ->set('name', 'JCB 3DX Backhoe')
        ->set('type', 'Excavator')
        ->set('ownership', 'owned')
        ->set('hourly_rate', '18000')
        ->set('daily_rate', '180000')
        ->set('status', 'available')
        ->call('save')
        ->assertHasNoErrors();

    $equipment = Equipment::firstOrFail();

    expect($equipment->code)->toBe('EQ-001')
        ->and($equipment->hourly_rate_paisa)->toBe(1800000)
        ->and($equipment->daily_rate_paisa)->toBe(18000000)
        ->and($equipment->current_project_id)->toBeNull();

    Livewire::actingAs($admin)
        ->test(EquipmentForm::class, ['equipment' => $equipment])
        ->set('daily_rate', '185000')
        ->call('save')
        ->assertHasNoErrors();

    expect($equipment->refresh()->daily_rate_paisa)->toBe(18500000);

    EquipmentUsage::create([
        'equipment_id' => $equipment->id,
        'project_id' => $project->id,
        'usage_date' => today()->toDateString(),
        'hours' => 8.00,
        'operator_name' => 'Tek Bahadur Pun',
    ]);

    Livewire::actingAs($admin)
        ->test(EquipmentShow::class, ['equipment' => $equipment])
        ->assertSee('Usage log')
        ->assertSee('Tek Bahadur Pun')
        ->set('assignProjectId', (string) $project->id)
        ->call('assignProject')
        ->assertHasNoErrors();

    expect($equipment->refresh()->current_project_id)->toBe($project->id)
        ->and($equipment->status->value)->toBe('on_site');
});

test('vehicle expiry badge uses the worst document and detail view shows each document', function () {
    $admin = fleetUser('admin-vehicle@smoke.test', Role::Admin);
    $project = fleetProject($admin->id, 'PRJ-VEH');

    Livewire::actingAs($admin)
        ->test(VehicleForm::class)
        ->set('registration_no', 'BA 16 CHA 2345')
        ->set('type', 'Concrete Mixer Truck')
        ->set('make_model', 'Ashok Leyland 2518')
        ->set('bluebook_expiry', today()->subDays(20)->toDateString())
        ->set('insurance_expiry', today()->addDays(60)->toDateString())
        ->set('pollution_expiry', today()->addDays(28)->toDateString())
        ->set('route_permit_expiry', today()->addDays(31)->toDateString())
        ->set('status', 'on_site')
        ->set('currentProjectId', (string) $project->id)
        ->call('save')
        ->assertHasNoErrors();

    Vehicle::create([
        'registration_no' => 'BA 18 PA 6789',
        'type' => 'Water Tanker',
        'make_model' => 'Tata 2518 Tanker',
        'bluebook_expiry' => today()->addDays(250)->toDateString(),
        'insurance_expiry' => today()->addDays(10)->toDateString(),
        'pollution_expiry' => today()->addDays(90)->toDateString(),
        'status' => 'available',
    ]);

    Vehicle::create([
        'registration_no' => 'BA 20 KHA 1122',
        'type' => 'Bus (staff)',
        'make_model' => 'Toyota Coaster',
        'bluebook_expiry' => today()->addDays(80)->toDateString(),
        'insurance_expiry' => today()->addDays(25)->toDateString(),
        'pollution_expiry' => today()->addDays(180)->toDateString(),
        'status' => 'available',
    ]);

    $calc = app(CalculationService::class);
    $expired = Vehicle::where('registration_no', 'BA 16 CHA 2345')->firstOrFail();
    $status = $calc->vehicleStatus($expired);

    expect($status['badge'])->toBe('red')
        ->and($status['documents']['bluebook']['days'])->toBe(-20)
        ->and($status['documents']['bluebook']['badge'])->toBe('red')
        ->and($status['documents']['route_permit']['badge'])->toBe('green');

    Livewire::actingAs($admin)
        ->test(VehiclesIndex::class)
        ->assertSee('Expired')
        ->assertSee('Expiring soon')
        ->assertSee('Valid')
        ->assertSee('BA 16 CHA 2345');

    Livewire::actingAs($admin)
        ->test(VehicleShow::class, ['vehicle' => $expired])
        ->assertSee('Document expiry')
        ->assertSee('Bluebook')
        ->assertSee('Route permit')
        ->assertSee('20 days overdue');
});

test('mock document upload stores browser metadata and a deterministic mock path only', function () {
    $admin = fleetUser('admin-docs@smoke.test', Role::Admin);
    $project = fleetProject($admin->id, 'PRJ-DOC');

    Livewire::actingAs($admin)
        ->test(DocumentsIndex::class)
        ->set('title', 'Approved structural drawings')
        ->set('uploadCategory', 'drawing')
        ->set('uploadProjectId', (string) $project->id)
        ->set('uploads', [
            [
                'name' => 'structural.pdf',
                'size' => 2048,
                'mime' => 'application/pdf',
            ],
        ])
        ->call('saveUpload')
        ->assertHasNoErrors()
        ->assertSet('showUpload', false)
        ->assertSee('Approved structural drawings');

    $document = Document::firstOrFail();

    expect($document->filename)->toBe('structural.pdf')
        ->and($document->size_bytes)->toBe(2048)
        ->and($document->mime)->toBe('application/pdf')
        ->and($document->path_mock)->toMatch('#^/mock/[0-9a-f-]{36}$#')
        ->and($document->uploaded_by)->toBe($admin->id)
        ->and($document->project_id)->toBe($project->id);

    expect($document->path_mock)->toBe('/mock/'.sprintf(
        '0000000%d-0000-4000-8000-%012d',
        $document->id % 10,
        $document->id
    ));

    Livewire::actingAs($admin)
        ->test(DocumentsIndex::class)
        ->call('openUpload')
        ->assertSet('showUpload', true);
});
