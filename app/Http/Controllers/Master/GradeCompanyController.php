<?php

namespace App\Http\Controllers\Master;

use App\Exports\GradeCompanyExport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\GradeCompany\GradeCompanyService;
use App\Http\Requests\GradeCompany\GradeCompanyRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class GradeCompanyController extends Controller
{
    protected GradeCompanyService $GradeCompanyService;

    public function __construct(GradeCompanyService $GradeCompanyService)
    {
        $this->GradeCompanyService = $GradeCompanyService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $gradeCompany = $this->GradeCompanyService->getAll($search);

        return view('admin.grade-company.index', compact('gradeCompany', 'search'));
    }

    public function export()
    {
        try {
            return $this->GradeCompanyService->exportToExcel();
        } catch (\Exception $e) {
            Log::error('GradeCompany export error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
            ]);
            return back()->with('error', 'Gagal mengekspor data. Silakan coba lagi.');
        }
    }

    public function create()
    {
        $parentGradeCompanies = \App\Models\ParentGradeCompany::orderBy('name')->get();
        return view('admin.grade-company.create', compact('parentGradeCompanies'));
    }

    public function store(GradeCompanyRequest $request)
    {
        $this->GradeCompanyService->create($request->validated());
        return redirect()->route('grade-company.index')->with('success', 'Grade company berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $gradeCompany = $this->GradeCompanyService->getById($id);
        $parentGradeCompanies = \App\Models\ParentGradeCompany::orderBy('name')->get();
        return view('admin.grade-company.edit', compact('gradeCompany', 'parentGradeCompanies'));
    }

    public function update(GradeCompanyRequest $request, int $id)
    {
        $data = $request->validated();
        $gradeCompany = $this->GradeCompanyService->getById($id);

        $newParentId = empty($data['parent_grade_company_id']) ? null : (int) $data['parent_grade_company_id'];
        $parentChanged = $newParentId !== $gradeCompany->parent_grade_company_id;

        if ($parentChanged && $this->GradeCompanyService->hasActiveStock($id)) {
            return back()->withInput()->with('error',
                "Tidak dapat mengubah parent \"{$gradeCompany->name}\" — grade ini masih punya stock aktif. " .
                "Reassign parent hanya boleh untuk grade dengan stock 0 (histori transaksi terkunci ke parent saat ini)."
            );
        }

        $this->GradeCompanyService->update($id, $data);
        return redirect()->route('grade-company.index')->with('success', 'Grade company berhasil diperbarui.');
    }





    public function destroy(int $id)
    {
        try {
            $this->GradeCompanyService->delete($id);
            return redirect()->route('grade-company.index')->with('success', 'Grade company berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
