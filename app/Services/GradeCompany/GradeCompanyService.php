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
        return GradeCompany::create($data);
    }

    public function update(int $id, array $data)
    {
        $gradeCompany = $this->getById($id);
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

    public function exportToExcel(){
        return Excel::download(new GradeCompanyExport, 'grade-company-' . date('Y-m-d') . '.xlsx');
    }
}
