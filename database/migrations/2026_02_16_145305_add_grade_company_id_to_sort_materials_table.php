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
        Schema::table('sort_materials', function (Blueprint $table) {
            $table->foreignId('grade_company_id')->nullable()->constrained('grades_company')->nullOnDelete()->after('parent_grade_company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sort_materials', function (Blueprint $table) {
            $table->dropForeign(['grade_company_id']);
            $table->dropColumn('grade_company_id');
        });
    }
};
