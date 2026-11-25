<?php

namespace App\Http\Controllers;

use App\Services\CalculationService;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentExportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('view');

        $calc = app(CalculationService::class);
        $company = app(TenantService::class)->currentCompany();
        $projectId = $this->idParam($request, 'project');
        $contractorId = $this->idParam($request, 'contractor');
        $from = $this->stringParam($request, 'from');
        $to = $this->stringParam($request, 'to');

        $headers = $calc->paymentExportHeaders();
        $rows = $calc->paymentExportRows(
            $projectId,
            $contractorId,
            $calc->paymentFilterDate($from),
            $calc->paymentFilterDate($to),
            $company
        );

        $filename = 'contractor-payments-'.today()->toDateString().'.csv';

        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            try {
                fputcsv($handle, $headers);

                foreach ($rows as $row) {
                    fputcsv($handle, array_values($row));
                }
            } finally {
                fclose($handle);
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
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
