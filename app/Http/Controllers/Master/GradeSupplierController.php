<?php

namespace App\Http\Controllers\Master;

use App\Http\Requests\GradeSupplier\GradeSupplierRequest;
use App\Services\GradeSupplier\GradeSupplierService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GradeSupplierController extends Controller
{
    protected $service;

    public function __construct(GradeSupplierService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $grades = $this->service->getAll($search);

        return view('admin.grade-supplier.index', compact('grades', 'search'));
    }

    public function create()
    {
        return view('admin.grade-supplier.create');
    }

    public function store(GradeSupplierRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->route('grade-supplier.index')
            ->with('success', 'Grade Supplier berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $gradeSupplier = $this->service->getById($id);
        return view('admin.grade-supplier.edit', compact('gradeSupplier'));
    }

    public function update(GradeSupplierRequest $request, $id)
    {
        $this->service->update($id, $request->validated());

        return redirect()->route('grade-supplier.index')
            ->with('success', 'Grade Supplier berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $this->service->delete($id);

        return redirect()->route('grade-supplier.index')
            ->with('success', 'Grade Supplier berhasil dihapus.');
    }
}
