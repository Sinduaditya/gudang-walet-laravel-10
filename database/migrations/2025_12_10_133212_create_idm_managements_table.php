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
        Schema::create('idm_managements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('grade_company_id')->nullable()->constrained('grades_company')->onDelete('set null');
            $table->decimal('initial_weight', 10, 2)->default(0);
            $table->decimal('shrinkage', 10, 2)->default(0);
            $table->decimal('initial_price', 15, 2)->default(0);
            $table->decimal('estimated_selling_price', 15, 2)->nullable();
            $table->date('grading_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idm_managements');
    }
};
