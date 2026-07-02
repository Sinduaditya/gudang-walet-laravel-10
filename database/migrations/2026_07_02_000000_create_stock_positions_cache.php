<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_company_id')->nullable()->constrained('grades_company')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->cascadeOnDelete();
            $table->decimal('quantity_grams', 15, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['grade_company_id', 'location_id'], 'idx_stock_pos_unique');
            $table->index('quantity_grams');
            $table->index('updated_at');
        });

        // Backfill initial data from inventory_transactions
        DB::statement("
            INSERT INTO stock_positions (grade_company_id, location_id, quantity_grams, created_at, updated_at)
            SELECT 
                grade_company_id,
                location_id,
                SUM(quantity_change_grams) as quantity_grams,
                NOW(),
                NOW()
            FROM inventory_transactions
            WHERE deleted_at IS NULL
            GROUP BY grade_company_id, location_id
            HAVING quantity_grams > 0
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_positions');
    }
};
