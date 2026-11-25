<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_CONTRACTOR_ENTITY = 'App\\Models\\Thekedar';

    private const LEGACY_PAYMENT_ENTITY = 'App\\Models\\ThekedarPayment';

    private const CONTRACTOR_ENTITY = 'App\\Models\\Contractor';

    private const CONTRACTOR_PAYMENT_ENTITY = 'App\\Models\\ContractorPayment';

    public function up(): void
    {
        $this->ensureSupportedDriver();

        Schema::rename('thekedars', 'contractors');
        Schema::table('project_jobs', function (Blueprint $table): void {
            $table->renameColumn('thekedar_id', 'contractor_id');
        });
        Schema::rename('thekedar_payments', 'contractor_payments');
        Schema::table('contractor_payments', function (Blueprint $table): void {
            $table->renameColumn('thekedar_id', 'contractor_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            $this->renamePostgresObjects();
        }

        DB::table('audit_logs')
            ->where('entity_type', self::LEGACY_CONTRACTOR_ENTITY)
            ->update(['entity_type' => self::CONTRACTOR_ENTITY]);
        DB::table('audit_logs')
            ->where('entity_type', self::LEGACY_PAYMENT_ENTITY)
            ->update(['entity_type' => self::CONTRACTOR_PAYMENT_ENTITY]);

        $this->renameAuditChangeKey('thekedar_id', 'contractor_id');
    }

    public function down(): void
    {
        $this->ensureSupportedDriver();

        $this->renameAuditChangeKey('contractor_id', 'thekedar_id');

        DB::table('audit_logs')
            ->where('entity_type', self::CONTRACTOR_ENTITY)
            ->update(['entity_type' => self::LEGACY_CONTRACTOR_ENTITY]);
        DB::table('audit_logs')
            ->where('entity_type', self::CONTRACTOR_PAYMENT_ENTITY)
            ->update(['entity_type' => self::LEGACY_PAYMENT_ENTITY]);

        if (DB::getDriverName() === 'pgsql') {
            $this->restorePostgresObjects();
        }

        Schema::table('contractor_payments', function (Blueprint $table): void {
            $table->renameColumn('contractor_id', 'thekedar_id');
        });
        Schema::rename('contractor_payments', 'thekedar_payments');
        Schema::table('project_jobs', function (Blueprint $table): void {
            $table->renameColumn('contractor_id', 'thekedar_id');
        });
        Schema::rename('contractors', 'thekedars');
    }

    private function ensureSupportedDriver(): void
    {
        if (! in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            throw new RuntimeException('The contractor table rename requires PostgreSQL or SQLite.');
        }
    }

    private function renamePostgresObjects(): void
    {
        foreach ([
            'ALTER SEQUENCE thekedars_id_seq RENAME TO contractors_id_seq',
            'ALTER SEQUENCE thekedar_payments_id_seq RENAME TO contractor_payments_id_seq',
            'ALTER TABLE contractors RENAME CONSTRAINT thekedars_pkey TO contractors_pkey',
            'ALTER TABLE project_jobs RENAME CONSTRAINT project_jobs_thekedar_id_foreign TO project_jobs_contractor_id_foreign',
            'ALTER INDEX project_jobs_thekedar_id_index RENAME TO project_jobs_contractor_id_index',
            'ALTER TABLE contractor_payments RENAME CONSTRAINT thekedar_payments_pkey TO contractor_payments_pkey',
            'ALTER TABLE contractor_payments RENAME CONSTRAINT thekedar_payments_payment_no_unique TO contractor_payments_payment_no_unique',
            'ALTER TABLE contractor_payments RENAME CONSTRAINT thekedar_payments_project_id_foreign TO contractor_payments_project_id_foreign',
            'ALTER TABLE contractor_payments RENAME CONSTRAINT thekedar_payments_project_job_id_foreign TO contractor_payments_project_job_id_foreign',
            'ALTER TABLE contractor_payments RENAME CONSTRAINT thekedar_payments_thekedar_id_foreign TO contractor_payments_contractor_id_foreign',
            'ALTER TABLE contractor_payments RENAME CONSTRAINT thekedar_payments_created_by_foreign TO contractor_payments_created_by_foreign',
            'ALTER TABLE contractor_payments RENAME CONSTRAINT thekedar_payments_amount_paisa_positive_check TO contractor_payments_amount_paisa_positive_check',
            'ALTER INDEX thekedar_payments_project_id_paid_on_index RENAME TO contractor_payments_project_id_paid_on_index',
            'ALTER INDEX thekedar_payments_thekedar_id_paid_on_index RENAME TO contractor_payments_contractor_id_paid_on_index',
            'ALTER INDEX thekedar_payments_project_job_id_index RENAME TO contractor_payments_project_job_id_index',
        ] as $statement) {
            DB::statement($statement);
        }
    }

    private function restorePostgresObjects(): void
    {
        foreach ([
            'ALTER SEQUENCE contractors_id_seq RENAME TO thekedars_id_seq',
            'ALTER SEQUENCE contractor_payments_id_seq RENAME TO thekedar_payments_id_seq',
            'ALTER TABLE contractors RENAME CONSTRAINT contractors_pkey TO thekedars_pkey',
            'ALTER TABLE project_jobs RENAME CONSTRAINT project_jobs_contractor_id_foreign TO project_jobs_thekedar_id_foreign',
            'ALTER INDEX project_jobs_contractor_id_index RENAME TO project_jobs_thekedar_id_index',
            'ALTER TABLE contractor_payments RENAME CONSTRAINT contractor_payments_pkey TO thekedar_payments_pkey',
            'ALTER TABLE contractor_payments RENAME CONSTRAINT contractor_payments_payment_no_unique TO thekedar_payments_payment_no_unique',
            'ALTER TABLE contractor_payments RENAME CONSTRAINT contractor_payments_project_id_foreign TO thekedar_payments_project_id_foreign',
            'ALTER TABLE contractor_payments RENAME CONSTRAINT contractor_payments_project_job_id_foreign TO thekedar_payments_project_job_id_foreign',
            'ALTER TABLE contractor_payments RENAME CONSTRAINT contractor_payments_contractor_id_foreign TO thekedar_payments_thekedar_id_foreign',
            'ALTER TABLE contractor_payments RENAME CONSTRAINT contractor_payments_created_by_foreign TO thekedar_payments_created_by_foreign',
            'ALTER TABLE contractor_payments RENAME CONSTRAINT contractor_payments_amount_paisa_positive_check TO thekedar_payments_amount_paisa_positive_check',
            'ALTER INDEX contractor_payments_project_id_paid_on_index RENAME TO thekedar_payments_project_id_paid_on_index',
            'ALTER INDEX contractor_payments_contractor_id_paid_on_index RENAME TO thekedar_payments_thekedar_id_paid_on_index',
            'ALTER INDEX contractor_payments_project_job_id_index RENAME TO thekedar_payments_project_job_id_index',
        ] as $statement) {
            DB::statement($statement);
        }
    }

    private function renameAuditChangeKey(string $from, string $to): void
    {
        DB::table('audit_logs')
            ->select(['id', 'changes_json'])
            ->whereNotNull('changes_json')
            ->orderBy('id')
            ->chunkById(100, function ($logs) use ($from, $to): void {
                foreach ($logs as $log) {
                    $changes = json_decode($log->changes_json, true, flags: JSON_THROW_ON_ERROR);

                    if (! is_array($changes) || ! array_key_exists($from, $changes)) {
                        continue;
                    }

                    $changes[$to] = $changes[$from];
                    unset($changes[$from]);

                    DB::table('audit_logs')
                        ->where('id', $log->id)
                        ->update(['changes_json' => json_encode($changes, JSON_THROW_ON_ERROR)]);
                }
            });
    }
};
