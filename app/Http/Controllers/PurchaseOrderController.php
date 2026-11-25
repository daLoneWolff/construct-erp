<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Services\CalculationService;
use App\Services\TenantService;
use App\Support\NumberToWords;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function print(PurchaseOrder $po): View
    {
        Gate::authorize('view');

        $po->loadMissing(['project', 'vendor']);
        $totals = app(CalculationService::class)->purchaseOrderTotals($po);
        $company = app(TenantService::class)->currentCompany() ?? new Company([
            'name' => config('app.name'),
            'pan' => '',
            'vat' => '',
            'address' => '',
            'district' => '',
        ]);

        return view('print.po.print', [
            'po' => $po,
            'company' => $company,
            'items' => $po->items()->with('boqItem')->get(),
            'totals' => $totals,
            'vatRate' => app(CalculationService::class)->vatRate(),
            'amountInWords' => NumberToWords::rupees($totals['invoice_total_paisa']),
        ]);
    }
}
