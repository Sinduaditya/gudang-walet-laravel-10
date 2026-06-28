<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('idm_managements', function (Blueprint $table) {
            $table->dropColumn(['initial_price', 'estimated_selling_price']);
        });

        Schema::table('idm_details', function (Blueprint $table) {
            $table->dropColumn(['price', 'total_price']);
        });

        Schema::table('idm_transfers', function (Blueprint $table) {
            $table->dropColumn(['price_transfer', 'average_idm_price', 'total_non_idm_price', 'total_idm_price']);
        });

        Schema::table('idm_transfer_details', function (Blueprint $table) {
            $table->dropColumn(['price', 'total_price']);
        });
    }

    public function down(): void
    {
        Schema::table('idm_managements', function (Blueprint $table) {
            $table->decimal('initial_price', 15, 2)->nullable();
            $table->decimal('estimated_selling_price', 15, 2)->nullable();
        });

        Schema::table('idm_details', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
        });

        Schema::table('idm_transfers', function (Blueprint $table) {
            $table->decimal('price_transfer', 15, 2)->nullable();
            $table->decimal('average_idm_price', 15, 2)->nullable();
            $table->decimal('total_non_idm_price', 15, 2)->nullable();
            $table->decimal('total_idm_price', 15, 2)->nullable();
        });

        Schema::table('idm_transfer_details', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
        });
    }
};
