<?php

namespace App\Support;

use App\Services\TenantService;
use Illuminate\Support\Facades\DB;

class DocumentNumber
{
    public static function requisition(): string
    {
        return self::next('material_requisitions', 'MR-');
    }

    public static function purchaseOrder(): string
    {
        $company = app(TenantService::class)->currentCompany();
        $fiscalYear = $company?->fiscal_year;
        $year = is_string($fiscalYear) && str_contains($fiscalYear, '/')
            ? explode('/', $fiscalYear)[0]
            : (string) now()->year;

        return self::next('purchase_orders', "PO-{$year}-");
    }

    public static function receipt(): string
    {
        return self::next('material_receipts', 'MRN-');
    }

    public static function issue(): string
    {
        return self::next('material_issues', 'IS-');
    }

    public static function equipment(): string
    {
        return self::next('equipment', 'EQ-');
    }

    public static function runningBill(): string
    {
        return self::next('running_bills', 'RB-');
    }

    private static function next(string $table, string $prefix, int $pad = 3): string
    {
        $companyId = app(TenantService::class)->currentCompanyId();
        $query = DB::table($table);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $max = 0;

        foreach ($query->pluck('code') as $code) {
            if (preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', (string) $code, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $prefix.str_pad((string) ($max + 1), $pad, '0', STR_PAD_LEFT);
    }
}
