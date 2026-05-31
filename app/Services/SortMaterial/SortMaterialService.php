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
            $destination = $data['destination'] ?? null;
            
            $destinationMap = [
                'mangkok' => 2,
                'idm' => 3,
                'aa' => 5,
                'af' => 1, // LEMPENG
            ];

            // Jika ada tujuan sortir (destination), arahkan parent_grade_company_id ke target parent grade!
            $targetParentId = isset($destinationMap[$destination])
                ? $destinationMap[$destination]
                : $data['parent_grade_company_id'];

            // Tambahkan keterangan deskripsi jika ada tujuan
            if ($destination && empty($data['description'])) {
                $data['description'] = 'Sortir Global - Masuk Stok (' . ucfirst($destination) . ')';
            }

            $sortMaterial = SortMaterial::create(array_merge($data, [
                'type'                    => SortMaterial::TYPE_MASUK,
                'parent_grade_company_id' => $targetParentId,
            ]));

            // Update parent grade stock dari target
            ParentGradeCompany::find($targetParentId)
                ?->increment('stock', $data['weight']);

            return $sortMaterial;
        });
    }

    // =============================================
    // KELUAR (PENJUALAN DARI SORTIR)
    // =============================================

    /**
     * Jual barang dari stok sortir bahan
     */
    public function sellFromSort(array $data): SortMaterial
    {
        return DB::transaction(function () use ($data) {
            $gradeCompanyId = $data['grade_company_id'] ?? null;
            
            if (!$gradeCompanyId) {
                throw new \Exception("Grade Company harus dipilih untuk melakukan penjualan sortir.");
            }

            $grade = GradeCompany::findOrFail($gradeCompanyId);
            $parentGrade = ParentGradeCompany::lockForUpdate()->findOrFail($grade->parent_grade_company_id);

            $weight = (float) $data['weight'];

            // Validasi stok di tingkat Grade Company secara dinamis
            $availableGradeStock = $this->getSortStockByGrade($gradeCompanyId);
            if ($availableGradeStock < $weight) {
                throw new \Exception(
                    "Stok sortir untuk grade '{$grade->name}' tidak mencukupi. Tersedia: " .
                    number_format($availableGradeStock, 0) . " gr, diminta: " .
                    number_format($weight, 0) . " gr."
                );
            }

            $sortMaterial = SortMaterial::create([
                'type'                    => SortMaterial::TYPE_KELUAR,
                'weight'                  => $weight,
                'sort_date'               => $data['sale_date'] ?? now(),
                'sale_date'               => $data['sale_date'] ?? now(),
                'parent_grade_company_id' => $grade->parent_grade_company_id,
                'grade_company_id'        => $gradeCompanyId,
                'notes'                   => $data['notes'] ?? null,
                'description'             => 'Penjualan dari Sortir Bahan',
            ]);

            // Selalu kurangi total stok parent cache juga
            $parentGrade->decrement('stock', $weight);

            return $sortMaterial;
        });
    }

    /**
     * Hapus penjualan dari sortir dan kembalikan stok
     */
    public function deleteSale(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $sortMaterial = SortMaterial::where('type', SortMaterial::TYPE_KELUAR)
                ->findOrFail($id);

            // Kembalikan stok ke parent grade
            ParentGradeCompany::find($sortMaterial->parent_grade_company_id)
                ?->increment('stock', $sortMaterial->weight);

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
                // VALIDASI: Apakah jika dihapus, stok sortir grade company ini menjadi negatif?
                if ($sortMaterial->grade_company_id) {
                    $availableGradeStock = $this->getSortStockByGrade($sortMaterial->grade_company_id);
                    if ($availableGradeStock < $sortMaterial->weight) {
                        throw new \Exception(
                            "Data sortir masuk tidak dapat dihapus karena barang dari grade '" .
                            ($sortMaterial->gradeCompany->name ?? 'Unknown') . "' ini " .
                            "telah diproses/dijual di Penjualan Langsung. Silakan hapus transaksi penjualan sortir terlebih dahulu."
                        );
                    }
                }

                // Kembalikan stok ke parent grade (dikurangi karena dibatalkan masuknya)
                ParentGradeCompany::find($sortMaterial->parent_grade_company_id)
                    ?->decrement('stock', $sortMaterial->weight);
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
}
