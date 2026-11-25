<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location');
            $table->string('type');
            $table->timestampsTz();
        });

        Schema::create('material_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->date('received_date');
            $table->foreignId('received_by_employee_id')->constrained('employees');
            $table->text('notes')->nullable();
            $table->string('status')->default('draft');
            $table->timestampsTz();
        });

        Schema::create('material_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('ordered_qty', 14, 3);
            $table->decimal('received_qty', 14, 3);
            $table->decimal('rejected_qty', 14, 3)->default(0);
            $table->timestampsTz();
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issued_to_employee_id')->constrained('employees');
            $table->date('issued_date');
            $table->text('notes')->nullable();
            $table->timestampsTz();
        });

        Schema::create('material_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->string('unit');
            $table->decimal('quantity', 14, 3);
            $table->foreignId('boq_item_id')->nullable()->constrained('boq_items')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('item_description');
            $table->string('unit');
            $table->decimal('in_qty', 14, 3)->default(0);
            $table->decimal('out_qty', 14, 3)->default(0);
            $table->decimal('balance_qty', 14, 3);
            $table->string('transaction_type');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->date('transaction_date');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
        Schema::dropIfExists('material_issue_items');
        Schema::dropIfExists('material_issues');
        Schema::dropIfExists('material_receipt_items');
        Schema::dropIfExists('material_receipts');
        Schema::dropIfExists('warehouses');
    }
};
