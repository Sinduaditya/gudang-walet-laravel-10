<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\GradeCompany;
use App\Models\ParentGradeCompany;
use Illuminate\Http\Request;

class BulkAssignmentController extends Controller
{
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
        // Fetch only GradeCompanies that do NOT have a parent
        $gradeCompanies = GradeCompany::whereNull('parent_grade_company_id')
            ->orderBy('name')
            ->get();

        $parentGradeCompanies = ParentGradeCompany::all();

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

        // Grades available (unassigned) for search/add
        $availableGrades = GradeCompany::whereNull('parent_grade_company_id')
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
        $parentGradeCompany = ParentGradeCompany::findOrFail($id);

        if ($request->has('unassign_ids')) {
            GradeCompany::whereIn('id', $request->unassign_ids)
                ->where('parent_grade_company_id', $id)
                ->update(['parent_grade_company_id' => null]);
            return redirect()->back()->with('success', 'Grade company berhasil di-unassign.');
        }

        if ($request->has('assign_ids')) {
            GradeCompany::whereIn('id', $request->assign_ids)
                ->whereNull('parent_grade_company_id')
                ->update(['parent_grade_company_id' => $id]);
            return redirect()->back()->with('success', 'Grade company berhasil ditambahkan.');
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     * Not used in this context really, or could be used to unassign ALL.
     */
    public function destroy(ParentGradeCompany $parentGradeCompany)
    {
        // Maybe unassign all?
        GradeCompany::where('parent_grade_company_id', $parentGradeCompany->id)
            ->update(['parent_grade_company_id' => null]);

        return redirect()->route('bulk-assignments.index')
            ->with('success', 'Semua Grade company berhasil di-unassign dari parent ini.');
    }
}
