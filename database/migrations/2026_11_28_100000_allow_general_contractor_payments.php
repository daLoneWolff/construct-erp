<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropNamedNotNullConstraint('contractor_payments', 'contractor_payments_project_job_id_not_null');
        $this->dropNamedNotNullConstraint('contractor_payments', 'contractor_payments_project_id_not_null');

        $this->makeColumnNullable('contractor_payments', 'project_job_id');
        $this->makeColumnNullable('contractor_payments', 'project_id');

        $this->replaceForeignKey('contractor_payments', 'contractor_payments_project_id_foreign', 'project_id', 'projects', 'SET NULL');
        $this->replaceForeignKey('contractor_payments', 'contractor_payments_project_job_id_foreign', 'project_job_id', 'project_jobs', 'SET NULL');
    }

    public function down(): void
    {
        $this->replaceForeignKey('contractor_payments', 'contractor_payments_project_job_id_foreign', 'project_job_id', 'project_jobs', 'CASCADE');
        $this->replaceForeignKey('contractor_payments', 'contractor_payments_project_id_foreign', 'project_id', 'projects', 'CASCADE');

        $this->makeColumnNotNull('contractor_payments', 'project_job_id');
        $this->makeColumnNotNull('contractor_payments', 'project_id');
    }

    private function dropNamedNotNullConstraint(string $table, string $constraint): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $exists = DB::selectOne(
            'SELECT 1 FROM pg_constraint WHERE conname = ? AND conrelid = to_regclass(?)',
            [$constraint, $table],
        ) !== null;

        if ($exists) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$constraint}");
        }
    }

    private function makeColumnNullable(string $table, string $column): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} DROP NOT NULL");

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column): void {
            $blueprint->unsignedBigInteger($column)->nullable()->change();
        });
    }

    private function makeColumnNotNull(string $table, string $column): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET NOT NULL");

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column): void {
            $blueprint->unsignedBigInteger($column)->nullable(false)->change();
        });
    }

    private function replaceForeignKey(string $table, string $constraint, string $column, string $referencesTable, string $onDelete): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $referencesTable, $onDelete): void {
                $blueprint->dropForeign([$column]);
                $foreign = $blueprint->foreign($column)->references('id')->on($referencesTable);
                if ($onDelete === 'CASCADE') {
                    $foreign->cascadeOnDelete();
                } else {
                    $foreign->nullOnDelete();
                }
            });

            return;
        }

        $foreignExists = DB::selectOne(
            'SELECT 1 FROM pg_constraint WHERE conname = ? AND conrelid = to_regclass(?)',
            [$constraint, $table],
        ) !== null;

        if ($foreignExists) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$constraint}");
        }

        DB::statement(
            "ALTER TABLE {$table} ADD CONSTRAINT {$constraint} FOREIGN KEY ({$column}) REFERENCES {$referencesTable}(id) ON DELETE {$onDelete}"
        );
    }
};
