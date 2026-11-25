<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_site_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('report_date');
            $table->foreignId('submitted_by_employee_id')->constrained('employees');
            $table->string('weather');
            $table->integer('manpower_total')->default(0);
            $table->text('remarks')->nullable();
            $table->string('status')->default('submitted');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampsTz();

            $table->unique(['project_id', 'report_date']);
        });

        Schema::create('dsr_manpower', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_site_report_id')->constrained()->cascadeOnDelete();
            $table->string('trade');
            $table->integer('count');
            $table->decimal('hours', 4, 2);
            $table->timestampsTz();
        });

        Schema::create('dsr_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_site_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->decimal('hours', 4, 2);
            $table->timestampsTz();
        });

        Schema::create('dsr_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_site_report_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->string('unit');
            $table->decimal('quantity', 14, 3);
            $table->foreignId('boq_item_id')->nullable()->constrained('boq_items')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('dsr_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_site_report_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->integer('size_bytes');
            $table->string('mime');
            $table->string('path_mock');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dsr_photos');
        Schema::dropIfExists('dsr_materials');
        Schema::dropIfExists('dsr_equipment');
        Schema::dropIfExists('dsr_manpower');
        Schema::dropIfExists('daily_site_reports');
    }
};
