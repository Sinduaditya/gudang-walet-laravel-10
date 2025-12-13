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
        Schema::create('idm_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idm_management_id')->constrained('idm_managements')->onDelete('cascade');
            $table->enum('grade_idm_name', ['perutan', 'kakian', 'idm']);
            $table->decimal('weight', 10, 2)->default(0);
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
        Schema::dropIfExists('idm_details');
    }
};
