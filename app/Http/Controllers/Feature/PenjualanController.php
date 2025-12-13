<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\GradeCompany;
use App\Models\Location;
use App\Services\BarangKeluar\BarangKeluarService;
use App\Http\Requests\BarangKeluar\SellRequest;
use Illuminate\Http\Request;

class PenjualanController extends Controller
{
    protected BarangKeluarService $service;

    public function __construct(BarangKeluarService $service)
    {
        $this->service = $service;
    }

    /**
     * Tampilkan form penjualan + riwayat penjualan
     */
    public function sellForm(Request $request)
    {
        $defaultLocation = Location::where('name', 'Gudang Utama')->first();
        if (!$defaultLocation) {
            return redirect()->back()->with('error', 'Lokasi "Gudang Utama" tidak ditemukan.');
        }

        // Fetch Grading Sources for "Penjualan Langsung"
        $gradingSources = $this->service->getGradingSources(\App\Models\SortingResult::OUTGOING_TYPE_PENJUALAN_LANGSUNG);

        // Map sources to view format
        // Note: We use Batch Stock Logic here to ensure specific batch selection
        $gradesWithStock = $gradingSources->map(function ($source) use ($defaultLocation) {
            // Calculate remaining stock for this specific batch
            $batchRemaining = $this->service->getBatchRemainingStock($source->id, $defaultLocation->id);
            
            return [
                'id' => $source->id, // Use SortingResult ID
                'name' => $source->gradeCompany->name ?? 'Unknown',
                'supplier_name' => $source->receiptItem->purchaseReceipt->supplier->name ?? 'Unknown',
                'supplier_id' => $source->receiptItem->purchaseReceipt->supplier_id ?? null,
                'grading_date' => $source->grading_date ? $source->grading_date->format('d M Y') : '-',
                'batch_stock_grams' => $batchRemaining,
                'total_stock_grams' => $batchRemaining,
            ];
        })->filter(function ($item) {
            return $item['batch_stock_grams'] > 0;
        });

        // Fetch Suppliers and Grades for filters
        $suppliers = \App\Models\Supplier::all();
        $grades = \App\Models\GradeCompany::all();

        $query = InventoryTransaction::where('transaction_type', 'SALE_OUT')
            ->with(['gradeCompany', 'location', 'sortingResult.receiptItem.purchaseReceipt.supplier'])
            ->orderBy('transaction_date', 'desc');

        // Apply Filters
        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }
        if ($request->filled('supplier_id')) {
            $query->whereHas('sortingResult.receiptItem.purchaseReceipt', function ($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            });
        }
        if ($request->filled('grade_company_id')) {
            $query->where('grade_company_id', $request->grade_company_id);
        }

        // Calculate Summary (Total Weight per Grade)
        // Clone query to avoid modifying the pagination query
        $summaryQuery = clone $query;
        $summary = $summaryQuery->get()
            ->groupBy('gradeCompany.name')
            ->map(function ($group) {
                return $group->sum(function ($tx) {
                    return abs($tx->quantity_change_grams);
                });
            });

        $penjualanTransactions = $query->paginate(10)->withQueryString();

        return view('admin.barang-keluar.sell', compact(
            'gradesWithStock', 
            'penjualanTransactions', 
            'defaultLocation',
            'suppliers',
            'grades',
            'summary'
        ));
    }

    public function checkStock(Request $request)
    {
        // This is now checking BATCH stock because grade_company_id param is actually sorting_result_id
        $sortingResultId = (int) $request->query('grade_company_id');
        
        if (!$sortingResultId) {
            return response()->json(['ok' => false, 'message' => 'Batch required'], 400);
        }

        $defaultLocation = Location::where('name', 'Gudang Utama')->first();
        $locationId = $defaultLocation ? $defaultLocation->id : 1;

        $available = $this->service->getBatchRemainingStock($sortingResultId, $locationId);
        return response()->json(['ok' => true, 'available_grams' => (float)$available]);
    }

    /**
     * Store sale
     */
    public function sell(SellRequest $request)
    {
        $defaultLocation = Location::where('name', 'Gudang Utama')->first();
        
        $data = $request->validated();
        
        // Resolve SortingResult and GradeCompany
        $sortingResult = \App\Models\SortingResult::findOrFail($data['grade_company_id']);
        $data['sorting_result_id'] = $sortingResult->id;
        $data['grade_company_id'] = $sortingResult->grade_company_id;
        $data['location_id'] = $defaultLocation->id;

        // Check BATCH stock
        $batchRemaining = $this->service->getBatchRemainingStock($data['sorting_result_id'], $data['location_id']);
        
        if ($batchRemaining < $data['weight_grams']) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Stok batch tidak mencukupi. Tersedia: ' . $batchRemaining . ' gr.');
        }

        $this->service->sell($data);

        return redirect()->route('barang.keluar.sell.form')
            ->with('success', 'Penjualan berhasil dicatat dan stok diperbarui.');
    }

    public function edit($id)
    {
        $tx = InventoryTransaction::findOrFail($id);
        return view('admin.barang-keluar.sell-edit', compact('tx'));
    }

    public function update(\Illuminate\Http\Request $request, $id)
    {
        $tx = InventoryTransaction::findOrFail($id);
        $request->validate(['weight_grams' => 'required|numeric|min:0.01']);
        $tx->update(['quantity_change_grams' => -abs($request->input('weight_grams'))]);
        return redirect()->route('barang.keluar.sell.form')->with('success', 'Transaksi diperbarui.');
    }

    public function destroy($id)
    {
        $tx = InventoryTransaction::findOrFail($id);
        $tx->delete();
        return redirect()->route('barang.keluar.sell.form')->with('success', 'Transaksi penjualan dihapus.');
    }
}