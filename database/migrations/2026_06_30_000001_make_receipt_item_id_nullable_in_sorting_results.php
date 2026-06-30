<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sorting_results', function (Blueprint $table) {
            // Bikin receipt_item_id nullable supaya bisa bikin SortingResult
            // untuk output ManajemenIDM (yang diagregasi dari banyak batch, tidak punya 1 receipt_item).
            $table->dropForeign(['receipt_item_id']);
            $table->foreignId('receipt_item_id')->nullable()->change()->constrained('receipt_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sorting_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('receipt_item_id');
            $table->foreignId('receipt_item_id')->nullable(false)->change()->constrained('receipt_items')->cascadeOnDelete();
        });
    }
};