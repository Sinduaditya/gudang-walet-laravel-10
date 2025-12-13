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
        Schema::table('sorting_results', function (Blueprint $table) {
            $table->integer('quantity')->after('weight_grams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sorting_results', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
