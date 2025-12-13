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
        Schema::table('receipt_items', function (Blueprint $table) {
            $table->decimal('percentage_difference', 8, 4)->nullable()->after('difference_grams')
                ->comment('Persentase selisih: (selisih / berat masuk) * 100');
        });
    }

    public function down()
    {
        Schema::table('receipt_items', function (Blueprint $table) {
            $table->dropColumn('percentage_difference');
        });
    }
};
