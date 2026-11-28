<?php

namespace App\Livewire\Expenses;

use App\Models\Expense;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Expense')]
class Show extends Component
{
    public Expense $expense;

    public function mount(Expense $expense): void
    {
        $this->expense = $expense;
    }

    public function delete(): void
    {
        $this->authorize('manage');

        $this->expense->delete();

        session()->flash('success', __('Expense deleted.'));

        $this->redirect(route('expenses.index'), navigate: true);
    }

    public function render()
    {
        $this->expense->loadMissing(['creator', 'attachments']);

        return view('livewire.expenses.show', [
            'expense' => $this->expense,
        ]);
    }
}
