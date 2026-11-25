<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\CalculationService;
use App\Services\TenantService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportController extends Controller
{
    /** @var array<int, string> */
    private const REPORTS = [
        'project-summary',
        'reconciliation',
        'po-status',
        'attendance-summary',
        'milestone-progress',
    ];

    public function print(string $report): View
    {
        Gate::authorize('view');

        if (! in_array($report, self::REPORTS, true)) {
            abort(404);
        }

        $calc = app(CalculationService::class);
        $projectParam = request()->query('project_id');
        $projectId = is_numeric($projectParam) ? (int) $projectParam : null;
        $fromParam = request()->query('from');
        $toParam = request()->query('to');
        [$from, $to] = $calc->reportDateRange(
            is_string($fromParam) ? $fromParam : null,
            is_string($toParam) ? $toParam : null
        );

        $data = match ($report) {
            'project-summary' => $calc->projectSummaryReport($projectId),
            'reconciliation' => $calc->reconciliationReport($projectId),
            'po-status' => $calc->poStatusReport($projectId, $from, $to),
            'attendance-summary' => $calc->attendanceReport($projectId, $from, $to),
            'milestone-progress' => $calc->milestoneProgressReport($projectId),
        };

        $company = app(TenantService::class)->currentCompany() ?? new Company([
            'name' => config('app.name'),
            'pan' => '',
            'vat' => '',
            'address' => '',
            'district' => '',
        ]);

        return view("print.{$report}.print", array_merge($data, [
            'company' => $company,
            'from' => $from,
            'to' => $to,
        ]));
    }
}
