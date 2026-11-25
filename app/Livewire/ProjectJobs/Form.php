<?php

namespace App\Livewire\ProjectJobs;

use App\Enums\JobStatus;
use App\Models\Contractor;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectJob;
use App\Services\CalculationService;
use App\Support\Npr;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Task')]
class Form extends Component
{
    public ?Project $project = null;

    public ?ProjectJob $job = null;

    #[Url(as: 'milestone')]
    public string $milestoneId = '';

    public string $title = '';

    public string $description = '';

    public string $assigneeType = 'none'; // none|employee|contractor

    public string $assigneeEmployeeId = '';

    public string $contractorId = '';

    public string $agreedAmount = '0';

    public string $status = 'todo';

    public string $startsOn = '';

    public string $dueOn = '';

    public function mount(?Project $project = null, ?ProjectJob $job = null): void
    {
        $this->authorize('manage');

        if ($job !== null) {
            $this->job = $job;
            $this->project = $job->project;
            $this->milestoneId = $job->milestone_id === null ? '' : (string) $job->milestone_id;
            $this->title = $job->title;
            $this->description = $job->description ?? '';
            $this->assigneeType = $job->contractor_id ? 'contractor' : ($job->assignee_employee_id ? 'employee' : 'none');
            $this->assigneeEmployeeId = $job->assignee_employee_id === null ? '' : (string) $job->assignee_employee_id;
            $this->contractorId = $job->contractor_id === null ? '' : (string) $job->contractor_id;
            $this->agreedAmount = Npr::toInput((int) $job->agreed_amount_paisa);
            $this->status = $job->status->value;
            $this->startsOn = $job->starts_on?->toDateString() ?? '';
            $this->dueOn = $job->due_on?->toDateString() ?? '';

            return;
        }

        if ($project === null) {
            abort(404);
        }

        $this->project = $project;
        $this->status = JobStatus::Todo->value;
    }

    public function save(): void
    {
        $this->authorize('manage');

        if ($this->project === null) {
            abort(404);
        }

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'milestoneId' => ['required', 'integer', 'exists:milestones,id'],
            'assigneeType' => ['required', Rule::in(['none', 'employee', 'contractor'])],
            'assigneeEmployeeId' => ['nullable', 'integer', 'exists:employees,id'],
            'contractorId' => ['nullable', 'integer', 'exists:contractors,id'],
            'agreedAmount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(JobStatus::class)],
            'startsOn' => ['nullable', 'date'],
            'dueOn' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value !== null && $value !== '' && $this->startsOn !== '' && $value < $this->startsOn) {
                        $fail(__('Due date cannot be before the start date.'));
                    }
                },
            ],
        ]);

        $milestone = $this->project->milestones()->find((int) $this->milestoneId);
        if ($milestone === null) {
            $this->addError('milestoneId', __('Select a milestone from this project.'));

            return;
        }

        $contractorId = $this->assigneeType === 'contractor' && $this->contractorId !== '' ? (int) $this->contractorId : null;
        $assigneeEmployeeId = $this->assigneeType === 'employee' && $this->assigneeEmployeeId !== '' ? (int) $this->assigneeEmployeeId : null;
        $agreedPaisa = Npr::toPaisa($this->agreedAmount === '' ? '0' : $this->agreedAmount);

        if ($this->assigneeType === 'contractor') {
            if (! $contractorId) {
                $this->addError('contractorId', __('Select a contractor.'));

                return;
            }
            if ($agreedPaisa <= 0) {
                $this->addError('agreedAmount', __('Contractor tasks must have an agreed amount greater than zero.'));

                return;
            }
        } else {
            $agreedPaisa = 0;
        }

        $calc = app(CalculationService::class);
        if ($this->job !== null) {
            $paidPaisa = $calc->jobPaidPaisa($this->job);
            if ($paidPaisa > 0 && ($this->job->contractor_id !== $contractorId || $agreedPaisa < $paidPaisa)) {
                $this->addError('agreedAmount', __('Paid contractor tasks cannot change contractor or reduce amount below paid total.'));

                return;
            }
        }

        $attributes = [
            'project_id' => $this->project->id,
            'milestone_id' => $milestone->id,
            'contractor_id' => $contractorId,
            'assignee_employee_id' => $assigneeEmployeeId,
            'title' => $this->title,
            'description' => $this->description ?: null,
            'agreed_amount_paisa' => $agreedPaisa,
            'status' => $this->status,
            'starts_on' => $this->startsOn !== '' ? $this->startsOn : null,
            'due_on' => $this->dueOn !== '' ? $this->dueOn : null,
        ];

        if ($this->job !== null) {
            $this->job->update($attributes);
            $job = $this->job;
            session()->flash('success', __('Task updated.'));
        } else {
            $attributes['company_id'] = $this->project->company_id;
            $job = ProjectJob::create($attributes);
            session()->flash('success', __('Task created.'));
        }

        $this->redirect(route('jobs.show', $job), navigate: true);
    }

    public function render()
    {
        return view('livewire.project-jobs.form', [
            'isEdit' => $this->job !== null,
            'contractors' => Contractor::query()->orderBy('name')->get(),
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'designation']),
            'milestones' => $this->project?->milestones()->orderBy('target_date')->get() ?? collect(),
            'statuses' => JobStatus::cases(),
        ]);
    }
}
