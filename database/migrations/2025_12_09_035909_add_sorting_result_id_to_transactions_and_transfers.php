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
        Schema::table('inventory_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_transactions', 'sorting_result_id')) {
                $table->foreignId('sorting_result_id')->nullable()->after('reference_id')->constrained('sorting_results')->nullOnDelete();
            }
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfers', 'sorting_result_id')) {
                $table->foreignId('sorting_result_id')->nullable()->after('notes')->constrained('sorting_results')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_transactions', 'sorting_result_id')) {
                $table->dropForeign(['sorting_result_id']);
                $table->dropColumn('sorting_result_id');
            }
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('stock_transfers', 'sorting_result_id')) {
                $table->dropForeign(['sorting_result_id']);
                $table->dropColumn('sorting_result_id');
            }
        });
    }
};
