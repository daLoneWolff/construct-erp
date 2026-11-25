<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category');
            $table->string('title');
            $table->string('filename');
            $table->integer('size_bytes');
            $table->string('mime');
            $table->string('path_mock');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestampsTz();
        });

        Schema::create('running_bills', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->integer('bill_number');
            $table->date('period_from');
            $table->date('period_to');
            $table->bigInteger('work_certified_paisa');
            $table->bigInteger('retention_paisa');
            $table->bigInteger('advance_adjusted_paisa');
            $table->bigInteger('net_payable_paisa');
            $table->date('certified_date')->nullable();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->unique(['project_id', 'bill_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('running_bills');
        Schema::dropIfExists('documents');
    }
};
