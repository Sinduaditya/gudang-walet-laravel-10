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
        Schema::table('sort_materials', function (Blueprint $table) {
            $table->unsignedBigInteger('sorting_result_id')->nullable()->after('grade_company_id');
            $table->foreign('sorting_result_id')->references('id')->on('sorting_results')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sort_materials', function (Blueprint $table) {
            //
        });
    }
};
