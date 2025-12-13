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
        Schema::create('sorting_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_item_id')->constrained('receipt_items')->cascadeOnDelete();
            $table->foreignId('grade_company_id')->constrained('grades_company')->cascadeOnDelete();
            $table->decimal('weight_grams', 10, 2);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sorting_results');
    }
};
