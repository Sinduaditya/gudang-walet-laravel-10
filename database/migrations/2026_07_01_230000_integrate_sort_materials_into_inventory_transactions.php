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
            $table->dropForeign(['grade_company_id']);
            $table->dropForeign(['location_id']);
        });

        DB::statement('ALTER TABLE inventory_transactions MODIFY COLUMN grade_company_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE inventory_transactions MODIFY COLUMN location_id BIGINT UNSIGNED NULL');

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreign('grade_company_id')->references('id')->on('grades_company')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();

            $table->foreignId('parent_grade_company_id')
                ->nullable()
                ->after('grade_company_id')
                ->constrained('parent_grade_companies')
                ->nullOnDelete();

            $table->index(['parent_grade_company_id', 'transaction_type', 'deleted_at'], 'idx_invtx_sort');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_invtx_sort');
            $table->dropForeign(['parent_grade_company_id']);
            $table->dropColumn('parent_grade_company_id');
            $table->dropForeign(['grade_company_id']);
            $table->dropForeign(['location_id']);
        });

        DB::statement('UPDATE inventory_transactions SET grade_company_id = 1 WHERE grade_company_id IS NULL');
        DB::statement('UPDATE inventory_transactions SET location_id = 1 WHERE location_id IS NULL');
        DB::statement('ALTER TABLE inventory_transactions MODIFY COLUMN grade_company_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE inventory_transactions MODIFY COLUMN location_id BIGINT UNSIGNED NOT NULL');

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreign('grade_company_id')->references('id')->on('grades_company')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
        });
    }
};
