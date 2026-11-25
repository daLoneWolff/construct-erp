<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type');
            $table->string('ownership');
            $table->bigInteger('hourly_rate_paisa')->nullable();
            $table->bigInteger('daily_rate_paisa')->nullable();
            $table->string('status')->default('available');
            $table->foreignId('current_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('equipment_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('usage_date');
            $table->decimal('hours', 4, 2);
            $table->string('operator_name')->nullable();
            $table->string('notes')->nullable();
            $table->timestampsTz();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('registration_no')->unique();
            $table->string('type');
            $table->string('make_model');
            $table->date('bluebook_expiry');
            $table->date('insurance_expiry');
            $table->date('pollution_expiry');
            $table->date('route_permit_expiry')->nullable();
            $table->string('status')->default('available');
            $table->foreignId('current_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('equipment_usages');
        Schema::dropIfExists('equipment');
    }
};
