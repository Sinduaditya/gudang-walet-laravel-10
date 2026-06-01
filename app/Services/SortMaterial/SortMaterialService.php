<?php

namespace App\Services\SortMaterial;

use App\Exports\SortMaterialExport;
use App\Models\GradeCompany;
use App\Models\InventoryTransaction;
use App\Models\ParentGradeCompany;
use App\Models\SortingResult;
use App\Models\SortMaterial;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SortMaterialService
{
    // =============================================
    // INDEX & READ
    // =============================================

    public function getAll(?string $search = null)
    {
        $query = SortMaterial::with('parentGradeCompany', 'gradeCompany')
            ->where('type', SortMaterial::TYPE_MASUK);

        if ($search) {
            $query->whereHas('parentGradeCompany', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate(10)->withQueryString();
    }

    public function getById(int $id): SortMaterial
    {
        return SortMaterial::findOrFail($id);
    }

    /**
     * List parent grade companies dengan stok sortir > 0 (untuk dropdown penjualan)
     */
    public function getAvailableSortStock(): Collection
    {
        return ParentGradeCompany::where('stock', '>', 0)
            ->orderBy('name')
            ->get()
            ->map(function ($pg) {
                return [
                    'id'    => $pg->id,
                    'name'  => $pg->name,
                    'stock' => (float) $pg->stock,
                ];
            });
    }

    /**
     * List detail grade company yang memiliki stok sortir > 0
     */
    public function getAvailableSortGradesWithStock(): Collection
    {
        return GradeCompany::orderBy('name')
            ->get()
            ->map(function ($gc) {
                $stock = $this->getSortStockByGrade($gc->id);
                return [
                    'id'                      => $gc->id,
                    'name'                    => $gc->name,
                    'parent_grade_company_id' => $gc->parent_grade_company_id,
                    'stock'                   => (float) $stock,
                ];
            })
            ->filter(fn($g) => $g['stock'] > 0)
            ->values();
    }

    /**
     * Hitung stok sortir per grade company (net masuk - keluar)
     */
    public function getSortStockByGrade(int $gradeId): float
    {
        $masuk  = SortMaterial::where('grade_company_id', $gradeId)
            ->where('type', SortMaterial::TYPE_MASUK)
            ->whereNull('deleted_at')
            ->sum('weight');
        $keluar = SortMaterial::where('grade_company_id', $gradeId)
            ->where('type', SortMaterial::TYPE_KELUAR)
            ->whereNull('deleted_at')
            ->sum('weight');
        return max(0.0, (float) ($masuk - $keluar));
    }

    /**
     * Hitung stok sortir per parent grade (net masuk - keluar)
     */
    public function getStockByParent(int $parentId): float
    {
        $masuk  = SortMaterial::where('parent_grade_company_id', $parentId)
            ->where('type', SortMaterial::TYPE_MASUK)
            ->whereNull('deleted_at')
            ->sum('weight');
        $keluar = SortMaterial::where('parent_grade_company_id', $parentId)
            ->where('type', SortMaterial::TYPE_KELUAR)
            ->whereNull('deleted_at')
            ->sum('weight');
        return max(0, $masuk - $keluar);
    }

    /**
     * List penjualan dari sortir bahan (type='keluar') untuk riwayat
     */
    public function getSortSales(array $filters = [])
    {
        $query = SortMaterial::with(['parentGradeCompany', 'gradeCompany'])
            ->where('type', SortMaterial::TYPE_KELUAR)
            ->whereNotNull('sale_date') // Hanya ambil record penjualan nyata, bukan grading internal!
            ->orderBy('sale_date', 'desc');

        if (!empty($filters['start_date'])) {
            $query->whereDate('sale_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('sale_date', '<=', $filters['end_date']);
        }
        if (!empty($filters['parent_grade_company_id'])) {
            $query->where('parent_grade_company_id', $filters['parent_grade_company_id']);
        }

        if (!empty($filters['no_paginate'])) {
            return $query->get();
        }

        return $query->paginate(10)->withQueryString();
    }

    // =============================================
    // MASUK (INPUT SORTIR BAHAN)
    // =============================================

    /**
     * Tambah data sortir masuk — semua tipe (ALU maupun non-ALU) pakai alur yang sama.
     * Stok sortir berdiri sendiri, TIDAK menyentuh inventory_transactions.
     */
    public function create(array $data): SortMaterial
    {
        return DB::transaction(function () use ($data) {
            $parentId       = $data['parent_grade_company_id'];
            $gradeCompanyId = $data['grade_company_id'] ?? null;
            $weight         = (float) $data['weight'];

            $sortMaterial = SortMaterial::create(array_merge($data, [
                'type'                    => SortMaterial::TYPE_MASUK,
                'weight'                  => $weight,
                'parent_grade_company_id' => $parentId,
                'grade_company_id'        => $gradeCompanyId ?: null,
            ]));

            // Hanya increment stock cache parent jika masuknya adalah raw parent (grade_company_id = null)
            if (is_null($gradeCompanyId)) {
                ParentGradeCompany::find($parentId)
                    ?->increment('stock', $weight);
            }

            return $sortMaterial;
        });
    }

    // =============================================
    // KELUAR (PENJUALAN DARI SORTIR)
    // =============================================

    /**
     * Memproses aktivitas grading/pecah stok sortir internal.
     * Mengurangi stok parent mentah (source) dan menambah stok pecahan detail grade tujuan (targets).
     */
    public function processInternalGrading(array $data): bool
    {
        return DB::transaction(function () use ($data) {
            $sourceParentId = $data['source_parent_grade_company_id'];
            $totalWeight = (float) $data['total_weight'];
            $processDate = $data['process_date'] ?? now();
            
            // 1. Validasi Stok Sumber
            $availableSourceStock = $this->getNetSortStock($sourceParentId);
            if ($availableSourceStock < $totalWeight) {
                $sourceParent = ParentGradeCompany::findOrFail($sourceParentId);
                throw new \Exception("Stok parent asal '" . $sourceParent->name . "' ({$availableSourceStock}g) tidak mencukupi untuk diproses sebesar {$totalWeight}g.");
            }

            // Validasi jumlah berat target harus tepat sama dengan total berat yang diproses
            $targetWeightSum = 0.00;
            foreach ($data['targets'] as $target) {
                $targetWeightSum += (float) $target['weight'];
            }

            // Menggunakan margin toleransi kecil untuk floating point (0.01g)
            if (abs($targetWeightSum - $totalWeight) > 0.01) {
                throw new \Exception("Jumlah berat hasil pecahan (" . number_format($targetWeightSum, 2) . " gr) harus tepat sama dengan total berat yang diproses (" . number_format($totalWeight, 2) . " gr).");
            }

            // 2. Buat Record "KELUAR" untuk Sumber Parent
            SortMaterial::create([
                'type'                    => SortMaterial::TYPE_KELUAR,
                'parent_grade_company_id' => $sourceParentId,
                'grade_company_id'        => null, // Kosong karena memotong stok mentah level parent
                'weight'                  => $totalWeight,
                'sort_date'               => $processDate,
                'sale_date'               => $processDate,
                'description'             => 'Aktivitas Grading Internal (Pengurangan Sumber)',
            ]);
            ParentGradeCompany::find($sourceParentId)->decrement('stock', $totalWeight);

            // 3. Buat Record "MASUK" untuk Setiap Target Hasil Pecahan
            foreach ($data['targets'] as $target) {
                $targetParentId = $target['parent_grade_company_id'];
                $targetGradeId = $target['grade_company_id'] ?: null;
                $weight = (float) $target['weight'];

                SortMaterial::create([
                    'type'                    => SortMaterial::TYPE_MASUK,
                    'parent_grade_company_id' => $targetParentId,
                    'grade_company_id'        => $targetGradeId,
                    'weight'                  => $weight,
                    'sort_date'               => $processDate,
                    'description'             => 'Hasil Grading Internal dari Parent ' . ParentGradeCompany::find($sourceParentId)->name,
                ]);
                
                // Hanya increment stock cache parent jika targetnya adalah raw parent (grade_company_id = null)
                if (is_null($targetGradeId)) {
                    ParentGradeCompany::find($targetParentId)->increment('stock', $weight);
                }
            }

            return true;
        });
    }

    /**
     * Jual barang dari stok sortir bahan
     */
    public function sellFromSort(array $data): SortMaterial
    {
        return DB::transaction(function () use ($data) {
            $gradeCompanyId = $data['grade_company_id'] ?? null;
            $parentGradeId = $data['parent_grade_company_id'] ?? null;
            $weight = (float) $data['weight'];

            if (!$parentGradeId) {
                throw new \Exception("Parent Grade harus dipilih untuk melakukan penjualan sortir.");
            }

            $parentGrade = ParentGradeCompany::lockForUpdate()->findOrFail($parentGradeId);

            if ($gradeCompanyId) {
                $grade = GradeCompany::findOrFail($gradeCompanyId);
                // Validasi stok di tingkat Grade Company secara dinamis
                $availableGradeStock = $this->getSortStockByGrade($gradeCompanyId);
                if ($availableGradeStock < $weight) {
                    throw new \Exception(
                        "Stok sortir untuk grade '{$grade->name}' tidak mencukupi. Tersedia: " .
                        number_format($availableGradeStock, 0) . " gr, diminta: " .
                        number_format($weight, 0) . " gr."
                    );
                }
                $parentId = $grade->parent_grade_company_id;
            } else {
                // Validasi stok sortir langsung dari parent grade stock
                $availableParentStock = $this->getNetSortStock($parentGradeId);
                if ($availableParentStock < $weight) {
                    throw new \Exception(
                        "Stok sortir parent grade '{$parentGrade->name}' tidak mencukupi. Tersedia: " .
                        number_format($availableParentStock, 0) . " gr, diminta: " .
                        number_format($weight, 0) . " gr."
                    );
                }
                $parentId = $parentGradeId;
            }

            $sortMaterial = SortMaterial::create([
                'type'                    => SortMaterial::TYPE_KELUAR,
                'weight'                  => $weight,
                'sort_date'               => $data['sale_date'] ?? now(),
                'sale_date'               => $data['sale_date'] ?? now(),
                'parent_grade_company_id' => $parentId,
                'grade_company_id'        => $gradeCompanyId ?: null,
                'notes'                   => $data['notes'] ?? null,
                'description'             => 'Penjualan dari Sortir Bahan',
            ]);

            // Selalu kurangi total stok parent cache jika penjualan dilakukan dari raw parent (grade_company_id = null)
            if (is_null($gradeCompanyId)) {
                $parentGrade->decrement('stock', $weight);
            }

            return $sortMaterial;
        });
    }

    public function deleteSale(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $sortMaterial = SortMaterial::where('type', SortMaterial::TYPE_KELUAR)
                ->whereNotNull('sale_date') // Mencegah bypass penghapusan record non-penjualan!
                ->findOrFail($id);

            // Kembalikan stok ke parent grade jika penjualan dilakukan dari raw parent
            if (is_null($sortMaterial->grade_company_id)) {
                ParentGradeCompany::find($sortMaterial->parent_grade_company_id)
                    ?->increment('stock', $sortMaterial->weight);
            }

            $sortMaterial->deleted_by = auth()->id();
            $sortMaterial->save();
            $sortMaterial->delete();

            return true;
        });
    }

    // =============================================
    // DELETE (HAPUS RECORD MASUK)
    // =============================================

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $sortMaterial = $this->getById($id);

            if ($sortMaterial->type === SortMaterial::TYPE_MASUK) {
                // 1. VALIDASI DETAIL GRADE: Apakah jika dihapus, stok detail grade company ini menjadi negatif?
                if ($sortMaterial->grade_company_id) {
                    $availableGradeStock = $this->getSortStockByGrade($sortMaterial->grade_company_id);
                    if ($availableGradeStock < $sortMaterial->weight) {
                        throw new \Exception(
                            "Data sortir masuk tidak dapat dihapus karena barang dari detail grade '" .
                            ($sortMaterial->gradeCompany->name ?? 'Unknown') . "' ini " .
                            "telah diproses/dijual di Penjualan Langsung. Silakan hapus transaksi penjualan sortir terlebih dahulu."
                        );
                    }
                }

                // 2. VALIDASI PARENT GRADE (Dua Lapis): Apakah jika dihapus, total sisa stok parent menjadi negatif?
                $availableParentStock = $this->getNetSortStock($sortMaterial->parent_grade_company_id);
                if ($availableParentStock < $sortMaterial->weight) {
                    throw new \Exception(
                        "Data sortir masuk tidak dapat dihapus karena sisa stok yang tersedia di parent '" .
                        ($sortMaterial->parentGradeCompany->name ?? 'Unknown') . "' hanya tinggal " .
                        number_format($availableParentStock, 2) . " gr (tidak mencukupi untuk membatalkan masuk sebesar " .
                        number_format($sortMaterial->weight, 2) . " gr). Barang ini kemungkinan sudah dipecah stok atau dijual."
                    );
                }

                // Kembalikan stok ke parent grade jika yang dibatalkan adalah raw parent
                if (is_null($sortMaterial->grade_company_id)) {
                    ParentGradeCompany::find($sortMaterial->parent_grade_company_id)
                        ?->decrement('stock', $sortMaterial->weight);
                }
            }

            $sortMaterial->deleted_by = auth()->id();
            $sortMaterial->save();
            $sortMaterial->delete();

            return true;
        });
    }

    // =============================================
    // UPDATE (jarang dipakai, tapi tetap ada)
    // =============================================

    public function update(int $id, array $data): SortMaterial
    {
        return DB::transaction(function () use ($id, $data) {
            $sortMaterial = $this->getById($id);
            $oldWeight    = (float) $sortMaterial->weight;
            $oldParentId  = $sortMaterial->parent_grade_company_id;

            $sortMaterial->update($data);

            // Revert stok lama
            ParentGradeCompany::find($oldParentId)?->decrement('stock', $oldWeight);

            // Tambah stok baru
            ParentGradeCompany::find($data['parent_grade_company_id'])
                ?->increment('stock', $data['weight']);

            return $sortMaterial;
        });
    }

    // =============================================
    // HELPER (masih dipakai oleh TrackingStock)
    // =============================================

    /**
     * Alias untuk TrackingStockService::calculateParentSortStock()
     * Net stok = parent_grade_companies.stock (selalu up-to-date)
     */
    public function getNetSortStock(int $parentId): float
    {
        return (float) (ParentGradeCompany::find($parentId)?->stock ?? 0);
    }

    /**
     * Memperbaiki dan menyinkronkan ulang seluruh data stok cache parent_grade_companies
     * agar 100% akurat mewakili stok mentah (raw parent) yang belum di-grading/di-pecah.
     */
    public function recalculateAllParentStocks(): void
    {
        DB::transaction(function () {
            $parents = ParentGradeCompany::all();
            foreach ($parents as $parent) {
                $masuk = SortMaterial::where('parent_grade_company_id', $parent->id)
                    ->whereNull('grade_company_id')
                    ->where('type', SortMaterial::TYPE_MASUK)
                    ->whereNull('deleted_at')
                    ->sum('weight');

                $keluar = SortMaterial::where('parent_grade_company_id', $parent->id)
                    ->whereNull('grade_company_id')
                    ->where('type', SortMaterial::TYPE_KELUAR)
                    ->whereNull('deleted_at')
                    ->sum('weight');

                $parent->update(['stock' => max(0.00, (float) ($masuk - $keluar))]);
            }
        });
    }
}
