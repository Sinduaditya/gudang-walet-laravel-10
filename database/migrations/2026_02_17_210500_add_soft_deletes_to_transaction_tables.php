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
        $tables = [
            'purchase_receipts',
            'receipt_items',
            'sorting_results',
            'idm_managements',
            'idm_details'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'deleted_at')) {
                        $table->softDeletes();
                    }
                    if (!Schema::hasColumn($tableName, 'deleted_by')) {
                        $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'purchase_receipts',
            'receipt_items',
            'sorting_results',
            'idm_managements',
            'idm_details'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'deleted_by')) {
                        $table->dropForeign(['deleted_by']);
                        $table->dropColumn(['deleted_by']);
                    }
                    if (Schema::hasColumn($tableName, 'deleted_at')) {
                        $table->dropColumn(['deleted_at']);
                    }
                });
            }
        }
    }
};
