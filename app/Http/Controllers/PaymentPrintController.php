<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contractor;
use App\Models\ContractorPayment;
use App\Models\Project;
use App\Services\CalculationService;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PaymentPrintController extends Controller
{
    public function payment(ContractorPayment $payment): View
    {
        Gate::authorize('view');

        $calc = app(CalculationService::class);
        $payment->loadMissing(['project', 'job', 'contractor', 'createdBy']);
        $job = $payment->job;

        return view('print.payment-receipt.print', [
            'payment' => $payment,
            'project' => $payment->project,
            'job' => $job,
            'contractor' => $payment->contractor,
            'company' => $this->company(),
            'jobTotals' => $job === null ? null : [
                'agreed_paisa' => (int) $job->agreed_amount_paisa,
                'paid_paisa' => $calc->jobPaidPaisa($job),
                'balance_paisa' => $calc->jobBalancePaisa($job),
            ],
        ]);
    }

    public function contractorLedger(Request $request, Contractor $contractor): View
    {
        Gate::authorize('view');

        $calc = app(CalculationService::class);
        $projectId = $this->idParam($request, 'project');
        $from = $this->stringParam($request, 'from');
        $to = $this->stringParam($request, 'to');

        $ledger = $calc->contractorLedger(
            $contractor,
            $projectId,
            $calc->paymentFilterDate($from),
            $calc->paymentFilterDate($to)
        );

        return view('print.contractor-ledger.print', [
            'ledger' => $ledger,
            'jobCount' => count($ledger['jobs']),
            'project' => $projectId === null ? null : Project::find($projectId),
            'company' => $this->company(),
            'from' => $from,
            'to' => $to,
            'generatedOn' => today(),
        ]);
    }

    public function projectPayments(Project $project): View
    {
        Gate::authorize('view');

        $calc = app(CalculationService::class);
        $summary = $calc->projectPaymentSummary($project);
        $payments = $calc->filteredPayments($project->id);

        return view('print.project-payments.print', [
            'project' => $project,
            'summary' => $summary,
            'payments' => $payments,
            'paymentTotalPaisa' => (int) $payments->sum('amount_paisa'),
            'company' => $this->company(),
            'generatedOn' => today(),
        ]);
    }

    private function company(): Company
    {
        return app(TenantService::class)->currentCompany() ?? new Company([
            'name' => config('app.name'),
            'pan' => '',
            'vat' => '',
            'address' => '',
            'district' => '',
        ]);
    }

    private function idParam(Request $request, string $key): ?int
    {
        $value = $request->query($key);

        if (is_array($value) || $value === null) {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private function stringParam(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
