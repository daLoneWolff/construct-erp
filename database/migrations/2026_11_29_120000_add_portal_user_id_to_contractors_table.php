<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractors', function (Blueprint $table): void {
            $table->foreignId('user_id')
                ->nullable()
                ->after('company_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->unique(['company_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('contractors', function (Blueprint $table): void {
            $table->dropUnique(['company_id', 'user_id']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
