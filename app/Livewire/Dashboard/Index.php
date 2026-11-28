<?php

namespace App\Livewire\Dashboard;

use App\Models\Project;
use App\Services\CalculationService;
use App\Services\TenantService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Index extends Component
{
    private const DASHBOARD_LIMIT = 5;

    public function mount(): void
    {
        if (auth()->user()?->isContractor()) {
            $this->redirect(route('portal.dashboard'), navigate: true);
        }
    }

    public function render(CalculationService $calc): View
    {
        $company = app(TenantService::class)->currentCompany();
        $companyId = $company?->id;
        $emptySummary = $calc->emptyPaymentSummary();

        $projects = $companyId !== null
            ? Project::query()->where('company_id', $companyId)->orderBy('code')->get()
            : collect();

        $paymentTotals = $calc->projectPaymentTotals($projects->pluck('id')->all(), $companyId);

        $projectRows = $projects
            ->map(function (Project $project) use ($paymentTotals): array {
                $summary = $paymentTotals->get($project->id, [
                    'agreed_paisa' => 0,
                    'paid_paisa' => 0,
                    'balance_paisa' => 0,
                ]);

                return [
                    'project' => $project,
                    'agreed_paisa' => $summary['agreed_paisa'],
                    'paid_paisa' => $summary['paid_paisa'],
                    'balance_paisa' => $summary['balance_paisa'],
                ];
            })
            ->sortByDesc('balance_paisa')
            ->take(self::DASHBOARD_LIMIT)
            ->values();

        $contractorRows = $companyId !== null
            ? $calc->contractorSummaries(null, $companyId)
                ->sortByDesc('balance_paisa')
                ->take(self::DASHBOARD_LIMIT)
                ->values()
            : collect();

        $paymentSummary = $companyId !== null
            ? $calc->overallPaymentSummary($companyId)
            : $emptySummary;

        $isEmptyWorkspace = $companyId !== null
            && $projectRows->isEmpty()
            && $contractorRows->isEmpty()
            && $paymentSummary['job_count'] === 0;

        return view('livewire.dashboard.index', [
            'companyRecord' => $company,
            'isEmptyWorkspace' => $isEmptyWorkspace,
            'clientMoneyReceivedPaisa' => $companyId !== null
                ? $calc->clientMoneyReceivedPaisa($companyId)
                : $emptySummary['agreed_paisa'],
            'paymentSummary' => $paymentSummary,
            'projectRows' => $projectRows,
            'contractorRows' => $contractorRows,
        ]);
    }
}
