<?php

use App\Enums\AttendanceStatus;
use App\Enums\EmploymentType;
use App\Enums\ProjectStatus;
use App\Livewire\Attendance\Index as AttendanceIndex;
use App\Livewire\Employees\Show as EmployeeShow;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function peopleAdmin(): User
{
    return tenantUser('people-admin@smoke.test', [
        'name' => 'People Admin',
    ]);
}

function peopleProject(int $managerId): Project
{
    return Project::create([
        'code' => 'PRJ-PEOPLE',
        'name' => 'People Smoke Project',
        'client_name' => 'Test Client',
        'contract_value_paisa' => 500000000,
        'location_ward' => '1',
        'location_municipality' => 'Lalitpur',
        'location_district' => 'Lalitpur',
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => $managerId,
        'advance_paisa' => 0,
        'status' => ProjectStatus::Active,
    ]);
}

function peopleEmployee(): Employee
{
    return Employee::create([
        'employee_code' => 'EMP-SMOKE',
        'name' => 'Hari Thapa',
        'designation' => 'Mason',
        'trade' => 'Mason',
        'department' => 'Construction',
        'employment_type' => EmploymentType::Monthly,
        'monthly_basic_paisa' => 3000000,
        'pan' => '601111199',
        'ssf_enrolled' => true,
        'ssf_no' => 'SSF-SMOKE',
        'joined_at' => today()->subMonths(6)->toDateString(),
        'status' => 'active',
    ]);
}

test('employee profile tabs render SSF and attendance data', function () {
    $admin = peopleAdmin();
    $project = peopleProject($admin->id);
    $employee = peopleEmployee();

    $project->employees()->attach($employee->id, [
        'role_on_project' => 'Mason',
        'assigned_at' => today()->subMonth()->toDateString(),
    ]);

    Attendance::create([
        'employee_id' => $employee->id,
        'project_id' => $project->id,
        'date' => today()->toDateString(),
        'status' => AttendanceStatus::Present,
        'overtime_hours' => 2,
    ]);

    $this->actingAs($admin)
        ->get(route('employees.index'))
        ->assertOk()
        ->assertSee('EMP-SMOKE');

    $this->actingAs($admin)
        ->get(route('employees.show', $employee))
        ->assertOk()
        ->assertSee('Hari Thapa');

    Livewire::actingAs($admin)
        ->test(EmployeeShow::class, ['employee' => $employee])
        ->set('tab', 'ssf')
        ->assertSee('Employee contribution (11%)')
        ->assertSee('Employer contribution (20%)')
        ->assertSee('Total SSF deposit (31%)')
        ->set('tab', 'attendance')
        ->assertSee('Overtime (h)')
        ->set('tab', 'assignments')
        ->assertSee('PRJ-PEOPLE');
});

test('employee attendance empty state uses the service lookback window', function () {
    $admin = peopleAdmin();
    $employee = peopleEmployee();

    Livewire::actingAs($admin)
        ->test(EmployeeShow::class, ['employee' => $employee])
        ->set('tab', 'attendance')
        ->assertSee('No attendance in the last 30 days');
});

test('attendance grid saves one row per employee and date', function () {
    $admin = peopleAdmin();
    $project = peopleProject($admin->id);
    $employee = peopleEmployee();

    $project->employees()->attach($employee->id, [
        'role_on_project' => 'Mason',
        'assigned_at' => today()->subMonth()->toDateString(),
    ]);

    $this->actingAs($admin)->get(route('attendance.index'))->assertOk()->assertSee('Hari Thapa');

    Livewire::actingAs($admin)
        ->test(AttendanceIndex::class)
        ->assertSet('date', today()->toDateString())
        ->assertSet('projectId', $project->id)
        ->set("statuses.{$employee->id}", AttendanceStatus::Absent->value)
        ->set("overtime.{$employee->id}", '3.5')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('attendances', [
        'employee_id' => $employee->id,
        'project_id' => $project->id,
        'status' => AttendanceStatus::Absent->value,
    ]);

    $saved = Attendance::query()
        ->where('employee_id', $employee->id)
        ->whereDate('date', today())
        ->firstOrFail();

    expect($saved->overtime_hours)->toEqual(3.5);
});

test('leave request functionality and data are removed', function () {
    $admin = peopleAdmin();
    $employee = peopleEmployee();

    $this->actingAs($admin)->get('/leaves')->assertNotFound();
    $this->actingAs($admin)->get(route('employees.index'))
        ->assertOk()
        ->assertDontSee('Leave requests');
    $this->actingAs($admin)->get(route('employees.show', $employee))
        ->assertOk()
        ->assertDontSee('Leave history')
        ->assertDontSee('No leave requests');

    $this->assertFalse(Schema::hasTable('leave_requests'));
});

test('admin can save attendance without a leave request workflow', function () {
    $admin = peopleAdmin();
    $project = peopleProject($admin->id);
    $employee = peopleEmployee();
    $project->employees()->attach($employee->id, [
        'role_on_project' => 'Mason',
        'assigned_at' => today()->subMonth()->toDateString(),
    ]);

    Livewire::actingAs($admin)
        ->test(AttendanceIndex::class)
        ->set('projectId', $project->id)
        ->set("statuses.{$employee->id}", AttendanceStatus::Present->value)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('attendances', [
        'employee_id' => $employee->id,
        'project_id' => $project->id,
        'status' => AttendanceStatus::Present->value,
    ]);

});
