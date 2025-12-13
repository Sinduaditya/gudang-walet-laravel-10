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
        Schema::create('idm_transfer_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idm_transfer_id')->constrained('idm_transfers')->onDelete('cascade');
            // assuming we link to idm_details. nullable if the item is deleted? or restrict.
            $table->foreignId('idm_detail_id')->constrained('idm_details')->onDelete('cascade');
            
            // Snapshots
            $table->string('item_name')->nullable(); // In case it's different
            $table->string('grade_idm_name')->nullable();
            $table->decimal('weight', 15, 2)->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idm_transfer_details');
    }
};
