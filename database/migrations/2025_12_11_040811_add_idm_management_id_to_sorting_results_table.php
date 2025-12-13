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
        Schema::table('sorting_results', function (Blueprint $table) {
            $table->foreignId('idm_management_id')->nullable()->constrained('idm_managements')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sorting_results', function (Blueprint $table) {
            $table->dropForeign(['idm_management_id']);
            $table->dropColumn('idm_management_id');
        });
    }
};
