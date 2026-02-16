<?php

namespace App\Services\GradeCompany;

use App\Exports\GradeCompanyExport;
use App\Models\GradeCompany;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class GradeCompanyService
{
    public function getAll(?string $search = null)
    {
        $query = GradeCompany::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
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

        if ($gradeCompany->image_url && Storage::disk('public')->exists($gradeCompany->image_url)) {
            Storage::disk('public')->delete($gradeCompany->image_url);
        }

        $gradeCompany->delete();
        return true;
    }

    public function bulkAssign(int $parentGradeId, array $gradeCompanyIds)
    {
        return GradeCompany::whereIn('id', $gradeCompanyIds)->update(['parent_grade_company_id' => $parentGradeId]);
    }

    public function exportToExcel()
    {
        return Excel::download(new GradeCompanyExport, 'grade-company-' . date('Y-m-d') . '.xlsx');
    }
}
