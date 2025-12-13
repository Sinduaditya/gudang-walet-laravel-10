<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ManajemenIdmController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = \App\Models\Supplier::all();
        $gradeCompanies = \App\Models\GradeCompany::all();

        $query = \App\Models\IdmManagement::with(['supplier', 'gradeCompany']);

        if ($request->has('supplier_id') && $request->supplier_id != '') {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('grade_company_id') && $request->grade_company_id != '') {
            $query->where('grade_company_id', $request->grade_company_id);
        }

        if ($request->has('category_grade') && $request->category_grade != '') {
            $query->whereHas('sourceItems', function ($q) use ($request) {
                $q->where('category_grade', $request->category_grade);
            });
        }

        $idmManagements = $query->latest()->paginate(10);

        return view('admin.manajemen-idm.index', compact('idmManagements', 'suppliers', 'gradeCompanies'));
    }

    public function create(Request $request)
    {
        $suppliers = \App\Models\Supplier::all();
        
        // Default category to IDM A if not specified
        $category = $request->input('category', 'IDM A');

        $query = \App\Models\SortingResult::with(['receiptItem.purchaseReceipt.supplier', 'gradeCompany'])
            ->where('category_grade', $category)
            ->whereNull('idm_management_id');

        // Filter Date
        if ($request->has('from_date') && $request->from_date != '') {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date != '') {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Filter Supplier
        if ($request->has('supplier_id') && $request->supplier_id != '') {
            $query->whereHas('receiptItem.purchaseReceipt', function($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            });
        }

        // Filter Barang (Search)
        if ($request->has('search') && $request->search != '') {
            $query->whereHas('gradeCompany', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $items = $query->latest()->paginate(12);

        return view('admin.manajemen-idm.create', compact('items', 'suppliers', 'category'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'selected_items' => 'required|array',
            'selected_items.*' => 'exists:sorting_results,id',
        ]);

        // Redirect to Step 2 with selected items
        return redirect()->route('manajemen-idm.step2', ['items' => $request->selected_items]);
    }

    public function createStep2(Request $request)
    {
        $itemIds = $request->query('items');
        
        if (!$itemIds || !is_array($itemIds)) {
            return redirect()->route('manajemen-idm.create')->with('error', 'Silakan pilih item terlebih dahulu.');
        }

        // Fetch selected items (SortingResult)
        $items = \App\Models\SortingResult::with(['receiptItem.purchaseReceipt.supplier', 'gradeCompany'])
            ->whereIn('id', $itemIds)
            ->get();

        if ($items->isEmpty()) {
            return redirect()->route('manajemen-idm.create')->with('error', 'Item tidak ditemukan.');
        }

        $firstItem = $items->first();
        $totalWeight = $items->sum('weight_grams');
        
        // Prepare data for view
        $data = [
            'items' => $items,
            'firstItem' => $firstItem,
            'totalWeight' => $totalWeight,
            'itemIds' => $itemIds,
        ];

        return view('admin.manajemen-idm.step2', $data);
    }

    public function storeStep2(Request $request)
    {
        $request->validate([
            'item_ids' => 'required|array',
            'total_weight' => 'required|numeric',
            'initial_price' => 'required|numeric',
            'shrinkage' => 'required|numeric',
            'details' => 'required|array',
            'details.perutan.weight' => 'required|numeric',
            'details.perutan.price' => 'required|numeric',
            'details.kakian.weight' => 'required|numeric',
            'details.kakian.price' => 'required|numeric',
            'details.idm.weight' => 'required|numeric',
            'details.idm.price' => 'required|numeric',
            'estimated_selling_price' => 'required|numeric',
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Fetch items to get supplier and grade info
            $items = \App\Models\SortingResult::whereIn('id', $request->item_ids)->get();
            $firstItem = $items->first();

            // Create IdmManagement record
            $idmManagement = \App\Models\IdmManagement::create([
                'supplier_id' => $firstItem->receiptItem->purchaseReceipt->supplier_id,
                'grade_company_id' => $firstItem->grade_company_id,
                'initial_weight' => $request->total_weight,
                'shrinkage' => $request->shrinkage,
                'initial_price' => $request->initial_price,
                'estimated_selling_price' => $request->estimated_selling_price,
                'grading_date' => now(),
            ]);

            // Create Details
            foreach ($request->details as $type => $detail) {
                \App\Models\IdmDetail::create([
                    'idm_management_id' => $idmManagement->id,
                    'grade_idm_name' => $type,
                    'weight' => $detail['weight'],
                    'price' => $detail['price'],
                    'total_price' => $detail['weight'] * $detail['price'],
                ]);
            }

            // Update SortingResult items to link them to this IdmManagement
            \App\Models\SortingResult::whereIn('id', $request->item_ids)
                ->update(['idm_management_id' => $idmManagement->id]);

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('manajemen-idm.index')->with('success', 'Data estimasi IDM berhasil disimpan.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $idmManagement = \App\Models\IdmManagement::with(['supplier', 'gradeCompany', 'details', 'sourceItems'])->findOrFail($id);
        return view('admin.manajemen-idm.edit', compact('idmManagement'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'total_weight' => 'required|numeric',
            'initial_price' => 'required|numeric',
            'shrinkage' => 'required|numeric',
            'details' => 'required|array',
            'details.perutan.weight' => 'required|numeric',
            'details.perutan.price' => 'required|numeric',
            'details.kakian.weight' => 'required|numeric',
            'details.kakian.price' => 'required|numeric',
            'details.idm.weight' => 'required|numeric',
            'details.idm.price' => 'required|numeric',
            'estimated_selling_price' => 'required|numeric',
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $idmManagement = \App\Models\IdmManagement::findOrFail($id);

            // Update IdmManagement record
            $idmManagement->update([
                'initial_weight' => $request->total_weight,
                'shrinkage' => $request->shrinkage,
                'initial_price' => $request->initial_price,
                'estimated_selling_price' => $request->estimated_selling_price,
            ]);

            // Update Details (Delete and Recreate)
            $idmManagement->details()->delete();

            foreach ($request->details as $type => $detail) {
                \App\Models\IdmDetail::create([
                    'idm_management_id' => $idmManagement->id,
                    'grade_idm_name' => $type,
                    'weight' => $detail['weight'],
                    'price' => $detail['price'],
                    'total_price' => $detail['weight'] * $detail['price'],
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('manajemen-idm.index')->with('success', 'Data estimasi IDM berhasil diperbarui.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $idmManagement = \App\Models\IdmManagement::with(['supplier', 'gradeCompany', 'details'])->findOrFail($id);
        return view('admin.manajemen-idm.show', compact('idmManagement'));
    }
    public function destroy($id)
    {
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $idmManagement = \App\Models\IdmManagement::findOrFail($id);

            // Revert SortingResult items (set idm_management_id to null)
            \App\Models\SortingResult::where('idm_management_id', $idmManagement->id)
                ->update(['idm_management_id' => null]);

            // Delete the record
            $idmManagement->delete();

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('manajemen-idm.index')->with('success', 'Data estimasi IDM berhasil dihapus.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
