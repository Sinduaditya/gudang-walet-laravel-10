<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('sorting_results', function (Blueprint $table) {
            $table->decimal('weight_grams', 10, 2)->nullable()->change();
            $table->integer('quantity')->nullable()->change();
            $table->decimal('percentage_difference', 5, 2)->nullable()->change();
            $table->text('notes')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sorting_results', function (Blueprint $table) {
            //
        });
    }
};
