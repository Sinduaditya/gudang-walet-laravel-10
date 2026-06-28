<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // Flag penanda lokasi Jasa Cuci (mitra/vendor eksternal).
            // - true  : lokasi jasa cuci → muncul di dropdown Transfer External & Receive External
            // - false : lokasi non-jasa-cuci (DMK, Gudang Utama, dll) → masuk Transfer Internal
            //           dan diperlakukan sebagai exit-point (skip TRANSFER_IN saat transfer internal)
            //
            // Untuk data existing: flag di-handle oleh seeder (LocationSeeder) atau
            // form UI Edit Lokasi (/admin/locations/{id}/edit). Tidak ada auto-update di migration
            // untuk menjaga migration tetap pure schema change.
            $table->boolean('is_jasa_cuci')->default(false)->after('description');
            $table->index('is_jasa_cuci');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex(['is_jasa_cuci']);
            $table->dropColumn('is_jasa_cuci');
        });
    }
};
