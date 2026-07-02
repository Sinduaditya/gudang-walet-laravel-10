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
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }

    public function down(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->date('sale_date');
            $table->string('buyer_name')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('grade_company_id')->constrained('grades_company');
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->decimal('weight_grams', 12, 2);
            $table->decimal('price_per_gram', 12, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->timestamps();
        });
    }
};
