<?php

namespace App\Livewire\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Attendance')]
class Index extends Component
{
    public string $date = '';

    public ?int $projectId = null;

    /** @var array<int, string> */
    public array $statuses = [];

    /** @var array<int, string> */
    public array $overtime = [];

    public function mount(): void
    {
        $this->date = today()->toDateString();
        $this->projectId = Project::query()->where('status', 'active')->orderBy('code')->value('id')
            ?? Project::query()->orderBy('code')->value('id');

        $this->loadGrid();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['date', 'projectId'], true)) {
            $this->loadGrid();
        }
    }

    public function loadGrid(): void
    {
        $this->statuses = [];
        $this->overtime = [];

        $existing = Attendance::query()
            ->whereDate('date', $this->date)
            ->whereIn('employee_id', $this->employees()->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        foreach ($this->employees() as $employee) {
            $row = $existing->get($employee->id);
            $this->statuses[$employee->id] = $row?->status->value ?? AttendanceStatus::Present->value;
            $this->overtime[$employee->id] = (string) ($row?->overtime_hours ?? 0);
        }
    }

    public function save(): void
    {
        $this->authorize('write');

        $this->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'projectId' => ['required', 'exists:projects,id'],
            'statuses' => ['required', 'array'],
            'statuses.*' => ['required', Rule::enum(AttendanceStatus::class)],
            'overtime' => ['array'],
            'overtime.*' => ['nullable', 'numeric', 'min:0', 'max:24'],
        ]);

        $employeeIds = $this->employees()->pluck('id');

        DB::transaction(function () use ($employeeIds) {
            foreach ($employeeIds as $employeeId) {
                Attendance::query()->updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'date' => $this->date,
                    ],
                    [
                        'project_id' => $this->projectId,
                        'status' => $this->statuses[$employeeId],
                        'overtime_hours' => (float) ($this->overtime[$employeeId] ?? 0),
                    ],
                );
            }
        });

        session()->flash('success', __('Attendance saved for :date (:count employees).', [
            'date' => \Illuminate\Support\Carbon::parse($this->date)->format('d/m/Y'),
            'count' => $employeeIds->count(),
        ]));
    }

    private function employees()
    {
        if ($this->projectId === null) {
            return collect();
        }

        $assigned = Project::query()->find($this->projectId)?->employees()
            ->orderBy('employees.name')
            ->get();

        return $assigned && $assigned->isNotEmpty()
            ? $assigned
            : Employee::query()->where('status', 'active')->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.attendance.index', [
            'employees' => $this->employees(),
            'projects' => Project::query()->orderBy('code')->get(['id', 'code', 'name', 'status']),
            'statusOptions' => AttendanceStatus::cases(),
            'canEdit' => auth()->user()->can('write'),
        ]);
    }
}
