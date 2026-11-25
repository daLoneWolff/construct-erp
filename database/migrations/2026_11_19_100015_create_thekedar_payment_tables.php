<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thekedars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('pan', 9)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestampsTz();
        });

        Schema::create('project_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('thekedar_id')->nullable()->constrained('thekedars')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->bigInteger('agreed_amount_paisa')->default(0);
            $table->string('status')->default('open');
            $table->date('starts_on')->nullable();
            $table->date('due_on')->nullable();
            $table->timestampsTz();

            $table->index(['project_id', 'status']);
            $table->index('thekedar_id');
        });

        Schema::create('thekedar_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_job_id')->constrained('project_jobs')->cascadeOnDelete();
            $table->foreignId('thekedar_id')->constrained('thekedars')->restrictOnDelete();
            $table->bigInteger('amount_paisa');
            $table->date('paid_on');
            $table->string('method')->default('cash');
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['project_id', 'paid_on']);
            $table->index(['thekedar_id', 'paid_on']);
            $table->index('project_job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thekedar_payments');
        Schema::dropIfExists('project_jobs');
        Schema::dropIfExists('thekedars');
    }
};
