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
        $grades = $this->sortMaterialService->getGradesWithGlobalStock();
        $destinations = $this->sortMaterialService->getDestinations();

        return view('admin.sort-materials.index', compact('sortMaterials', 'search', 'parentGradeCompanies', 'gradeCompanies', 'grades', 'destinations'));
    }

    public function create()
    {
        $parentGradeCompanies = \App\Models\ParentGradeCompany::all();
        $gradeCompanies = \App\Models\GradeCompany::all(['id', 'name', 'parent_grade_company_id']);
        $destinations = $this->sortMaterialService->getDestinations();

        // ALU grades dengan global stock
        $aluGrades = $this->sortMaterialService->getGradesWithGlobalStock()->filter(function($g) {
            return $g->parentGradeCompany && $g->parentGradeCompany->name === 'ALU';
        })->values();

        // Map parent grade id ke nama untuk JS
        $parentGradeNames = [];
        foreach ($parentGradeCompanies as $pg) {
            $parentGradeNames[$pg->id] = $pg->name;
        }

        return view('admin.sort-materials.create', compact('parentGradeCompanies', 'gradeCompanies', 'aluGrades', 'parentGradeNames', 'destinations'));
    }

    public function store(Request $request)
    {
        $rules = [
            'sort_date' => 'required|date',
            'weight' => 'required|numeric|min:0',
            'parent_grade_company_id' => 'required|exists:parent_grade_companies,id',
            'grade_company_id' => 'nullable|exists:grades_company,id',
            'description' => 'nullable|string',
            'alu_action' => 'nullable|string',
            'destination' => 'nullable|string',
        ];

        $request->validate($rules);

        $parentGrade = \App\Models\ParentGradeCompany::find($request->parent_grade_company_id);

        // If ALU, use global sorting logic
        if ($parentGrade && $parentGrade->name === 'ALU') {
            try {
                $aluAction = $request->input('alu_action');

                // Check if it's a penjualan action (from the hidden field)
                if ($aluAction === 'penjualan') {
                    $data = [
                        'grade_company_id' => $request->grade_company_id,
                        'weight_grams' => $request->weight,
                        'sort_date' => $request->sort_date,
                    ];
                    $this->sortMaterialService->processPenjualanLangsung($data);
                    return redirect()->route('sort-materials.index')->with('success', 'Penjualan langsung ALU berhasil.');
                }

                // Default: masuk stok
                $data = [
                    'grade_company_id' => $request->grade_company_id,
                    'weight_grams' => $request->weight,
                    'destination' => $request->input('destination'),
                    'sort_date' => $request->sort_date,
                ];
                $this->sortMaterialService->processSortirMasukStok($data);
                return redirect()->route('sort-materials.index')->with('success', 'Data Sortir ALU berhasil ditambahkan.');
            } catch (\Exception $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        // Non-ALU: use existing logic
        $this->sortMaterialService->create($request->all());
        return redirect()->route('sort-materials.index')->with('success', 'Data Sortir Bahan berhasil ditambahkan.');
    }

    public function destroy(int $id)
    {
        $this->sortMaterialService->delete($id);
        return redirect()->route('sort-materials.index')->with('success', 'Data sortir bahan berhasil dihapus.');
    }

    public function storeGlobal(Request $request)
    {
        $request->validate([
            'grade_company_id' => 'required|exists:grades_company,id',
            'weight_grams' => 'required|numeric|min:1',
            'action' => 'required|in:masuk_stok,penjualan',
            'destination' => 'nullable|string',
            'sort_date' => 'nullable|date',
        ]);

        try {
            $data = [
                'grade_company_id' => $request->input('grade_company_id'),
                'weight_grams' => $request->input('weight_grams'),
                'destination' => $request->input('destination'),
                'sort_date' => $request->input('sort_date', now()),
            ];

            if ($request->input('action') === 'masuk_stok') {
                $result = $this->sortMaterialService->processSortirMasukStok($data);
                return redirect()->back()->with('success', 'Berhasil memproses masuk stok.');
            } else {
                $result = $this->sortMaterialService->processPenjualanLangsung($data);
                return redirect()->back()->with('success', 'Berhasil memproses penjualan langsung.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
