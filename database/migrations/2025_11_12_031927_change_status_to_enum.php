<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipt_items', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('receipt_items', function (Blueprint $table) {
            $table->enum('status', ['mentah', 'selesai_disortir'])->default('mentah')->after('is_flagged_red');
        });
    }

    public function down(): void
    {
        Schema::table('receipt_items', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('receipt_items', function (Blueprint $table) {
            $table->enum('status', ['received', 'processing', 'completed'])->default('received')->after('is_flagged_red');
        });
    }
};