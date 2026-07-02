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
     * List parent grade companies dengan stok sortir > 0 (baik di parent mentah ATAU di child-nya)
     */
    public function getAvailableSortStock(): Collection
    {
        return ParentGradeCompany::orderBy('name')
            ->get()
            ->map(function ($pg) {
                $rawStock = $this->getNetSortStock($pg->id);
                $totalStock = $this->getStockByParent($pg->id);
                return [
                    'id'          => $pg->id,
                    'name'        => $pg->name,
                    'stock'       => (float) $rawStock,
                    'total_stock' => (float) $totalStock,
                ];
            })
            ->filter(fn($pg) => $pg['total_stock'] > 0)
            ->values();
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
     * Hitung stok sortir per parent grade (net masuk - keluar), semua tipe termasuk child grade
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
            ->whereNotNull('sale_date')
            ->orderBy('sale_date', 'desc')
            ->orderBy('id', 'desc');

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

            InventoryTransaction::create([
                'transaction_date'        => $data['sort_date'] ?? now(),
                'parent_grade_company_id' => $parentId,
                'grade_company_id'        => $gradeCompanyId ?: null,
                'location_id'             => null,
                'quantity_change_grams'   => $weight,
                'transaction_type'        => InventoryTransaction::SORT_IN,
                'category'                => InventoryTransaction::CAT_SORT,
                'is_revert'               => false,
                'reference_id'            => $sortMaterial->id,
                'created_by'              => auth()->id(),
            ]);

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

            $targetWeightSum = 0.00;
            foreach ($data['targets'] as $target) {
                $targetWeightSum += (float) $target['weight'];
            }

            if (abs($targetWeightSum - $totalWeight) > 0.01) {
                throw new \Exception("Jumlah berat hasil pecahan (" . number_format($targetWeightSum, 2) . " gr) harus tepat sama dengan total berat yang diproses (" . number_format($totalWeight, 2) . " gr).");
            }

            // 2. Buat Record "KELUAR" untuk Sumber Parent
            $sourceKeluar = SortMaterial::create([
                'type'                    => SortMaterial::TYPE_KELUAR,
                'parent_grade_company_id' => $sourceParentId,
                'grade_company_id'        => null,
                'weight'                  => $totalWeight,
                'sort_date'               => $processDate,
                'sale_date'               => $processDate,
                'description'             => 'Aktivitas Grading Internal (Pengurangan Sumber)',
            ]);

            InventoryTransaction::create([
                'transaction_date'        => $processDate,
                'parent_grade_company_id' => $sourceParentId,
                'grade_company_id'        => null,
                'location_id'             => null,
                'quantity_change_grams'   => -$totalWeight,
                'transaction_type'        => InventoryTransaction::SORT_GRADING_OUT,
                'category'                => InventoryTransaction::CAT_SORT,
                'is_revert'               => false,
                'reference_id'            => $sourceKeluar->id,
                'created_by'              => auth()->id(),
            ]);

            // 3. Buat Record "MASUK" untuk Setiap Target Hasil Pecahan
            $sourceParentName = ParentGradeCompany::find($sourceParentId)->name;
            foreach ($data['targets'] as $target) {
                $targetParentId = $target['parent_grade_company_id'];
                $targetGradeId  = $target['grade_company_id'] ?: null;
                $weight         = (float) $target['weight'];

                $targetMasuk = SortMaterial::create([
                    'type'                      => SortMaterial::TYPE_MASUK,
                    'parent_grade_company_id'   => $targetParentId,
                    'grade_company_id'          => $targetGradeId,
                    'weight'                    => $weight,
                    'sort_date'                 => $processDate,
                    'description'               => 'Hasil Grading Internal dari Parent ' . $sourceParentName,
                    'grading_source_parent_id'  => $sourceParentId,
                ]);

                InventoryTransaction::create([
                    'transaction_date'        => $processDate,
                    'parent_grade_company_id' => $targetParentId,
                    'grade_company_id'        => $targetGradeId,
                    'location_id'             => null,
                    'quantity_change_grams'   => $weight,
                    'transaction_type'        => InventoryTransaction::SORT_GRADING_IN,
                    'category'                => InventoryTransaction::CAT_SORT,
                    'is_revert'               => false,
                    'reference_id'            => $targetMasuk->id,
                    'created_by'              => auth()->id(),
                ]);
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

            ParentGradeCompany::lockForUpdate()->findOrFail($parentGradeId);

            if ($gradeCompanyId) {
                $grade = GradeCompany::findOrFail($gradeCompanyId);
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
                $availableParentStock = $this->getNetSortStock($parentGradeId);
                if ($availableParentStock < $weight) {
                    $parentGrade = ParentGradeCompany::findOrFail($parentGradeId);
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

            InventoryTransaction::create([
                'transaction_date'        => $data['sale_date'] ?? now(),
                'parent_grade_company_id' => $parentId,
                'grade_company_id'        => $gradeCompanyId ?: null,
                'location_id'             => null,
                'quantity_change_grams'   => -$weight,
                'transaction_type'        => InventoryTransaction::SORT_OUT,
                'category'                => InventoryTransaction::CAT_SORT,
                'is_revert'               => false,
                'reference_id'            => $sortMaterial->id,
                'created_by'              => auth()->id(),
            ]);

            return $sortMaterial;
        });
    }

    public function deleteSale(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $sortMaterial = SortMaterial::where('type', SortMaterial::TYPE_KELUAR)
                ->whereNotNull('sale_date')
                ->findOrFail($id);

            $this->softDeleteSortTx($sortMaterial->id, InventoryTransaction::SORT_OUT);

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
            $weight       = (float) $sortMaterial->weight;

            if ($sortMaterial->type === SortMaterial::TYPE_MASUK) {

                // ── VALIDASI CHILD GRADE ──────────────────────────────────────────────
                if ($sortMaterial->grade_company_id) {
                    $availableGradeStock = $this->getSortStockByGrade($sortMaterial->grade_company_id);
                    if ($availableGradeStock < $weight) {
                        throw new \Exception(
                            "Data sortir masuk tidak dapat dihapus karena barang dari detail grade '" .
                            ($sortMaterial->gradeCompany->name ?? 'Unknown') .
                            "' ini telah diproses/dijual. Silakan hapus transaksi penjualan sortir terlebih dahulu."
                        );
                    }
                }

                // ── VALIDASI PARENT ──────────────────────────────────────────────────
                if (is_null($sortMaterial->grade_company_id)) {
                    $availableParentStock = $this->getNetSortStock($sortMaterial->parent_grade_company_id);
                    if ($availableParentStock < $weight) {
                        throw new \Exception(
                            "Data sortir masuk tidak dapat dihapus karena sisa stok parent '" .
                            ($sortMaterial->parentGradeCompany->name ?? 'Unknown') . "' hanya " .
                            number_format($availableParentStock, 2) . " gr (dibutuhkan " .
                            number_format($weight, 2) . " gr). Barang kemungkinan sudah dipecah stok atau dijual."
                        );
                    }
                }

                // ── KEMBALIKAN STOK ──────────────────────────────────────────────────
                if ($sortMaterial->grading_source_parent_id) {
                    // Ini TARGET hasil grading internal — reversal ke source parent
                    $sourceParentId = $sortMaterial->grading_source_parent_id;

                    $keluarRecord = SortMaterial::where('parent_grade_company_id', $sourceParentId)
                        ->where('type', SortMaterial::TYPE_KELUAR)
                        ->where('sort_date', $sortMaterial->sort_date)
                        ->where('description', 'Aktivitas Grading Internal (Pengurangan Sumber)')
                        ->whereNull('deleted_at')
                        ->lockForUpdate()
                        ->first();

                    if ($keluarRecord) {
                        $newKeluarWeight = (float) $keluarRecord->weight - $weight;
                        if ($newKeluarWeight <= 0.001) {
                            $keluarId = $keluarRecord->id;
                            $keluarRecord->deleted_by = auth()->id();
                            $keluarRecord->save();
                            $keluarRecord->delete();
                            $this->softDeleteSortTx($keluarId, InventoryTransaction::SORT_GRADING_OUT);
                        } else {
                            $keluarRecord->update(['weight' => round($newKeluarWeight, 2)]);
                            InventoryTransaction::where('reference_id', $keluarRecord->id)
                                ->where('transaction_type', InventoryTransaction::SORT_GRADING_OUT)
                                ->update(['quantity_change_grams' => -round($newKeluarWeight, 2)]);
                        }
                    }

                    $this->softDeleteSortTx($sortMaterial->id, InventoryTransaction::SORT_GRADING_IN);

                } else {
                    // Input biasa: reversal SORT_IN
                    $this->softDeleteSortTx($sortMaterial->id, InventoryTransaction::SORT_IN);
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

            // Soft-delete IT lama, buat yang baru dengan data terupdate
            $this->softDeleteSortTx($sortMaterial->id, InventoryTransaction::SORT_IN);

            $sortMaterial->update($data);

            InventoryTransaction::create([
                'transaction_date'        => $data['sort_date'] ?? $sortMaterial->sort_date ?? now(),
                'parent_grade_company_id' => $data['parent_grade_company_id'],
                'grade_company_id'        => $data['grade_company_id'] ?? null,
                'location_id'             => null,
                'quantity_change_grams'   => (float) $data['weight'],
                'transaction_type'        => InventoryTransaction::SORT_IN,
                'reference_id'            => $sortMaterial->id,
                'created_by'              => auth()->id(),
            ]);

            return $sortMaterial;
        });
    }

    // =============================================
    // HELPER
    // =============================================

    /**
     * Net stok raw parent — dihitung dari inventory_transactions (audit trail)
     */
    public function getNetSortStock(int $parentId): float
    {
        $net = InventoryTransaction::where('parent_grade_company_id', $parentId)
            ->whereNull('grade_company_id')
            ->where('category', InventoryTransaction::CAT_SORT)
            ->sum('quantity_change_grams');

        return max(0.0, (float) $net);
    }

    /**
     * Maintenance tool: sinkronkan cache parent_grade_companies.stock dari sort_materials.
     * Tidak lagi dipanggil otomatis — jalankan manual via artisan/tinker jika perlu rekonsiliasi.
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

    private function softDeleteSortTx(int $refId, string $type): void
    {
        InventoryTransaction::where('reference_id', $refId)
            ->where('transaction_type', $type)
            ->get()
            ->each(function ($tx) {
                $tx->deleted_by = auth()->id();
                $tx->save();
                $tx->delete();
            });
    }
}
