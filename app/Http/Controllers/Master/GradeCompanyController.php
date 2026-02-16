<?php

namespace App\Http\Controllers\Master;

use App\Exports\GradeCompanyExport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\GradeCompany\GradeCompanyService;
use App\Http\Requests\GradeCompany\GradeCompanyRequest;
use Illuminate\Support\Facades\Storage;
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
        $parentGradeCompanies = \App\Models\ParentGradeCompany::all();

        return view('admin.grade-company.index', compact('gradeCompany', 'search', 'parentGradeCompanies'));
    }

    public function export()
    {
        try {
            return $this->GradeCompanyService->exportToExcel();
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengekspor data: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('admin.grade-company.create');
    }

    public function store(GradeCompanyRequest $request)
    {
        $this->GradeCompanyService->create($request->validated());
        return redirect()->route('grade-company.index')->with('success', 'Grade company berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $gradeCompany = $this->GradeCompanyService->getById($id);
        return view('admin.grade-company.edit', compact('gradeCompany'));
    }

    public function update(GradeCompanyRequest $request, int $id)
    {
        $this->GradeCompanyService->update($id, $request->validated());
        return redirect()->route('grade-company.index')->with('success', 'Grade company berhasil diperbarui.');
    }





    public function destroy(int $id)
    {
        $this->GradeCompanyService->delete($id);
        return redirect()->route('grade-company.index')->with('success', 'Grade company berhasil dihapus.');
    }
}
