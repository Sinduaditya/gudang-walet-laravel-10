<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_receipt_id')->constrained('purchase_receipts')->cascadeOnDelete();
            $table->foreignId('grade_supplier_id')->constrained('grades_supplier')->cascadeOnDelete();
            $table->decimal('supplier_weight_grams', 10, 2);
            $table->decimal('warehouse_weight_grams', 10, 2);
            $table->decimal('difference_grams', 10, 2);
            $table->decimal('moisture_percentage', 5, 2);
            $table->boolean('is_flagged_red')->default(false);
            $table->string('status')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_items');
    }
};
