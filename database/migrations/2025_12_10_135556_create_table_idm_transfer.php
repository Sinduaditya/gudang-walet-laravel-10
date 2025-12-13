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
        Schema::create('idm_transfers', function (Blueprint $table) {
            $table->id();

            // Informasi Dasar
            $table->date('transfer_date');
            $table->string('transfer_code', 50)->unique(); 

            $table->unsignedInteger('sum_goods'); // total barang
            $table->decimal('price_transfer', 15, 2)->default(0); // total harga transfer

            // Harga & Nilai
            $table->decimal('average_idm_price', 15, 2)->default(0); 
            $table->decimal('total_non_idm_price', 15, 2)->default(0); 
            $table->decimal('total_idm_price', 15, 2)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idm_transfer');
    }
};
