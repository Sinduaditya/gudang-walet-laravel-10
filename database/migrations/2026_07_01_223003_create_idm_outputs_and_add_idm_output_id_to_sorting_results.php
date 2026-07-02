<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat tabel idm_outputs
        Schema::create('idm_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idm_management_id')->constrained('idm_managements')->cascadeOnDelete();
            $table->foreignId('grade_company_id')->nullable()->constrained('grades_company')->nullOnDelete();
            $table->decimal('weight_grams', 12, 2);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Tambah idm_output_id ke sorting_results
        Schema::table('sorting_results', function (Blueprint $table) {
            $table->foreignId('idm_output_id')->nullable()->after('idm_management_id')
                ->constrained('idm_outputs')->nullOnDelete();
        });

        // 3. Data migration: populate idm_outputs dari IDM-SR sorting_results
        //    IDM-SR = receipt_item_id IS NULL AND idm_management_id IS NOT NULL
        $idmSRs = DB::table('sorting_results')
            ->whereNull('receipt_item_id')
            ->whereNotNull('idm_management_id')
            ->whereNull('deleted_at')
            ->get();

        foreach ($idmSRs as $sr) {
            $idmOutputId = DB::table('idm_outputs')->insertGetId([
                'idm_management_id' => $sr->idm_management_id,
                'grade_company_id'  => $sr->grade_company_id,
                'weight_grams'      => $sr->weight_grams,
                'notes'             => $sr->notes,
                'created_by'        => $sr->created_by,
                'created_at'        => $sr->created_at,
                'updated_at'        => $sr->updated_at,
            ]);

            // Update proxy: set idm_output_id, clear idm_management_id
            DB::table('sorting_results')
                ->where('id', $sr->id)
                ->update([
                    'idm_output_id'     => $idmOutputId,
                    'idm_management_id' => null,
                ]);
        }
    }

    public function down(): void
    {
        // Restore IDM-SR: copy idm_management_id back from idm_outputs
        $proxies = DB::table('sorting_results')->whereNotNull('idm_output_id')->get();

        foreach ($proxies as $proxy) {
            $idmOutput = DB::table('idm_outputs')->where('id', $proxy->idm_output_id)->first();
            if ($idmOutput) {
                DB::table('sorting_results')
                    ->where('id', $proxy->id)
                    ->update([
                        'idm_management_id' => $idmOutput->idm_management_id,
                        'idm_output_id'     => null,
                    ]);
            }
        }

        Schema::table('sorting_results', function (Blueprint $table) {
            $table->dropForeign(['idm_output_id']);
            $table->dropColumn('idm_output_id');
        });

        Schema::dropIfExists('idm_outputs');
    }
};
