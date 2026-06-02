<?php

namespace App\Http\Controllers\SortMaterial;

use App\Http\Controllers\Controller;
use App\Services\SortMaterial\SortMaterialService;
use App\Exports\SortMaterialExport;
use Maatwebsite\Excel\Facades\Excel;
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
        
        // Singkronkan ulang stok cache parent secara otomatis agar selalu 100% akurat
        $this->sortMaterialService->recalculateAllParentStocks();
        
        $sortMaterials = $this->sortMaterialService->getAll($search);

        return view('admin.sort-materials.index', compact('sortMaterials', 'search'));
    }

    public function create()
    {
        $parentGradeCompanies = \App\Models\ParentGradeCompany::orderBy('name')->get();
        $gradeCompanies       = \App\Models\GradeCompany::orderBy('name')->get(['id', 'name', 'parent_grade_company_id']);

        return view('admin.sort-materials.create', compact(
            'parentGradeCompanies',
            'gradeCompanies',
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sort_date'               => 'required|date',
            'weight'                  => 'required|numeric|min:0.01',
            'parent_grade_company_id' => 'required|exists:parent_grade_companies,id',
            'grade_company_id'        => 'nullable|exists:grades_company,id',
            'description'             => 'nullable|string',
            'destination'             => 'nullable|string',
        ]);

        try {
            $this->sortMaterialService->create($request->only([
                'sort_date', 'weight', 'parent_grade_company_id',
                'grade_company_id', 'description', 'destination',
            ]));
            return redirect()->route('sort-materials.index')
                ->with('success', 'Data Sortir Bahan berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function showGradingForm()
    {
        // Hanya ambil parent grade company yang memiliki stok sortir > 0
        $parentGradeCompanies = \App\Models\ParentGradeCompany::where('stock', '>', 0)->orderBy('name')->get();
        // Ambil semua detail grade company untuk dropdown target pecahan
        $gradeCompanies       = \App\Models\GradeCompany::orderBy('name')->get(['id', 'name', 'parent_grade_company_id']);
        // Dan semua parent grade untuk dropdown target pecahan
        $allParentGrades      = \App\Models\ParentGradeCompany::orderBy('name')->get();

        return view('admin.sort-materials.grading', compact(
            'parentGradeCompanies',
            'gradeCompanies',
            'allParentGrades'
        ));
    }

    public function processGrading(Request $request)
    {
        $request->validate([
            'process_date'                    => 'required|date',
            'source_parent_grade_company_id'  => 'required|exists:parent_grade_companies,id',
            'total_weight'                    => 'required|numeric|min:0.01',
            'targets'                         => 'required|array|min:1',
            'targets.*.parent_grade_company_id'=> 'required|exists:parent_grade_companies,id',
            'targets.*.grade_company_id'      => 'nullable|exists:grades_company,id',
            'targets.*.weight'                => 'required|numeric|min:0.01',
        ]);

        try {
            $this->sortMaterialService->processInternalGrading($request->all());
            return redirect()->route('sort-materials.index')
                ->with('success', 'Aktivitas grading internal sortir berhasil diproses.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->sortMaterialService->delete($id);
            return redirect()->route('sort-materials.index')
                ->with('success', 'Data sortir bahan berhasil dihapus dan stok dikembalikan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        try {
            $search   = $request->input('search');
            $fileName = 'sortir_bahan_' . date('Y-m-d') . '.xlsx';
            return Excel::download(new SortMaterialExport($search), $fileName);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SortMaterialController export error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat export data.');
        }
    }
}
