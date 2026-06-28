<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\GradeCompany;
use App\Services\Idm\ManajemenIdmService;
use Illuminate\Http\Request;

class ManajemenIdmController extends Controller
{
    public function __construct(private ManajemenIdmService $service) {}

    public function index(Request $request)
    {
        $suppliers      = Supplier::all();
        $gradeCompanies = GradeCompany::all();
        $idmManagements = $this->service->getAll($request->all());

        return view('admin.manajemen-idm.index', compact('idmManagements', 'suppliers', 'gradeCompanies'));
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::all();
        $category  = $request->input('category', 'IDM A');
        $items     = $this->service->getAvailableItems($category, $request->all());

        return view('admin.manajemen-idm.create', compact('items', 'suppliers', 'category'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'selected_items'   => 'required|array',
            'selected_items.*' => 'exists:sorting_results,id',
        ]);

        return redirect()->route('manajemen-idm.step2', ['items' => $request->selected_items]);
    }

    public function createStep2(Request $request)
    {
        $itemIds = $request->query('items');

        if (!$itemIds || !is_array($itemIds)) {
            return redirect()->route('manajemen-idm.create')->with('error', 'Silakan pilih item terlebih dahulu.');
        }

        $items = $this->service->getItemsByIds($itemIds);

        if ($items->isEmpty()) {
            return redirect()->route('manajemen-idm.create')->with('error', 'Item tidak ditemukan.');
        }

        $firstItem   = $items->first();
        $totalWeight = $items->sum('weight_grams');

        return view('admin.manajemen-idm.step2', compact('items', 'firstItem', 'totalWeight', 'itemIds'));
    }

    public function storeStep2(Request $request)
    {
        $request->validate([
            'item_ids'                    => 'required|array',
            'details'                     => 'required|array',
            'details.perutan.weight'      => 'required|numeric|min:0',
            'details.kakian.weight'       => 'required|numeric|min:0',
            'details.idm.weight'          => 'required|numeric|min:0',
            'grade_company_id'            => 'required|exists:grades_company,id',
        ]);

        try {
            $idmManagement = $this->service->create($request->item_ids, $request->only(['details', 'grade_company_id']));

            return redirect()->route('manajemen-idm.index')->with('success', 'Data IDM berhasil disimpan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function edit(Request $request, $id)
    {
        $idmManagement = $this->service->find($id);

        $page             = $request->get('page');
        $supplier_id      = $request->get('supplier_id');
        $grade_company_id = $request->get('grade_company_id');
        $category_grade   = $request->get('category_grade');

        return view('admin.manajemen-idm.edit', compact(
            'idmManagement', 'page', 'supplier_id', 'grade_company_id', 'category_grade'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'details'                => 'required|array',
            'details.perutan.weight' => 'required|numeric|min:0',
            'details.kakian.weight'  => 'required|numeric|min:0',
            'details.idm.weight'     => 'required|numeric|min:0',
        ]);

        try {
            $this->service->update($id, $request->only('details'));

            $redirectParams = array_filter([
                'page'             => $request->get('page'),
                'supplier_id'      => $request->get('supplier_id'),
                'grade_company_id' => $request->get('grade_company_id'),
                'category_grade'   => $request->get('category_grade'),
            ]);

            return redirect()->route('manajemen-idm.show', array_merge(['id' => $id], $redirectParams))
                ->with('success', 'Data IDM berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        $idmManagement    = $this->service->find($id);
        $page             = $request->get('page');
        $supplier_id      = $request->get('supplier_id');
        $grade_company_id = $request->get('grade_company_id');
        $category_grade   = $request->get('category_grade');

        return view('admin.manajemen-idm.show', compact(
            'idmManagement', 'page', 'supplier_id', 'grade_company_id', 'category_grade'
        ));
    }

    public function destroy($id)
    {
        try {
            $this->service->delete($id);

            return redirect()->route('manajemen-idm.index')->with('success', 'Data IDM berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
