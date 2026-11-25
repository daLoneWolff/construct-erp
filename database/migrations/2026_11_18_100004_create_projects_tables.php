<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('client_name');
            $table->bigInteger('contract_value_paisa');
            $table->string('location_ward');
            $table->string('location_municipality');
            $table->string('location_district');
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('project_manager_id')->constrained('users');
            $table->decimal('retention_pct', 5, 2)->default(5.00);
            $table->bigInteger('advance_paisa')->default(0);
            $table->bigInteger('advance_adjusted_paisa')->default(0);
            $table->string('status')->default('active');
            $table->timestampsTz();
        });

        Schema::create('project_employee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('role_on_project');
            $table->date('assigned_at');
            $table->timestampsTz();

            $table->unique(['project_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_employee');
        Schema::dropIfExists('projects');
    }
};
