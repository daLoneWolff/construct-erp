<?php

namespace App\Livewire\Employees;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Models\Employee;
use App\Rules\Pan;
use App\Support\Npr;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Employee')]
class Form extends Component
{
    public ?Employee $employee = null;

    public string $employee_code = '';

    public string $name = '';

    public string $designation = '';

    public string $trade = '';

    public string $department = '';

    public string $employment_type = 'daily';

    public string $daily_wage = '';

    public string $monthly_basic = '';

    public string $pan = '';

    public bool $ssf_enrolled = false;

    public string $ssf_no = '';

    public string $joined_at = '';

    public string $phone = '';

    public string $email = '';

    public string $status = 'active';

    public function mount(?Employee $employee = null): void
    {
        $this->authorize('manage');

        $this->employee = $employee;
        $this->joined_at = today()->toDateString();

        if ($employee) {
            $this->employee_code = $employee->employee_code;
            $this->name = $employee->name;
            $this->designation = $employee->designation;
            $this->trade = $employee->trade ?? '';
            $this->department = $employee->department;
            $this->employment_type = $employee->employment_type->value;
            $this->daily_wage = $employee->daily_wage_paisa !== null ? Npr::toInput($employee->daily_wage_paisa) : '';
            $this->monthly_basic = $employee->monthly_basic_paisa !== null ? Npr::toInput($employee->monthly_basic_paisa) : '';
            $this->pan = $employee->pan ?? '';
            $this->ssf_enrolled = $employee->ssf_enrolled;
            $this->ssf_no = $employee->ssf_no ?? '';
            $this->joined_at = $employee->joined_at->toDateString();
            $this->phone = $employee->phone ?? '';
            $this->email = $employee->email ?? '';
            $this->status = $employee->status->value;
        }
    }

    public function save(): void
    {
        $this->authorize('manage');

        $this->validate([
            'employee_code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('employees', 'employee_code')->ignore($this->employee?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'trade' => ['nullable', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'daily_wage' => ['nullable', 'numeric', 'min:0'],
            'monthly_basic' => ['nullable', 'numeric', 'min:0'],
            'pan' => ['nullable', 'string', new Pan],
            'ssf_enrolled' => ['boolean'],
            'ssf_no' => ['nullable', 'string', 'max:50'],
            'joined_at' => ['required', 'date'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
        ]);

        $attributes = [
            'employee_code' => $this->employee_code,
            'name' => $this->name,
            'designation' => $this->designation,
            'trade' => $this->trade ?: null,
            'department' => $this->department,
            'employment_type' => $this->employment_type,
            'daily_wage_paisa' => $this->daily_wage !== '' ? Npr::toPaisa($this->daily_wage) : null,
            'monthly_basic_paisa' => $this->monthly_basic !== '' ? Npr::toPaisa($this->monthly_basic) : null,
            'pan' => $this->pan ?: null,
            'ssf_enrolled' => $this->ssf_enrolled,
            'ssf_no' => $this->ssf_no ?: null,
            'joined_at' => $this->joined_at,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'status' => $this->status,
        ];

        if ($this->employee) {
            $this->employee->update($attributes);
            $employee = $this->employee;
            session()->flash('success', __('Employee updated.'));
        } else {
            $employee = Employee::create($attributes);
            session()->flash('success', __('Employee created.'));
        }

        $this->redirect(route('employees.show', $employee), navigate: true);
    }

    public function render()
    {
        return view('livewire.employees.form', [
            'employmentTypes' => EmploymentType::cases(),
            'isEdit' => $this->employee !== null,
        ]);
    }
}
