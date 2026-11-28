<?php

namespace App\Livewire\Portal;

use App\Models\Contractor;
use App\Services\CalculationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app.contractor')]
#[Title('My Dashboard')]
class Dashboard extends Component
{
    public function render(CalculationService $calc): View
    {
        $contractor = $this->contractor();

        return view('livewire.portal.dashboard', [
            'contractor' => $contractor,
            'summary' => $contractor ? $calc->contractorPortalSummary($contractor) : null,
            'recentPayments' => $contractor ? $calc->contractorPortalPayments($contractor, 5) : collect(),
            'jobGroups' => $contractor ? $calc->contractorPortalJobsByProject($contractor) : collect(),
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
