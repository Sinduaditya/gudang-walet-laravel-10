<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sort_materials', function (Blueprint $table) {
            // Menyimpan ID parent grade asal saat record ini dibuat dari aktivitas grading internal.
            // NULL berarti record ini adalah input biasa (bukan hasil grading).
            $table->unsignedBigInteger('grading_source_parent_id')
                  ->nullable()
                  ->after('sorting_result_id');

            $table->foreign('grading_source_parent_id')
                  ->references('id')
                  ->on('parent_grade_companies')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sort_materials', function (Blueprint $table) {
            $table->dropForeign(['grading_source_parent_id']);
            $table->dropColumn('grading_source_parent_id');
        });
    }
};
