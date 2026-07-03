<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\GradeCompany;
use App\Models\ParentGradeCompany;
use App\Services\GradeCompany\GradeCompanyService;
use Illuminate\Http\Request;

class BulkAssignmentController extends Controller
{
    protected GradeCompanyService $gradeCompanyService;

    public function __construct(GradeCompanyService $gradeCompanyService)
    {
        $this->gradeCompanyService = $gradeCompanyService;
    }

    /**
     * Display a listing of Parent Grade Companies and their assigned Grade Companies count.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = ParentGradeCompany::withCount('gradeCompanies');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $parentGradeCompanies = $query->latest()->paginate(10)->withQueryString();

        return view('admin.grade-company.bulk-assignment.index', compact('parentGradeCompanies', 'search'));
    }

    /**
     * Show the form for creating a new assignment (Assign unassigned grades to a parent).
     */
    public function create(Request $request)
    {
        // Semua grade company, termasuk yang sudah punya parent (bisa direassign)
        $gradeCompanies = GradeCompany::with('parentGradeCompany')
            ->orderBy('name')
            ->get();

        $parentGradeCompanies = ParentGradeCompany::orderBy('name')->get();

        return view('admin.grade-company.bulk-assignment.create', compact('gradeCompanies', 'parentGradeCompanies'));
    }

    /**
     * Store a newly created assignment in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'parent_grade_company_id' => 'required|exists:parent_grade_companies,id',
            'grade_company_ids' => 'required|array',
            'grade_company_ids.*' => 'exists:grades_company,id',
        ]);

        $blocked = $this->gradeCompanyService->getGradesWithActiveStock($request->grade_company_ids);
        if ($blocked->isNotEmpty()) {
            return redirect()->back()->withInput()->with('error',
                'Tidak dapat assign/reassign — grade berikut masih punya stock aktif: ' . $blocked->implode(', ') . '.'
            );
        }

        GradeCompany::whereIn('id', $request->grade_company_ids)
            ->update(['parent_grade_company_id' => $request->parent_grade_company_id]);

        return redirect()->route('bulk-assignments.index')
            ->with('success', 'Grade company berhasil di-assign ke parent.');
    }

    /**
     * Display the specified resource (List of grades assigned to a parent).
     */
    public function show(string $id)
    {
        $parentGradeCompany = ParentGradeCompany::with('gradeCompanies')->findOrFail($id);
        return view('admin.grade-company.bulk-assignment.show', compact('parentGradeCompany'));
    }

    /**
     * Show the form for editing the specified resource (Manage assignments).
     */
    public function edit(string $id, Request $request)
    {
        $parentGradeCompany = ParentGradeCompany::findOrFail($id);

        // Grades currently assigned to this parent
        $assignedGrades = $parentGradeCompany->gradeCompanies()->orderBy('name')->get();

        // Grades available untuk di-assign: yang belum punya parent ATAU masih punya parent lain
        // (assign otomatis pindahkan dari parent lama, tanpa perlu unassign manual dulu)
        $availableGrades = GradeCompany::where(function ($q) use ($id) {
                $q->where('parent_grade_company_id', '!=', $id)
                    ->orWhereNull('parent_grade_company_id');
            })
            ->with('parentGradeCompany')
            ->orderBy('name')
            ->get();

        return view('admin.grade-company.bulk-assignment.edit', compact('parentGradeCompany', 'assignedGrades', 'availableGrades'));
    }

    /**
     * Update the specified resource in storage.
     * Use this to Unassign grades or Assign new ones from Edit page.
     */
    public function update(Request $request, string $id)
    {
        ParentGradeCompany::findOrFail($id);

        $blockedNames = collect();

        if ($request->filled('unassign_ids')) {
            $blockedNames = $blockedNames->merge(
                $this->gradeCompanyService->getGradesWithActiveStock($request->unassign_ids)
            );
        }
        if ($request->filled('assign_ids')) {
            $blockedNames = $blockedNames->merge(
                $this->gradeCompanyService->getGradesWithActiveStock($request->assign_ids)
            );
        }

        if ($blockedNames->isNotEmpty()) {
            return redirect()->back()->with('error',
                'Tidak dapat assign/unassign — grade berikut masih punya stock aktif: ' . $blockedNames->unique()->implode(', ') . '.'
            );
        }

        $messages = [];

        if ($request->filled('unassign_ids')) {
            GradeCompany::whereIn('id', $request->unassign_ids)
                ->where('parent_grade_company_id', $id)
                ->update(['parent_grade_company_id' => null]);
            $messages[] = 'di-unassign';
        }

        if ($request->filled('assign_ids')) {
            GradeCompany::whereIn('id', $request->assign_ids)
                ->update(['parent_grade_company_id' => $id]);
            $messages[] = 'ditambahkan';
        }

        if (empty($messages)) {
            return redirect()->back()->with('error', 'Tidak ada grade company yang dipilih.');
        }

        return redirect()->back()->with('success', 'Grade company berhasil ' . implode(' & ', $messages) . '.');
    }

    /**
     * Remove the specified resource from storage.
     * Not used in this context really, or could be used to unassign ALL.
     */
    public function destroy(ParentGradeCompany $parentGradeCompany)
    {
        $childIds = GradeCompany::where('parent_grade_company_id', $parentGradeCompany->id)->pluck('id')->all();

        $blocked = $this->gradeCompanyService->getGradesWithActiveStock($childIds);
        if ($blocked->isNotEmpty()) {
            return redirect()->route('bulk-assignments.index')->with('error',
                'Tidak dapat unassign semua — grade berikut masih punya stock aktif: ' . $blocked->implode(', ') . '.'
            );
        }

        GradeCompany::where('parent_grade_company_id', $parentGradeCompany->id)
            ->update(['parent_grade_company_id' => null]);

        return redirect()->route('bulk-assignments.index')
            ->with('success', 'Semua Grade company berhasil di-unassign dari parent ini.');
    }
}
