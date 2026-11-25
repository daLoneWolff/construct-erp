<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const RENAMES = [
        'contractors' => [
            'thekedars_id_not_null' => 'contractors_id_not_null',
            'thekedars_is_active_not_null' => 'contractors_is_active_not_null',
            'thekedars_name_not_null' => 'contractors_name_not_null',
        ],
        'contractor_payments' => [
            'thekedar_payments_amount_paisa_not_null' => 'contractor_payments_amount_paisa_not_null',
            'thekedar_payments_id_not_null' => 'contractor_payments_id_not_null',
            'thekedar_payments_method_not_null' => 'contractor_payments_method_not_null',
            'thekedar_payments_paid_on_not_null' => 'contractor_payments_paid_on_not_null',
            'thekedar_payments_payment_no_not_null' => 'contractor_payments_payment_no_not_null',
            'thekedar_payments_project_id_not_null' => 'contractor_payments_project_id_not_null',
            'thekedar_payments_project_job_id_not_null' => 'contractor_payments_project_job_id_not_null',
            'thekedar_payments_thekedar_id_not_null' => 'contractor_payments_contractor_id_not_null',
        ],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::RENAMES as $table => $renames) {
            foreach ($renames as $from => $to) {
                $this->renameIfPresent($table, $from, $to);
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::RENAMES as $table => $renames) {
            foreach ($renames as $from => $to) {
                $this->renameIfPresent($table, $to, $from);
            }
        }
    }

    private function renameIfPresent(string $table, string $from, string $to): void
    {
        $constraintExists = DB::selectOne(
            'SELECT 1 FROM pg_constraint WHERE conname = ? AND conrelid = to_regclass(?)',
            [$from, $table],
        ) !== null;

        if (! $constraintExists) {
            return;
        }

        DB::statement("ALTER TABLE {$table} RENAME CONSTRAINT {$from} TO {$to}");
    }
};
