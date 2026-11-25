<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('running_bills', function (Blueprint $table) {
            $table->dropColumn('retention_paisa');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('retention_pct');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('retention_pct', 5, 2)->default(5.00);
        });

        Schema::table('running_bills', function (Blueprint $table) {
            $table->bigInteger('retention_paisa')->default(0);
        });
    }
};
