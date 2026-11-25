<?php

namespace App\Livewire\Milestones;

use App\Enums\JobStatus;
use App\Enums\MilestoneStatus;
use App\Models\Contractor;
use App\Models\ContractorPayment;
use App\Models\Employee;
use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Milestones/Tasks')]
class Index extends Component
{
    public Project $project;

    public ?int $expandedId = null;

    public bool $showMilestoneForm = false;

    public ?int $milestoneId = null;

    public string $title = '';

    public string $description = '';

    public string $targetDate = '';

    public string $milestoneStatus = 'pending';

    public bool $showTaskForm = false;

    public ?int $taskId = null;

    public ?int $taskMilestoneId = null;

    public string $taskTitle = '';

    public string $taskDescription = '';

    public string $taskAssigneeType = 'none'; // none|employee|contractor

    public string $taskAssigneeEmployeeId = '';

    public string $taskContractorId = '';

    public string $taskAgreedAmountRupees = '0';

    public string $taskDueDate = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function toggle(int $milestoneId): void
    {
        $this->expandedId = $this->expandedId === $milestoneId ? null : $milestoneId;
    }

    public function createMilestone(): void
    {
        $this->authorize('manage');
        $this->resetValidation();
        $this->milestoneId = null;
        $this->title = '';
        $this->description = '';
        $this->targetDate = today()->addMonth()->toDateString();
        $this->milestoneStatus = MilestoneStatus::Pending->value;
        $this->showMilestoneForm = true;
    }

    public function editMilestone(int $milestoneId): void
    {
        $this->authorize('manage');
        $milestone = $this->project->milestones()->findOrFail($milestoneId);
        $this->resetValidation();
        $this->milestoneId = $milestone->id;
        $this->title = $milestone->title;
        $this->description = $milestone->description ?? '';
        $this->targetDate = $milestone->target_date->toDateString();
        $this->milestoneStatus = $milestone->status->value;
        $this->showMilestoneForm = true;
    }

