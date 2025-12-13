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
            $table->enum('outgoing_type', ['penjualan_langsung', 'internal', 'external'])->nullable()->after('notes');
            $table->enum('category_grade', ['IDM A', 'IDM B'])->nullable()->after('outgoing_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sorting_results', function (Blueprint $table) {
            $table->dropColumn(['outgoing_type', 'category_grade']);
        });
    }
};
