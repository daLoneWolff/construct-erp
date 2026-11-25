<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('pan', 9);
            $table->string('vat', 9)->nullable();
            $table->string('address');
            $table->string('contact_person');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->decimal('tds_rate', 5, 2);
            $table->string('status')->default('active');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
