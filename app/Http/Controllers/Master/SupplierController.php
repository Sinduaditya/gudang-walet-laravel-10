<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Services\Supplier\SupplierService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    protected $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $suppliers = $this->supplierService->getAll($search);
        return view('admin.suppliers.index', compact('suppliers', 'search'));
    }

    public function export(){
        try {
            return $this->supplierService->exportToExcel();
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengekspor data: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('admin.suppliers.create');
    }

    public function store(Request $request)
    {
        $supplier = $this->supplierService->create($request->all());
        return redirect()->route('suppliers.index')->with('success', 'Data supplier berhasil ditambahkan');
    }

    public function edit($id)
    {
        $supplier = $this->supplierService->getById($id);
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, $id)
    {
        $this->supplierService->update($id, $request->all());
        return redirect()->route('suppliers.index')->with('success', 'Data supplier berhasil diperbarui');
    }

    public function destroy($id)
    {
        $this->supplierService->delete($id);
        return redirect()->route('suppliers.index')->with('success', 'Data supplier berhasil dihapus');
    }
}
