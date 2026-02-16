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
        Schema::table('grades_company', function (Blueprint $table) {
            $table->foreignId('parent_grade_company_id')->nullable()->constrained('parent_grade_companies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades_company', function (Blueprint $table) {
            $table->dropForeign(['parent_grade_company_id']);
            $table->dropColumn('parent_grade_company_id');
        });
    }
};
