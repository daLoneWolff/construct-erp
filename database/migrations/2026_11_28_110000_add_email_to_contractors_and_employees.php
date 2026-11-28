<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractors', function (Blueprint $table): void {
            if (! Schema::hasColumn('contractors', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
        });

        Schema::table('employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contractors', function (Blueprint $table): void {
            if (Schema::hasColumn('contractors', 'email')) {
                $table->dropColumn('email');
            }
        });

        Schema::table('employees', function (Blueprint $table): void {
            if (Schema::hasColumn('employees', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
