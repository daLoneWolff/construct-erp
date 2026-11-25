<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const TENANT_TABLES = [
        'employees',
        'projects',
        'project_employee',
        'boq_headers',
        'boq_clauses',
        'boq_items',
        'milestones',
        'tasks',
        'equipment',
        'equipment_usages',
        'vehicles',
        'daily_site_reports',
        'dsr_manpower',
        'dsr_equipment',
        'dsr_materials',
        'dsr_photos',
        'attendances',
        'vendors',
        'material_requisitions',
        'material_requisition_items',
        'purchase_orders',
        'purchase_order_items',
        'warehouses',
        'material_receipts',
        'material_receipt_items',
        'material_issues',
        'material_issue_items',
        'stock_ledger',
        'documents',
        'running_bills',
        'audit_logs',
        'contractors',
        'project_jobs',
        'contractor_payments',
    ];

    /**
     * Old global uniqueness is replaced by company-scoped uniqueness. Null
     * company_id rows remain valid in T1 so the current demo/tests keep working
     * until scoping is enforced in T3/T5.
     *
     * @var list<array{table: string, from: list<string>, to: list<string>}>
     */
    private const UNIQUE_REPLACEMENTS = [
        ['table' => 'employees', 'from' => ['employee_code'], 'to' => ['company_id', 'employee_code']],
        ['table' => 'projects', 'from' => ['code'], 'to' => ['company_id', 'code']],
        ['table' => 'project_employee', 'from' => ['project_id', 'employee_id'], 'to' => ['company_id', 'project_id', 'employee_id']],
        ['table' => 'equipment', 'from' => ['code'], 'to' => ['company_id', 'code']],
        ['table' => 'vehicles', 'from' => ['registration_no'], 'to' => ['company_id', 'registration_no']],
        ['table' => 'daily_site_reports', 'from' => ['project_id', 'report_date'], 'to' => ['company_id', 'project_id', 'report_date']],
        ['table' => 'attendances', 'from' => ['employee_id', 'date'], 'to' => ['company_id', 'employee_id', 'date']],
        ['table' => 'material_requisitions', 'from' => ['code'], 'to' => ['company_id', 'code']],
        ['table' => 'purchase_orders', 'from' => ['code'], 'to' => ['company_id', 'code']],
        ['table' => 'material_receipts', 'from' => ['code'], 'to' => ['company_id', 'code']],
        ['table' => 'material_issues', 'from' => ['code'], 'to' => ['company_id', 'code']],
        ['table' => 'running_bills', 'from' => ['code'], 'to' => ['company_id', 'code']],
        ['table' => 'running_bills', 'from' => ['project_id', 'bill_number'], 'to' => ['company_id', 'project_id', 'bill_number']],
        ['table' => 'contractor_payments', 'from' => ['payment_no'], 'to' => ['company_id', 'payment_no']],
    ];

    /** @var list<array{table: string, columns: list<string>}> */
    private const COMPOSITE_INDEXES = [
        ['table' => 'projects', 'columns' => ['company_id', 'status']],
        ['table' => 'contractors', 'columns' => ['company_id', 'is_active']],
        ['table' => 'contractor_payments', 'columns' => ['company_id', 'project_id', 'paid_on']],
        ['table' => 'contractor_payments', 'columns' => ['company_id', 'contractor_id', 'paid_on']],
        ['table' => 'running_bills', 'columns' => ['company_id', 'project_id']],
        ['table' => 'audit_logs', 'columns' => ['company_id', 'created_at']],
    ];

    /**
     * SQLite does not run the PostgreSQL object-renaming pass in the legacy
     * contractor migration, so its old payment index still uses the old name.
     */
    private const SQLITE_LEGACY_UNIQUE_NAMES = [
        'contractor_payments' => [
            'payment_no' => 'thekedar_payments_payment_no_unique',
        ],
    ];

    public function up(): void
    {
        foreach (self::TENANT_TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('companies')
                    ->cascadeOnDelete();
            });
        }

        $this->backfillDemoTenantRows();

        foreach (self::UNIQUE_REPLACEMENTS as $replacement) {
            $this->dropOldUnique($replacement['table'], $replacement['from']);

            Schema::table($replacement['table'], function (Blueprint $blueprint) use ($replacement): void {
                $blueprint->unique($replacement['to']);
            });
        }

        foreach (self::COMPOSITE_INDEXES as $index) {
            Schema::table($index['table'], function (Blueprint $blueprint) use ($index): void {
                $blueprint->index($index['columns']);
            });
        }
    }

    public function down(): void
    {
        foreach (self::COMPOSITE_INDEXES as $index) {
            Schema::table($index['table'], function (Blueprint $blueprint) use ($index): void {
                $blueprint->dropIndex($index['columns']);
            });
        }

        foreach (array_reverse(self::UNIQUE_REPLACEMENTS) as $replacement) {
            Schema::table($replacement['table'], function (Blueprint $blueprint) use ($replacement): void {
                $blueprint->dropUnique($replacement['to']);
                $blueprint->unique($replacement['from']);
            });
        }

        foreach (array_reverse(self::TENANT_TABLES) as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropConstrainedForeignId('company_id');
            });
        }
    }

    /** @param list<string> $columns */
    private function dropOldUnique(string $table, array $columns): void
    {
        $legacyName = self::SQLITE_LEGACY_UNIQUE_NAMES[$table][$columns[0]] ?? null;

        if (DB::getDriverName() === 'sqlite' && $legacyName !== null && count($columns) === 1) {
            DB::statement('DROP INDEX IF EXISTS '.$legacyName);

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
            $blueprint->dropUnique($columns);
        });
    }

    private function backfillDemoTenantRows(): void
    {
        $companyId = DB::table('companies')
            ->where('is_demo', true)
            ->value('id');

        if ($companyId === null) {
            return;
        }

        foreach (self::TENANT_TABLES as $table) {
            DB::table($table)
                ->whereNull('company_id')
                ->update(['company_id' => $companyId]);
        }
    }
};
