<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('expense_no', 32);
            $table->date('expense_date');
            $table->bigInteger('amount_paisa');
            $table->string('category', 32);
            $table->string('method', 32);
            $table->string('payee')->nullable();
            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['company_id', 'expense_no']);
            $table->index(['company_id', 'expense_date']);
            $table->index(['company_id', 'category']);
        });

        Schema::create('expense_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_id')->constrained('expenses')->cascadeOnDelete();
            $table->string('filename');
            $table->unsignedBigInteger('size_bytes');
            $table->string('mime', 100);
            $table->string('path_mock');
            $table->timestampsTz();

            $table->index(['company_id', 'expense_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_attachments');
        Schema::dropIfExists('expenses');
    }
};
