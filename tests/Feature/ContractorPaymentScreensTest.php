<?php

use App\Enums\MilestoneStatus;
use App\Enums\ProjectStatus;
use App\Livewire\Contractors\Form as ContractorForm;
use App\Livewire\Contractors\Show as ContractorShow;
use App\Livewire\Milestones\Index as MilestonesIndex;
use App\Livewire\Payments\Form as PaymentForm;
use App\Livewire\Payments\Index as PaymentIndex;
use App\Livewire\Payments\ProjectSummary as ProjectPayments;
use App\Livewire\Payments\Show as PaymentShow;
use App\Livewire\ProjectJobs\Form as ProjectJobForm;
use App\Livewire\ProjectJobs\Index as ProjectJobIndex;
use App\Models\Contractor;
use App\Models\ContractorPayment;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectJob;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function contractorScreenAdmin(): User
{
    return tenantUser('screen-admin@smoke.test', [
        'name' => 'Screen Admin',
    ]);
}

function contractorScreenProject(int $managerId, string $code = 'PRJ-SCREEN'): Project
{
    return Project::create([
        'code' => $code,
        'name' => 'Screen Project '.$code,
        'client_name' => 'Test Client',
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

function contractorScreenContractor(string $name, string $pan): Contractor
{
    return Contractor::create([
        'name' => $name,
        'phone' => '9841000901',
        'address' => 'Lalitpur',
        'pan' => $pan,
        'is_active' => true,
        'notes' => 'Screen test payee.',
    ]);
}

function contractorScreenJob(Project $project, ?Contractor $contractor, int $agreedPaisa, string $title = 'Bricklaying'): ProjectJob
{
    return ProjectJob::create([
        'project_id' => $project->id,
        'contractor_id' => $contractor?->id,
        'title' => $title,
        'description' => 'Screen test job.',
        'agreed_amount_paisa' => $agreedPaisa,
        'status' => 'todo',
        'starts_on' => today()->subWeek()->toDateString(),
        'due_on' => today()->addWeek()->toDateString(),
    ]);
}

function contractorScreenPayment(
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
        'note' => 'Screen test payment.',
    ]);
}

test('the full contractor payment workflow is reachable from the admin shell', function () {
    $admin = contractorScreenAdmin();
    $project = contractorScreenProject($admin->id);
    $contractor = contractorScreenContractor('Dhan Bahadur Mason', '112233445');
    $job = contractorScreenJob($project, $contractor, 10000000, 'Bricklaying and masonry');
    $payment = contractorScreenPayment($job, $contractor, 'CTP-2026-9001', 2500000, '2026-10-05');

    $this->actingAs($admin)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('Contractor payments')
        ->assertSee('Payment ledger');

    $this->actingAs($admin)->get(route('projects.jobs', $project))
        ->assertRedirect(route('projects.milestones', $project));
    $this->actingAs($admin)->get(route('projects.milestones', $project))
        ->assertOk()->assertSee('Milestones');
    $this->actingAs($admin)->get(route('projects.jobs.create', $project))->assertOk();
    $this->actingAs($admin)->get(route('projects.payments', $project))
        ->assertOk()->assertSee('By contractor');
    $this->actingAs($admin)->get(route('jobs.show', $job))
        ->assertOk()->assertSee('Record payment');
    $this->actingAs($admin)->get(route('jobs.edit', $job))->assertOk();
    $this->actingAs($admin)->get(route('jobs.payments.create', $job))->assertOk();

    $this->actingAs($admin)->get(route('contractors.index'))
        ->assertOk()->assertSee('Dhan Bahadur Mason');
    $this->actingAs($admin)->get(route('contractors.create'))->assertOk();
    $this->actingAs($admin)->get(route('contractors.show', $contractor))
        ->assertOk()->assertSee('Payment history');
    $this->actingAs($admin)->get(route('contractors.edit', $contractor))->assertOk();

    $this->actingAs($admin)->get(route('payments.index'))
        ->assertOk()->assertSee('CTP-2026-9001');
    $this->actingAs($admin)->get(route('payments.show', $payment))
        ->assertOk()->assertSee('Payment record');
    $this->actingAs($admin)->get(route('payments.edit', $payment))->assertOk();
});

test('contractor records can be created and validated', function () {
    $admin = contractorScreenAdmin();
    contractorScreenContractor('Existing Payee', '123456789');

    Livewire::actingAs($admin)
        ->test(ContractorForm::class)
        ->set('name', 'New Mason Contractor')
        ->set('phone', '9841000911')
        ->set('address', 'Bhaktapur')
        ->set('pan', '234567891')
        ->set('is_active', true)
        ->set('notes', 'New screen test payee.')
        ->call('save')
        ->assertHasNoErrors();

    $created = Contractor::query()->where('pan', '234567891')->firstOrFail();

    expect($created->name)->toBe('New Mason Contractor')
        ->and($created->is_active)->toBeTrue();

    Livewire::actingAs($admin)
        ->test(ContractorForm::class)
        ->set('name', 'Bad PAN')
        ->set('pan', 'bad-pan')
        ->call('save')
        ->assertHasErrors('pan');

    Livewire::actingAs($admin)
        ->test(ContractorForm::class)
        ->set('name', 'Duplicate PAN')
        ->set('pan', '123456789')
        ->call('save')
        ->assertHasErrors('pan');
});

test('contractor tasks live under milestones without changing payment balances', function () {
    $admin = contractorScreenAdmin();
    $project = contractorScreenProject($admin->id);
    $contractor = contractorScreenContractor('Sita Paints and Finishes', '334455667');
    $milestone = Milestone::create([
        'project_id' => $project->id,
        'title' => 'Exterior works',
        'target_date' => today()->addMonth()->toDateString(),
        'status' => MilestoneStatus::InProgress,
    ]);
    $nextMilestone = Milestone::create([
        'project_id' => $project->id,
        'title' => 'Finishing works',
        'target_date' => today()->addMonths(2)->toDateString(),
        'status' => MilestoneStatus::Pending,
    ]);
    $job = ProjectJob::create([
        'project_id' => $project->id,
        'milestone_id' => $milestone->id,
        'contractor_id' => $contractor->id,
        'title' => 'Terrace waterproofing',
        'agreed_amount_paisa' => 1000000,
        'status' => 'todo',
    ]);
    $payment = contractorScreenPayment($job, $contractor, 'CTP-2026-9003', 250000, '2026-10-07');

    Livewire::actingAs($admin)
        ->test(MilestonesIndex::class, ['project' => $project])
        ->call('toggle', $milestone->id)
        ->assertSee('Terrace waterproofing');

    Livewire::actingAs($admin)
        ->test(ProjectJobForm::class, ['job' => $job->refresh()])
        ->set('milestoneId', (string) $nextMilestone->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($job->refresh()->milestone_id)->toBe($nextMilestone->id)
        ->and($payment->refresh()->project_job_id)->toBe($job->id)
        ->and($job->balance_paisa)->toBe(750000);
});

test('a milestone cannot be completed until its tasks are done', function () {
    $admin = contractorScreenAdmin();
    $project = contractorScreenProject($admin->id);
    $milestone = Milestone::create([
        'project_id' => $project->id,
        'title' => 'Structure complete',
        'target_date' => today()->addMonth()->toDateString(),
        'status' => MilestoneStatus::InProgress,
    ]);
    $taskA = ProjectJob::create([
        'project_id' => $project->id,
        'milestone_id' => $milestone->id,
        'title' => 'Complete formwork inspection',
        'agreed_amount_paisa' => 0,
        'status' => 'todo',
    ]);
    $taskB = ProjectJob::create([
        'project_id' => $project->id,
        'milestone_id' => $milestone->id,
        'title' => 'Pour the slab',
        'agreed_amount_paisa' => 0,
        'status' => 'todo',
    ]);

    $screen = Livewire::actingAs($admin)->test(MilestonesIndex::class, ['project' => $project]);

    $screen->call('editMilestone', $milestone->id)
        ->set('milestoneStatus', MilestoneStatus::Completed->value)
        ->call('saveMilestone')
        ->assertHasErrors('milestoneStatus');

    expect($milestone->refresh()->status)->toBe(MilestoneStatus::InProgress);

    $taskA->update(['status' => 'done']);
    $screen->call('editMilestone', $milestone->id)
        ->set('milestoneStatus', MilestoneStatus::Completed->value)
        ->call('saveMilestone')
        ->assertHasErrors('milestoneStatus');

    $taskB->update(['status' => 'done']);
    $screen->call('editMilestone', $milestone->id)
        ->set('milestoneStatus', MilestoneStatus::Completed->value)
        ->call('saveMilestone')
        ->assertHasNoErrors();

    expect($milestone->refresh()->status)->toBe(MilestoneStatus::Completed);
});

test('jobs can be created and edited without reducing agreed amount below payments', function () {
    $admin = contractorScreenAdmin();
    $project = contractorScreenProject($admin->id);
    $contractor = contractorScreenContractor('Krishna Plumbing Works', '223344556');
    $milestone = Milestone::create([
        'project_id' => $project->id,
        'title' => 'Services',
        'target_date' => today()->addMonth()->toDateString(),
        'status' => MilestoneStatus::Pending,
    ]);

    Livewire::actingAs($admin)
        ->test(ProjectJobForm::class, ['project' => $project])
        ->set('milestoneId', (string) $milestone->id)
        ->set('title', 'Plumbing and sanitary')
        ->set('description', 'Water supply and drainage.')
        ->set('assigneeType', 'contractor')
        ->set('contractorId', (string) $contractor->id)
        ->set('agreedAmount', '1000.00')
        ->set('status', 'todo')
        ->set('startsOn', today()->subDays(10)->toDateString())
        ->set('dueOn', today()->addDays(20)->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    $job = ProjectJob::query()->firstOrFail();

    expect($job->agreed_amount_paisa)->toBe(100000)
        ->and($job->contractor_id)->toBe($contractor->id);

    contractorScreenPayment($job, $contractor, 'CTP-2026-9002', 60000, '2026-10-06');

    Livewire::actingAs($admin)
        ->test(ProjectJobForm::class, ['job' => $job])
        ->set('agreedAmount', '500.00')
        ->call('save')
        ->assertHasErrors('agreedAmount');

    Livewire::actingAs($admin)
        ->test(ProjectJobForm::class, ['job' => $job])
        ->set('agreedAmount', '800.00')
        ->call('save')
        ->assertHasNoErrors();

    expect($job->refresh()->agreed_amount_paisa)->toBe(80000)
        ->and($job->balance_paisa)->toBe(20000);
});

test('payments can be recorded, checked for overpayment, filtered, corrected and deleted', function () {
    $admin = contractorScreenAdmin();
    $project = contractorScreenProject($admin->id);
    $contractor = contractorScreenContractor('Sita Paints and Finishes', '334455667');
    $job = contractorScreenJob($project, $contractor, 100000, 'Interior painting');

    Livewire::actingAs($admin)
        ->test(PaymentForm::class, ['job' => $job])
        ->set('amount', '250.00')
        ->set('paidOn', '2026-10-05')
        ->set('method', 'bank_transfer')
        ->set('referenceNo', 'NEFT-SCREEN-1')
        ->set('note', 'First progress payment.')
        ->call('save')
        ->assertHasNoErrors();

    $payment = ContractorPayment::query()->firstOrFail();

    expect($payment->payment_no)->toBe('CTP-2026-0001')
        ->and($payment->project_id)->toBe($project->id)
        ->and($payment->project_job_id)->toBe($job->id)
        ->and($payment->contractor_id)->toBe($contractor->id)
        ->and($payment->amount_paisa)->toBe(25000)
        ->and($payment->created_by)->toBe($admin->id);

    Livewire::actingAs($admin)
        ->test(PaymentForm::class, ['job' => $job])
        ->set('amount', '800.00')
        ->set('paidOn', '2026-10-06')
        ->call('save')
        ->assertHasErrors('amount');

    expect(ContractorPayment::query()->count())->toBe(1);

    Livewire::actingAs($admin)
        ->test(PaymentForm::class, ['payment' => $payment])
        ->set('amount', '300.00')
        ->call('save')
        ->assertHasNoErrors();

    expect($payment->refresh()->amount_paisa)->toBe(30000)
        ->and($job->refresh()->balance_paisa)->toBe(70000);

    Livewire::actingAs($admin)
        ->test(PaymentIndex::class)
        ->set('projectId', (string) $project->id)
        ->assertSee($payment->payment_no)
        ->set('from', '2026-11-01')
        ->assertDontSee($payment->payment_no);

    Livewire::actingAs($admin)
        ->test(PaymentShow::class, ['payment' => $payment->refresh()])
        ->call('delete')
        ->assertRedirect(route('payments.index'));

    expect(ContractorPayment::query()->count())->toBe(0)
        ->and($job->refresh()->balance_paisa)->toBe(100000);
});

test('contractor ledger filters do not change the all-time balances', function () {
    $admin = contractorScreenAdmin();
    $projectOne = contractorScreenProject($admin->id, 'PRJ-SCREEN-1');
    $projectTwo = contractorScreenProject($admin->id, 'PRJ-SCREEN-2');
    $contractor = contractorScreenContractor('Bikash Electrical Contractor', '445566778');
    $jobOne = contractorScreenJob($projectOne, $contractor, 100000, 'Electrical wiring');
    $jobTwo = contractorScreenJob($projectTwo, $contractor, 200000, 'Bridge deck works');

    contractorScreenPayment($jobOne, $contractor, 'CTP-2026-9003', 40000, '2026-10-01');
    contractorScreenPayment($jobTwo, $contractor, 'CTP-2026-9004', 50000, '2026-11-01');

    Livewire::actingAs($admin)
        ->test(ContractorShow::class, ['contractor' => $contractor])
        ->assertSee('CTP-2026-9003')
        ->assertSee('CTP-2026-9004')
        ->set('projectId', (string) $projectTwo->id)
        ->assertDontSee('CTP-2026-9003')
        ->assertSee('CTP-2026-9004')
        ->set('from', '2026-12-01')
        ->assertDontSee('CTP-2026-9004');

    Livewire::actingAs($admin)
        ->test(ProjectJobIndex::class, ['project' => $projectOne])
        ->assertRedirect(route('projects.milestones', $projectOne));

    Livewire::actingAs($admin)
        ->test(MilestonesIndex::class, ['project' => $projectOne])
        ->assertOk();

    Livewire::actingAs($admin)
        ->test(ProjectPayments::class, ['project' => $projectOne])
        ->assertSee('By contractor')
        ->assertSee('Bikash Electrical Contractor')
        ->assertSee('CTP-2026-9003')
        ->assertDontSee('CTP-2026-9004');
});
