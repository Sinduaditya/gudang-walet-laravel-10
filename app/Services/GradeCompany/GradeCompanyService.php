<?php

namespace App\Services\GradeCompany;

use App\Exports\GradeCompanyExport;
use App\Models\GradeCompany;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class GradeCompanyService
{
    public function getAll(?string $search = null)
    {
        $query = GradeCompany::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate(10)->withQueryString();
    }

    public function getById(int $id)
    {
        return GradeCompany::findOrFail($id);
    }

    public function create(array $data)
    {
        if (isset($data['name'])) {
            $data['name'] = strtoupper($data['name']);
        }

        if (empty($data['parent_grade_company_id'])) {
            $data['parent_grade_company_id'] = null;
        }

        $image = $data['image_url'] ?? null;
        if ($image instanceof \Illuminate\Http\UploadedFile) {
            $data['image_url'] = $image->store('grade-company', 'public');
        } elseif (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $data['image_url'] = $data['image']->store('grade-company', 'public');
        }

        return GradeCompany::create($data);
    }

    public function update(int $id, array $data)
    {
        if (isset($data['name'])) {
            $data['name'] = strtoupper($data['name']);
        }

        if (array_key_exists('parent_grade_company_id', $data) && empty($data['parent_grade_company_id'])) {
            $data['parent_grade_company_id'] = null;
        }

        $gradeCompany = $this->getById($id);

        $image = $data['image_url'] ?? ($data['image'] ?? null);

        if ($image instanceof \Illuminate\Http\UploadedFile) {
            if ($gradeCompany->image_url && Storage::disk('public')->exists($gradeCompany->image_url)) {
                Storage::disk('public')->delete($gradeCompany->image_url);
            }
            $data['image_url'] = $image->store('grade-company', 'public');
        } else {
            if (!array_key_exists('image_url', $data) && !array_key_exists('image', $data)) {
                // do nothing
            } else {
                unset($data['image_url']);
            }
        }

        $gradeCompany->update($data);
        return $gradeCompany;
    }

    public function delete(int $id)
    {
        $gradeCompany = $this->getById($id);

        // ✅ G-15: Cek apakah masih digunakan di sorting_results
        $usageCount = $gradeCompany->sortingResults()->count();
        if ($usageCount > 0) {
            throw new \Exception(
                "Grade company \"{$gradeCompany->name}\" tidak dapat dihapus karena masih digunakan di {$usageCount} data grading."
            );
        }

        if ($gradeCompany->image_url && Storage::disk('public')->exists($gradeCompany->image_url)) {
            Storage::disk('public')->delete($gradeCompany->image_url);
        }

        $gradeCompany->delete();
        return true;
    }

    public function bulkAssign(int $parentGradeId, array $gradeCompanyIds)
    {
        // ✅ G-17: Validasi parent ada
        GradeCompany::findOrFail($parentGradeId);

        // ✅ G-17: Validasi IDs tidak kosong
        if (empty($gradeCompanyIds)) {
            throw new \InvalidArgumentException('Tidak ada grade company yang dipilih.');
        }

        return GradeCompany::whereIn('id', $gradeCompanyIds)->update(['parent_grade_company_id' => $parentGradeId]);
    }

    /**
     * Cek apakah grade company punya stock aktif (net quantity != 0 di ledger).
     * Dipakai buat block perubahan parent_grade_company_id — reassign/unassign
     * grade yang masih ada stock bikin "Global Budgeting" pool (BarangKeluarService)
     * & TrackingStockService::calculateParentGlobalStock() berubah retroaktif
     * antar parent, walau histori transaksinya kejadian di bawah parent lama.
     */
    public function hasActiveStock(int $gradeId): bool
    {
        $net = (float) InventoryTransaction::where('grade_company_id', $gradeId)
            ->whereNull('deleted_at')
            ->sum('quantity_change_grams');

        return round($net, 2) !== 0.0;
    }

    /**
     * Filter list grade company id, kembalikan (nama => stock) untuk yang masih
     * punya stock aktif — dipakai buat pesan error yang jelas ke user.
     */
    public function getGradesWithActiveStock(array $gradeCompanyIds): \Illuminate\Support\Collection
    {
        return GradeCompany::whereIn('id', $gradeCompanyIds)
            ->get()
            ->filter(fn (GradeCompany $g) => $this->hasActiveStock($g->id))
            ->pluck('name');
    }

    public function exportToExcel()
    {
        return Excel::download(new GradeCompanyExport, 'grade-company-' . date('Y-m-d') . '.xlsx');
    }
}
