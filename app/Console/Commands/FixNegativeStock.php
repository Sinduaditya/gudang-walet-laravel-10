<?php

namespace App\Console\Commands;

use App\Models\GradeCompany;
use App\Models\InventoryTransaction;
use App\Models\Location;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixNegativeStock extends Command
{
    protected $signature = 'stock:fix-negative
                            {--dry-run : Preview saja, tidak menyimpan perubahan}';

    protected $description = 'Temukan semua grade dengan stok minus dan buat ADJUSTMENT_IN untuk menutupnya ke 0';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info(
            $isDryRun
            ? '==== DRY RUN: Tidak ada data yang diubah ===='
            : '==== EKSEKUSI: Menyimpan adjustment ke database ===='
        );
        $this->newLine();

        $gudangUtama = Location::where('name', 'Gudang Utama')->first();

        if (!$gudangUtama) {
            $this->error('Lokasi "Gudang Utama" tidak ditemukan!');
            return Command::FAILURE;
        }

        $grades = GradeCompany::all();
        $fixedCount = 0;
        $totalAdjusted = 0;

        $this->table(
            ['Grade', 'Net Stok Sekarang', 'Adjustment', 'Tipe'],
            $grades->filter(function ($grade) {
                $net = (float) InventoryTransaction::where('grade_company_id', $grade->id)->sum('quantity_change_grams');
                return $net < -0.01;
            })->map(function ($grade) use ($isDryRun, $gudangUtama, &$fixedCount, &$totalAdjusted) {
                $net = (float) InventoryTransaction::where('grade_company_id', $grade->id)->sum('quantity_change_grams');
                $adjustment = abs($net);

                if (!$isDryRun) {
                    DB::transaction(function () use ($grade, $adjustment, $gudangUtama) {
                        InventoryTransaction::create([
                            'transaction_date' => now(),
                            'grade_company_id' => $grade->id,
                            'location_id' => $gudangUtama->id,
                            'quantity_change_grams' => $adjustment,
                            'transaction_type' => 'ADJUSTMENT_IN',
                            'sorting_result_id' => null,
                            'notes' => 'Auto-fix: menutup stok minus (riwayat bug dropdown lama)',
                            'created_by' => 1,
                        ]);
                    });
                }

                $fixedCount++;
                $totalAdjusted += $adjustment;

                return [
                    $grade->name,
                    number_format($net, 2) . ' gr',
                    '+' . number_format($adjustment, 2) . ' gr',
                    'ADJUSTMENT_IN',
                ];
            })->values()->toArray()
        );

        $this->newLine();

        if ($fixedCount === 0) {
            $this->info('✅ Semua grade sudah 0 atau positif. Tidak ada yang perlu diperbaiki!');
            return Command::SUCCESS;
        }

        $this->info("Total grade diperbaiki : $fixedCount grade");
        $this->info('Total gram di-adjust   : ' . number_format($totalAdjusted, 2) . ' gr');

        if ($isDryRun) {
            $this->newLine();
            $this->warn('Ini adalah DRY RUN. Jalankan tanpa --dry-run untuk menyimpan perubahan.');
        } else {
            $this->newLine();
            $this->info('✅ Semua stok minus berhasil diperbaiki!');
        }

        return Command::SUCCESS;
    }
}
