<?php

namespace App\Livewire\Employees;

use App\Models\Employee;
use App\Services\CalculationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Employee')]
class Show extends Component
{
    public Employee $employee;

    public string $tab = 'profile';

    public function mount(Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function render(CalculationService $calc): View
    {
        $attendanceWindowDays = $calc->employeeAttendanceWindowDays();
        $windowStart = today()->subDays($attendanceWindowDays - 1);

        $attendance = $this->employee->attendances()
            ->whereBetween('date', [$windowStart->toDateString(), today()->toDateString()])
            ->orderByDesc('date')
            ->get();

        $monthly = $this->employee->monthly_basic_paisa;

        return view('livewire.employees.show', [
            'assignments' => $this->employee->projects()->orderByDesc('project_employee.assigned_at')->get(),
            'attendance' => $attendance,
            'attendanceWindowDays' => $attendanceWindowDays,
            'attendanceSummary' => $calc->employeeAttendanceSummary($this->employee, $windowStart, today()),
            'ssf' => $monthly !== null ? $calc->ssfSplit($monthly) : null,
            'festival' => $calc->festivalAllowance($monthly, $this->employee->joined_at),
            'canManage' => auth()->user()->can('manage'),
        ]);
    }
}
