<?php

namespace App\Livewire\Expenses;

use App\Enums\ExpenseCategory;
use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Expenses')]
class Index extends Component
{
    use WithPagination;

    public string $category = '';

    public string $from = '';

    public string $to = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['category', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function render(CalculationService $calc)
    {
        $category = $this->category === '' ? null : $this->category;

        return view('livewire.expenses.index', [
            'expenses' => $calc->filteredExpenses($category, $this->from, $this->to),
            'summary' => $calc->expenseFilterSummary($category, $this->from, $this->to),
            'categories' => ExpenseCategory::cases(),
        ]);
    }
}
