<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array<string, list<string>>>
     */
    private array $indexes = [
        'project_jobs' => [
            'project_jobs_company_project_idx' => ['company_id', 'project_id'],
            'project_jobs_company_contractor_idx' => ['company_id', 'contractor_id'],
        ],
        'contractor_payments' => [
            'contractor_payments_company_job_idx' => ['company_id', 'project_job_id'],
        ],
        'stock_ledger' => [
            'stock_ledger_warehouse_item_idx' => ['warehouse_id', 'item_description', 'unit', 'id'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = collect(Schema::getIndexes($table))->pluck('name')->all();

            Schema::table($table, function (Blueprint $blueprint) use ($indexes, $existing): void {
                foreach ($indexes as $name => $columns) {
                    if (in_array($name, $existing, true)) {
                        continue;
                    }

                    $blueprint->index($columns, $name);
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = collect(Schema::getIndexes($table))->pluck('name')->all();

            Schema::table($table, function (Blueprint $blueprint) use ($indexes, $existing): void {
                foreach ($indexes as $name => $columns) {
                    if (! in_array($name, $existing, true)) {
                        continue;
                    }

                    $blueprint->dropIndex($name);
                }
            });
        }
    }
};
