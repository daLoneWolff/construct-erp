<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_partners', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('share_percent', 5, 2);
            $table->timestampsTz();

            $table->index(['company_id', 'project_id']);
            $table->unique(['company_id', 'project_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_partners');
    }
};
