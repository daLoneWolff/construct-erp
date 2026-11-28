<?php

namespace App\Livewire\Portal;

use App\Models\Contractor;
use App\Services\CalculationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app.contractor')]
#[Title('My Payments')]
class Payments extends Component
{
    public function render(CalculationService $calc): View
    {
        $contractor = $this->contractor();

        return view('livewire.portal.payments', [
            'contractor' => $contractor,
            'summary' => $contractor ? $calc->contractorPortalSummary($contractor) : null,
            'payments' => $contractor ? $calc->contractorPortalPayments($contractor, 200) : collect(),
        ]);
    }

    private function contractor(): ?Contractor
    {
        $user = auth()->user();

        if (! $user || ! $user->isContractor()) {
            return null;
        }

        return Contractor::query()->where('user_id', $user->id)->first();
    }
}
