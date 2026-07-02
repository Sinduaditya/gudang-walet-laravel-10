<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->string('category', 50)->nullable()->after('transaction_type');
            $table->boolean('is_revert')->default(false)->after('category');
            $table->index(['category', 'is_revert'], 'idx_invtx_category');
        });

        DB::statement("UPDATE inventory_transactions SET category = CASE
            WHEN transaction_type LIKE 'SORT%'              THEN 'SORT'
            WHEN transaction_type LIKE 'IDM%'               THEN 'IDM'
            WHEN transaction_type LIKE 'EXTERNAL_TRANSFER%' THEN 'EXTERNAL_TRANSFER'
            WHEN transaction_type LIKE 'TRANSFER%'          THEN 'TRANSFER'
            WHEN transaction_type LIKE 'RECEIVE_EXTERNAL%'  THEN 'RECEIVE_EXTERNAL'
            WHEN transaction_type LIKE 'RECEIVE_INTERNAL%'  THEN 'RECEIVE_INTERNAL'
            WHEN transaction_type LIKE 'SALE%'              THEN 'SALE'
            WHEN transaction_type = 'GRADING_IN'            THEN 'GRADING'
            WHEN transaction_type = 'ADJUSTMENT_IN'         THEN 'ADJUSTMENT'
            ELSE 'UNKNOWN'
        END");

        DB::statement("UPDATE inventory_transactions SET is_revert = 1
            WHERE transaction_type LIKE '%REVERT%' OR transaction_type = 'SALE_REVERT'");
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_invtx_category');
            $table->dropColumn(['category', 'is_revert']);
        });
    }
};
