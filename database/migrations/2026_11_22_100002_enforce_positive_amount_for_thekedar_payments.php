<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CONSTRAINT = 'thekedar_payments_amount_paisa_positive_check';

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite is only used for tests and cannot add table CHECK constraints with ALTER TABLE.
            return;
        }

        if (DB::getDriverName() !== 'pgsql') {
            throw new RuntimeException('The payment amount check requires PostgreSQL.');
        }

        DB::statement('ALTER TABLE thekedar_payments ADD CONSTRAINT '.self::CONSTRAINT.' CHECK (amount_paisa > 0)');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (DB::getDriverName() !== 'pgsql') {
            throw new RuntimeException('The payment amount check requires PostgreSQL.');
        }

        DB::statement('ALTER TABLE thekedar_payments DROP CONSTRAINT '.self::CONSTRAINT);
    }
};
