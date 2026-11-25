<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boq_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('title');
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();
        });

        Schema::create('boq_clauses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boq_header_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('title');
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();
        });

        Schema::create('boq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boq_clause_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('description');
            $table->string('unit');
            $table->decimal('quantity', 14, 3);
            $table->bigInteger('rate_paisa');
            $table->bigInteger('amount_paisa');
            $table->decimal('executed_quantity', 14, 3)->default(0);
            $table->timestampsTz();

            $table->index('boq_clause_id');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boq_items');
        Schema::dropIfExists('boq_clauses');
        Schema::dropIfExists('boq_headers');
    }
};
