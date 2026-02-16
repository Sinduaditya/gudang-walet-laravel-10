<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ParentGradeCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ParentGradeCompanyExport;

class ParentGradeCompanyController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = ParentGradeCompany::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $parentGradeCompanies = $query->latest()->paginate(10)->withQueryString();

        return view('admin.parent-grade-companies.index', compact('parentGradeCompanies', 'search'));
    }

    public function export()
    {
        return Excel::download(new ParentGradeCompanyExport, 'parent-grade-companies-' . date('Y-m-d') . '.xlsx');
    }

    public function create()
    {
        return view('admin.parent-grade-companies.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:parent_grade_companies,name',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'description' => 'nullable|string',
        ]);

        $data = $request->all();

        if ($request->hasFile('image_url')) {
            $data['image_url'] = $request->file('image_url')->store('parent-grade-companies', 'public');
        }

        ParentGradeCompany::create($data);

        return redirect()->route('parent-grade-companies.index')
            ->with('success', 'Parent Grade Company berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $parentGradeCompany = ParentGradeCompany::findOrFail($id);
        return view('admin.parent-grade-companies.edit', compact('parentGradeCompany'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:parent_grade_companies,name,' . $id,
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'description' => 'nullable|string',
        ]);

        $parentGradeCompany = ParentGradeCompany::findOrFail($id);
        $data = $request->all();

        if ($request->hasFile('image_url')) {
            // Delete old image
            if ($parentGradeCompany->image_url && Storage::disk('public')->exists($parentGradeCompany->image_url)) {
                Storage::disk('public')->delete($parentGradeCompany->image_url);
            }
            $data['image_url'] = $request->file('image_url')->store('parent-grade-companies', 'public');
        }

        $parentGradeCompany->update($data);

        return redirect()->route('parent-grade-companies.index')
            ->with('success', 'Parent Grade Company berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $parentGradeCompany = ParentGradeCompany::findOrFail($id);

        if ($parentGradeCompany->image_url && Storage::disk('public')->exists($parentGradeCompany->image_url)) {
            Storage::disk('public')->delete($parentGradeCompany->image_url);
        }

        $parentGradeCompany->delete();

        return redirect()->route('parent-grade-companies.index')
            ->with('success', 'Parent Grade Company berhasil dihapus.');
    }
}
