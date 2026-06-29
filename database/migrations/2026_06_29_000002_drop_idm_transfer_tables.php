<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('idm_transfer_details');
        Schema::dropIfExists('idm_transfers');
    }

    public function down(): void
    {
        // Re-create idm_transfers (versi original — drop_price sudah hapus total_price)
        Schema::create('idm_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_code')->unique();
            $table->date('transfer_date');
            $table->integer('sum_goods')->default(0);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
        });

        Schema::create('idm_transfer_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idm_transfer_id')->constrained('idm_transfers')->cascadeOnDelete();
            $table->foreignId('idm_detail_id')->constrained('idm_details')->cascadeOnDelete();
            $table->string('item_name')->nullable();
            $table->string('grade_idm_name')->nullable();
            $table->decimal('weight', 10, 2)->default(0);
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
        });
    }
};
