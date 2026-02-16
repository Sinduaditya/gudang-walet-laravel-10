<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sort_materials', function (Blueprint $table) {
            $table->id();
            $table->decimal('weight', 10, 2);
            $table->date('sort_date');
            $table->foreignId('parent_grade_company_id')->constrained('parent_grade_companies')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sort_materials');
    }
};
