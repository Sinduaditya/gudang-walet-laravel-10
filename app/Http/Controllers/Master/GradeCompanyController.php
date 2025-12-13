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

        return view('admin.grade-company.index', compact('gradeCompany', 'search'));
    }

    public function export(){
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
        $data = $request->validated();

        if ($request->hasFile('image_url')) {
            $path = $request->file('image_url')->store('grade-company', 'public');
            $data['image_url'] = $path;
        }

        $this->GradeCompanyService->create($data);
        return redirect()->route('grade-company.index')->with('success', 'Grade company berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $gradeCompany = $this->GradeCompanyService->getById($id);
        return view('admin.grade-company.edit', compact('gradeCompany'));
    }

    public function update(GradeCompanyRequest $request, int $id)
    {
        $data = $request->validated();
        $gradeCompany = $this->GradeCompanyService->getById($id);

        if ($request->hasFile('image_url')) {
            if ($gradeCompany->image_url && Storage::disk('public')->exists($gradeCompany->image_url)) {
                Storage::disk('public')->delete($gradeCompany->image_url);
            }

            $path = $request->file('image_url')->store('grade-company', 'public');
            $data['image_url'] = $path;
        }

        $this->GradeCompanyService->update($id, $data);
        return redirect()->route('grade-company.index')->with('success', 'Grade company berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $this->GradeCompanyService->delete($id);
        return redirect()->route('grade-company.index')->with('success', 'Grade company berhasil dihapus.');
    }
}
