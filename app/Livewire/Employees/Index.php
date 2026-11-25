<?php

namespace App\Livewire\Employees;

use App\Models\Employee;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Employees')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $trade = '';

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'status', 'trade'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $employees = Employee::query()
            ->when($this->search, fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('employee_code', 'like', "%{$this->search}%")
                ->orWhere('designation', 'like', "%{$this->search}%")))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->trade, fn ($query) => $query->where('trade', $this->trade))
            ->orderBy('employee_code')
            ->paginate(20);

        return view('livewire.employees.index', [
            'employees' => $employees,
            'trades' => Employee::query()->whereNotNull('trade')->distinct()->orderBy('trade')->pluck('trade'),
            'canManage' => auth()->user()->can('manage'),
        ]);
    }
}