    public function saveMilestone(): void
    {
        $this->authorize('manage');

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'targetDate' => ['required', 'date'],
            'milestoneStatus' => ['required', Rule::enum(MilestoneStatus::class)],
        ]);

        $milestone = $this->milestoneId
            ? $this->project->milestones()->findOrFail($this->milestoneId)
            : null;

        if ($milestone && $this->milestoneStatus === MilestoneStatus::Completed->value) {
            $hasIncomplete = $milestone->tasks()->where('status', '!=', JobStatus::Done->value)->exists();
            if ($hasIncomplete) {
                $this->addError('milestoneStatus', __('Complete all tasks before marking this milestone completed.'));

                return;
            }
        }

        $attributes = [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'target_date' => $this->targetDate,
            'status' => $this->milestoneStatus,
        ];

        if ($milestone) {
            $milestone->update($attributes);
            session()->flash('success', __('Milestone updated.'));
        } else {
            $this->project->milestones()->create($attributes);
            session()->flash('success', __('Milestone created.'));
        }

        $this->showMilestoneForm = false;
    }

    public function deleteMilestone(int $milestoneId): void
    {
        $this->authorize('manage');
        $milestone = $this->project->milestones()->withCount('tasks')->findOrFail($milestoneId);

        if ($milestone->tasks_count > 0) {
            session()->flash('error', __('Delete or move tasks before removing this milestone.'));

            return;
        }

        $milestone->delete();
        session()->flash('success', __('Milestone deleted.'));
    }

    public function startMilestone(int $milestoneId): void
    {
        $this->authorize('manage');
        $milestone = $this->project->milestones()->findOrFail($milestoneId);
        if ($milestone->status !== MilestoneStatus::Pending) {
            return;
        }
        $milestone->update(['status' => MilestoneStatus::InProgress]);
        session()->flash('success', __('Milestone started.'));
    }

    public function completeMilestone(int $milestoneId): void
    {
        $this->authorize('manage');
        $milestone = $this->project->milestones()->findOrFail($milestoneId);
        if ($milestone->tasks()->where('status', '!=', JobStatus::Done->value)->exists()) {
            session()->flash('error', __('Complete all tasks before marking this milestone completed.'));

            return;
        }
        $milestone->update(['status' => MilestoneStatus::Completed]);
        session()->flash('success', __('Milestone completed.'));
    }

    public function reopenMilestone(int $milestoneId): void
    {
        $this->authorize('manage');
        $milestone = $this->project->milestones()->findOrFail($milestoneId);

        $hasPayments = ContractorPayment::query()
            ->whereIn('project_job_id', $milestone->tasks()->select('id'))
            ->exists();

        if ($hasPayments) {
            session()->flash('error', __('Cannot reopen this milestone because contractor payments already exist on its tasks.'));

            return;
        }

        $milestone->tasks()->each(fn ($task) => $task->update(['status' => JobStatus::Todo->value]));
        $milestone->update(['status' => MilestoneStatus::Pending]);
        session()->flash('success', __('Milestone reopened to pending. Task checkboxes were reset.'));
    }

    public function createTask(int $milestoneId): void
    {
        $this->authorize('write');
        $milestone = $this->project->milestones()->findOrFail($milestoneId);
        $this->ensureMilestoneEditable($milestone);

        $this->resetValidation();
        $this->taskId = null;
        $this->taskMilestoneId = $milestoneId;
        $this->taskTitle = '';
        $this->taskDescription = '';
        $this->taskAssigneeType = 'none';
        $this->taskAssigneeEmployeeId = '';
        $this->taskContractorId = '';
        $this->taskAgreedAmountRupees = '0';
        $this->taskDueDate = '';
        $this->showTaskForm = true;
        $this->expandedId = $milestoneId;
    }

    public function editTask(int $taskId): void
    {
        $this->authorize('write');
        $task = $this->project->jobs()->findOrFail($taskId);
        $milestone = $this->project->milestones()->findOrFail($task->milestone_id);
        $this->ensureMilestoneEditable($milestone);

        $this->resetValidation();
        $this->taskId = $task->id;
        $this->taskMilestoneId = $task->milestone_id;
        $this->taskTitle = $task->title;
        $this->taskDescription = $task->description ?? '';
        $this->taskAssigneeType = $task->contractor_id ? 'contractor' : ($task->assignee_employee_id ? 'employee' : 'none');
        $this->taskAssigneeEmployeeId = $task->assignee_employee_id ? (string) $task->assignee_employee_id : '';
        $this->taskContractorId = $task->contractor_id ? (string) $task->contractor_id : '';
        $this->taskAgreedAmountRupees = (string) ((int) $task->agreed_amount_paisa / 100);
        $this->taskDueDate = $task->due_on?->toDateString() ?? '';
        $this->showTaskForm = true;
    }

    public function saveTask(): void
    {
        $this->authorize('write');

        $this->validate([
            'taskMilestoneId' => ['required', 'integer'],
            'taskTitle' => ['required', 'string', 'max:255'],
            'taskDescription' => ['nullable', 'string', 'max:2000'],
            'taskAssigneeType' => ['required', Rule::in(['none', 'employee', 'contractor'])],
            'taskAssigneeEmployeeId' => ['nullable', 'integer', 'exists:employees,id'],
            'taskContractorId' => ['nullable', 'integer', 'exists:contractors,id'],
            'taskAgreedAmountRupees' => ['nullable', 'numeric', 'min:0'],
            'taskDueDate' => ['nullable', 'date'],
        ]);

        $milestone = $this->project->milestones()->findOrFail($this->taskMilestoneId);
        $this->ensureMilestoneEditable($milestone);

        $contractorId = $this->taskAssigneeType === 'contractor' && $this->taskContractorId !== '' ? (int) $this->taskContractorId : null;
        $assigneeEmployeeId = $this->taskAssigneeType === 'employee' && $this->taskAssigneeEmployeeId !== '' ? (int) $this->taskAssigneeEmployeeId : null;
        $agreedPaisa = (int) round(((float) ($this->taskAgreedAmountRupees ?: 0)) * 100);

        if ($this->taskAssigneeType === 'contractor') {
            if (! $contractorId) {
                $this->addError('taskContractorId', __('Select a contractor.'));

                return;
            }
            if ($agreedPaisa <= 0) {
                $this->addError('taskAgreedAmountRupees', __('Contractor tasks must have an agreed amount greater than zero.'));

                return;
            }
        } else {
            $agreedPaisa = 0;
        }

        $attributes = [
            'milestone_id' => $milestone->id,
            'project_id' => $this->project->id,
            'title' => $this->taskTitle,
            'description' => $this->taskDescription ?: null,
            'contractor_id' => $contractorId,
            'assignee_employee_id' => $assigneeEmployeeId,
            'agreed_amount_paisa' => $agreedPaisa,
            'due_on' => $this->taskDueDate ?: null,
        ];

        if ($this->taskId) {
            $task = $this->project->jobs()->findOrFail($this->taskId);
            $paid = (int) $task->payments()->sum('amount_paisa');
            if ($paid > 0 && ($task->contractor_id !== $contractorId || $agreedPaisa < $paid)) {
                $this->addError('taskAgreedAmountRupees', __('Paid contractor tasks cannot change contractor or reduce amount below paid total.'));

                return;
            }
            $task->update($attributes);
            session()->flash('success', __('Task updated.'));
        } else {
            $attributes['company_id'] = $this->project->company_id;
            $attributes['status'] = JobStatus::Todo->value;
            $this->project->jobs()->create($attributes);
            session()->flash('success', __('Task added.'));
        }

        $this->showTaskForm = false;
    }

    public function toggleTaskDone(int $taskId): void
    {
        $this->authorize('write');
        $task = $this->project->jobs()->findOrFail($taskId);
        $milestone = $this->project->milestones()->findOrFail($task->milestone_id);
        if ($milestone->status !== MilestoneStatus::InProgress) {
            session()->flash('error', __('Tasks can only be checked in an in-progress milestone.'));

            return;
        }
        $task->update(['status' => $task->status === JobStatus::Done ? JobStatus::Todo : JobStatus::Done]);
    }

    public function deleteTask(int $taskId): void
    {
        $this->authorize('manage');
        $task = $this->project->jobs()->findOrFail($taskId);
        $milestone = $this->project->milestones()->findOrFail($task->milestone_id);
        $this->ensureMilestoneEditable($milestone);

        if ($task->payments()->exists()) {
            session()->flash('error', __('Tasks with contractor payments cannot be deleted.'));

            return;
        }

        $task->delete();
        session()->flash('success', __('Task deleted.'));
    }

    private function ensureMilestoneEditable(Milestone $milestone): void
    {
        if ($milestone->status !== MilestoneStatus::Pending) {
            abort(403, 'Milestone tasks are locked unless the milestone is pending.');
        }
    }

    public function render()
    {
        $milestones = $this->project->milestones()
            ->with(['tasks.contractor', 'tasks.assignee', 'tasks.payments'])
            ->orderBy('target_date')
            ->get();

        $allTasks = $milestones->flatMap->tasks;
        $doneTasks = $allTasks->where('status', JobStatus::Done)->count();
        $totalTasks = $allTasks->count();

        return view('livewire.milestones.index', [
            'milestones' => $milestones,
            'columns' => [
                MilestoneStatus::Pending,
                MilestoneStatus::InProgress,
                MilestoneStatus::Completed,
            ],
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'designation']),
            'contractors' => Contractor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'totalTasks' => $totalTasks,
            'doneTasks' => $doneTasks,
            'progressPct' => $totalTasks > 0 ? round($doneTasks / $totalTasks * 100, 2) : 0,
        ]);
    }
}
