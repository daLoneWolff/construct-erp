<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->unique();
            $table->string('name');
            $table->string('designation');
            $table->string('trade')->nullable();
            $table->string('department');
            $table->string('employment_type');
            $table->bigInteger('daily_wage_paisa')->nullable();
            $table->bigInteger('monthly_basic_paisa')->nullable();
            $table->string('pan', 9)->nullable();
            $table->boolean('ssf_enrolled')->default(false);
            $table->string('ssf_no')->nullable();
            $table->date('joined_at');
            $table->string('phone')->nullable();
            $table->string('status')->default('active');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
