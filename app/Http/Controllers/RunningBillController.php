<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\RunningBill;
use App\Services\CalculationService;
use App\Services\TenantService;
use App\Support\NumberToWords;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RunningBillController extends Controller
{
    public function print(RunningBill $bill): View
    {
        Gate::authorize('view');

        $bill->loadMissing('project');
        $project = $bill->project;
        $totals = app(CalculationService::class)->runningBillTotals(
            $bill->work_certified_paisa,
            $bill->advance_adjusted_paisa
        );
        $company = app(TenantService::class)->currentCompany() ?? new Company([
            'name' => config('app.name'),
            'pan' => '',
            'vat' => '',
            'address' => '',
            'district' => '',
        ]);

        return view('print.running-bill.print', [
            'bill' => $bill,
            'project' => $project,
            'company' => $company,
            'totals' => $totals,
            'amountInWords' => NumberToWords::rupees($totals['net_payable_paisa']),
        ]);
    }
}
