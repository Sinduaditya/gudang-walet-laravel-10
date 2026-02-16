<?php

namespace App\Http\Controllers\SortMaterial;

use App\Http\Controllers\Controller;
use App\Models\ParentGradeCompany;
use App\Services\SortMaterial\SortMaterialService;
use Illuminate\Http\Request;

class SortMaterialController extends Controller
{
    protected SortMaterialService $sortMaterialService;

    public function __construct(SortMaterialService $sortMaterialService)
    {
        $this->sortMaterialService = $sortMaterialService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $sortMaterials = $this->sortMaterialService->getAll($search);
        $parentGradeCompanies = \App\Models\ParentGradeCompany::all();
        $gradeCompanies = \App\Models\GradeCompany::all(['id', 'name', 'parent_grade_company_id']);

        return view('admin.sort-materials.index', compact('sortMaterials', 'search', 'parentGradeCompanies', 'gradeCompanies'));
    }

    public function create()
    {
        $parentGradeCompanies = \App\Models\ParentGradeCompany::all();
        $gradeCompanies = \App\Models\GradeCompany::all(['id', 'name', 'parent_grade_company_id']);
        return view('admin.sort-materials.create', compact('parentGradeCompanies', 'gradeCompanies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sort_date' => 'required|date',
            'weight' => 'required|numeric|min:0',
            'parent_grade_company_id' => 'required|exists:parent_grade_companies,id',
            'grade_company_id' => 'nullable|exists:grades_company,id',
            'description' => 'nullable|string',
        ]);

        $this->sortMaterialService->create($request->all());
        return redirect()->route('sort-materials.index')->with('success', 'Data Sortir Bahan berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $sortMaterial = $this->sortMaterialService->getById($id);
        $parentGradeCompanies = \App\Models\ParentGradeCompany::all();
        $gradeCompanies = \App\Models\GradeCompany::all(['id', 'name', 'parent_grade_company_id']);
        return view('admin.sort-materials.edit', compact('sortMaterial', 'parentGradeCompanies', 'gradeCompanies'));
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'sort_date' => 'required|date',
            'weight' => 'required|numeric|min:0',
            'parent_grade_company_id' => 'required|exists:parent_grade_companies,id',
            'grade_company_id' => 'nullable|exists:grades_company,id',
            'description' => 'nullable|string',
        ]);

        $this->sortMaterialService->update($id, $request->all());
        return redirect()->route('sort-materials.index')->with('success', 'Data Sortir Bahan berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $this->sortMaterialService->delete($id);
        return redirect()->route('sort-materials.index')->with('success', 'Data sortir bahan berhasil dihapus.');
    }
}
