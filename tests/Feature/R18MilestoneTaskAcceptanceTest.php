<?php

use App\Enums\CompanyRole;
use App\Enums\JobStatus;
use App\Enums\MilestoneStatus;
use App\Enums\ProjectStatus;
use App\Livewire\Milestones\Index as MilestonesIndex;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Contractor;
use App\Models\ContractorPayment;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectJob;
use App\Models\User;
use App\Services\CalculationService;
use App\Services\TenantService;
use Livewire\Livewire;

/**
 * @return array{company: Company, user: User, project: Project, contractor: Contractor}
 */
function r18Workspace(): array
{
    $company = Company::create([
        'name' => 'R18 Builders',
        'slug' => 'r18-builders',
        'pan' => '123456789',
        'vat' => '123456789',
        'address' => 'Kathmandu',
        'district' => 'Kathmandu',
        'fiscal_year' => '2083/84',
    ]);

    $user = User::factory()->create([
        'name' => 'R18 Owner',
        'email' => 'r18-owner@example.test',
        'email_verified_at' => now(),
    ]);

    $company->forceFill(['owner_user_id' => $user->id])->save();
    CompanyUser::create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'role' => CompanyRole::Owner,
        'joined_at' => now(),
    ]);
    $user->forceFill(['current_company_id' => $company->id])->save();
    app(TenantService::class)->setCurrentCompany($company);

    $project = Project::create([
        'company_id' => $company->id,
        'code' => 'PRJ-R18-001',
        'name' => 'R18 Project',
        'client_name' => 'R18 Client',
        'contract_value_paisa' => 10000000,
        'location_ward' => '1',
        'location_municipality' => 'Kathmandu',
        'location_district' => 'Kathmandu',
        'start_date' => today()->subWeek()->toDateString(),
        'end_date' => today()->addMonth()->toDateString(),
        'project_manager_id' => $user->id,
        'advance_paisa' => 0,
        'status' => ProjectStatus::Active,
    ]);

    $contractor = Contractor::create([
        'company_id' => $company->id,
        'name' => 'R18 Contractor',
        'phone' => '9800000000',
        'address' => 'Kathmandu',
        'pan' => '987654321',
        'is_active' => true,
    ]);

    return compact('company', 'user', 'project', 'contractor');
}

test('r18 milestone task flow enforces lock, reopen, todo-done, overdue, and progress', function () {
    ['company' => $company, 'user' => $user, 'project' => $project, 'contractor' => $contractor] = r18Workspace();

    $milestone = Milestone::create([
        'company_id' => $company->id,
        'project_id' => $project->id,
        'title' => 'Foundation',
        'description' => 'R18 milestone',
        'target_date' => today()->subDay()->toDateString(),
        'status' => MilestoneStatus::Pending,
    ]);

    $taskA = ProjectJob::create([
        'company_id' => $company->id,
        'project_id' => $project->id,
        'milestone_id' => $milestone->id,
        'contractor_id' => $contractor->id,
        'title' => 'Contractor task',
        'agreed_amount_paisa' => 100000,
        'status' => JobStatus::Todo,
    ]);

    $taskB = ProjectJob::create([
        'company_id' => $company->id,
        'project_id' => $project->id,
        'milestone_id' => $milestone->id,
        'title' => 'Employee task',
        'agreed_amount_paisa' => 0,
        'status' => JobStatus::Todo,
    ]);

    expect($milestone->fresh()->isOverdue())->toBeTrue();

    $this->actingAs($user);

    Livewire::test(MilestonesIndex::class, ['project' => $project])
        ->assertSet('progressPct', 0.0)
        ->call('startMilestone', $milestone->id);

    expect($milestone->fresh()->status)->toBe(MilestoneStatus::InProgress);

    Livewire::test(MilestonesIndex::class, ['project' => $project])
        ->call('toggleTaskDone', $taskA->id);

    expect($taskA->fresh()->status)->toBe(JobStatus::Done);

    Livewire::test(MilestonesIndex::class, ['project' => $project])
        ->call('completeMilestone', $milestone->id);

    expect($milestone->fresh()->status)->toBe(MilestoneStatus::InProgress);

    Livewire::test(MilestonesIndex::class, ['project' => $project])
        ->call('toggleTaskDone', $taskB->id)
        ->call('completeMilestone', $milestone->id);

    expect($milestone->fresh()->status)->toBe(MilestoneStatus::Completed);

    Livewire::test(MilestonesIndex::class, ['project' => $project])
        ->call('reopenMilestone', $milestone->id);

    expect($milestone->fresh()->status)->toBe(MilestoneStatus::Pending);
    expect($taskA->fresh()->status)->toBe(JobStatus::Todo);
    expect($taskB->fresh()->status)->toBe(JobStatus::Todo);

    $dashboard = app(CalculationService::class)->projectDashboard($project->fresh());
    expect($dashboard['milestone_pct'])->toBe(0.0);
});

test('r18 reopen is blocked when payments exist and paid tasks keep guards', function () {
    ['company' => $company, 'user' => $user, 'project' => $project, 'contractor' => $contractor] = r18Workspace();

    $milestone = Milestone::create([
        'company_id' => $company->id,
        'project_id' => $project->id,
        'title' => 'Paid milestone',
        'target_date' => today()->addWeek()->toDateString(),
        'status' => MilestoneStatus::InProgress,
    ]);

    $task = ProjectJob::create([
        'company_id' => $company->id,
        'project_id' => $project->id,
        'milestone_id' => $milestone->id,
        'contractor_id' => $contractor->id,
        'title' => 'Payable task',
        'agreed_amount_paisa' => 100000,
        'status' => JobStatus::Done,
    ]);

    ContractorPayment::create([
        'company_id' => $company->id,
        'payment_no' => 'CTP-2026-9001',
        'project_id' => $project->id,
        'project_job_id' => $task->id,
        'contractor_id' => $contractor->id,
        'amount_paisa' => 40000,
        'paid_on' => today()->toDateString(),
        'method' => 'cash',
    ]);

    $this->actingAs($user);

    Livewire::test(MilestonesIndex::class, ['project' => $project])
        ->call('reopenMilestone', $milestone->id);

    expect($milestone->fresh()->status)->toBe(MilestoneStatus::InProgress);
    expect($task->fresh()->status)->toBe(JobStatus::Done);

    $milestone->update(['status' => MilestoneStatus::Pending]);

    Livewire::test(MilestonesIndex::class, ['project' => $project])
        ->call('deleteTask', $task->id);

    expect(ProjectJob::query()->whereKey($task->id)->exists())->toBeTrue();

    Livewire::test(MilestonesIndex::class, ['project' => $project])
        ->call('editTask', $task->id)
        ->set('taskTitle', 'Payable task edited')
        ->set('taskAssigneeType', 'contractor')
        ->set('taskContractorId', (string) $contractor->id)
        ->set('taskAgreedAmountRupees', '300')
        ->call('saveTask')
        ->assertHasErrors(['taskAgreedAmountRupees']);

    expect($task->fresh()->agreed_amount_paisa)->toBe(100000);
    expect($task->fresh()->contractor_id)->toBe($contractor->id);
});
