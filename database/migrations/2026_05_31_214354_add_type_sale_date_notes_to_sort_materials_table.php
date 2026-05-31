<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sort_materials', function (Blueprint $table) {
            $table->enum('type', ['masuk', 'keluar'])->default('masuk')->after('weight');
            $table->date('sale_date')->nullable()->after('type');
            $table->text('notes')->nullable()->after('description');
        });

        // Semua data lama dianggap type='masuk'
        DB::table('sort_materials')->whereNull('deleted_at')->update(['type' => 'masuk']);

        // Recalculate parent_grade_companies.stock dari sort_materials
        $parents = DB::table('parent_grade_companies')->get();
        foreach ($parents as $parent) {
            $masuk  = DB::table('sort_materials')
                ->where('parent_grade_company_id', $parent->id)
                ->where('type', 'masuk')
                ->whereNull('deleted_at')
                ->sum('weight');
            $keluar = DB::table('sort_materials')
                ->where('parent_grade_company_id', $parent->id)
                ->where('type', 'keluar')
                ->whereNull('deleted_at')
                ->sum('weight');
            DB::table('parent_grade_companies')
                ->where('id', $parent->id)
                ->update(['stock' => $masuk - $keluar]);
        }
    }

    public function down(): void
    {
        Schema::table('sort_materials', function (Blueprint $table) {
            $table->dropColumn(['type', 'sale_date', 'notes']);
        });
    }
};
