<?php

namespace App\Livewire\Contractors;

use App\Services\CalculationService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Contractors')]
class Index extends Component
{
    public string $search = '';

    public function render(CalculationService $calc)
    {
        return view('livewire.contractors.index', [
            'rows' => $calc->contractorSummaries($this->search),
        ]);
    }
}
