<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah grade_company_id (nullable FK ke grades_company)
        Schema::table('idm_details', function (Blueprint $table) {
            $table->foreignId('grade_company_id')->nullable()->after('grade_idm_name')
                ->constrained('grades_company')->nullOnDelete();
        });

        // 2. Perluas enum + uppercase: tambah 'ALU', ubah lowercase ke UPPERCASE
        DB::statement("ALTER TABLE idm_details
            MODIFY COLUMN grade_idm_name ENUM('IDM', 'KAKIAN', 'PERUTAN', 'ALU') NOT NULL");
    }

    public function down(): void
    {
        // Revert enum ke 3 bin lowercase (data zero, aman)
        DB::statement("ALTER TABLE idm_details
            MODIFY COLUMN grade_idm_name ENUM('perutan', 'kakian', 'idm') NOT NULL");

        Schema::table('idm_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grade_company_id');
        });
    }
};
