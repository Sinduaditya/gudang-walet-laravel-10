<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Location;
use App\Models\GradeCompany;
use App\Services\BarangKeluar\BarangKeluarService;
use App\Http\Requests\BarangKeluar\ExternalTransferRequest;
use Illuminate\Http\Request;

class TransferExternalController extends Controller
{
    protected BarangKeluarService $service;

    public function __construct(BarangKeluarService $service)
    {
        $this->service = $service;
    }

    /**
     * Step 1: Form transfer external + riwayat
     */
    public function externalTransferStep1(Request $request)
    {
        $gudangUtama = Location::where('name', 'Gudang Utama')->first();
        if (!$gudangUtama) {
            return redirect()->back()->with('error', 'Lokasi "Gudang Utama" tidak ditemukan.');
        }

        // Fetch Grading Sources for "External"
        $gradingSources = $this->service->getGradingSources(\App\Models\SortingResult::OUTGOING_TYPE_EXTERNAL);

        $gradesWithStock = $gradingSources->map(function ($source) use ($gudangUtama) {
            // Calculate remaining stock for this specific batch
            $batchRemaining = $this->service->getBatchRemainingStock($source->id, $gudangUtama->id);
            
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

        $jasaCuciLocations = Location::where('name', 'NOT LIKE', '%IDM%')
            ->where('name', 'NOT LIKE', '%DMK%')
            ->where('name', '!=', 'Gudang Utama')
            ->orderBy('name')
            ->get();

        // Fetch Suppliers and Grades for filters
        $suppliers = \App\Models\Supplier::all();
        $grades = \App\Models\GradeCompany::all();

        $query = InventoryTransaction::where('transaction_type', 'EXTERNAL_TRANSFER_OUT')
            ->with(['gradeCompany', 'location', 'stockTransfer.fromLocation', 'stockTransfer.toLocation', 'sortingResult.receiptItem.purchaseReceipt.supplier'])
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
        $summaryQuery = clone $query;
        $summary = $summaryQuery->get()
            ->groupBy('gradeCompany.name')
            ->map(function ($group) {
                return $group->sum(function ($tx) {
                    return abs($tx->quantity_change_grams);
                });
            });

        $transferExternalTransactions = $query->paginate(10)->withQueryString();

        return view('admin.barang-keluar.external-transfer-step1', compact(
            'gradesWithStock', 
            'jasaCuciLocations', 
            'transferExternalTransactions', 
            'gudangUtama',
            'suppliers',
            'grades',
            'summary'
        ));
    }

    /**
     * Store External Transfer Step 1 data to session
     */
    public function storeExternalTransferStep1(Request $request)
    {
        $validated = $request->validate([
            'grade_company_id' => 'required|exists:sorting_results,id', // Validate against sorting_results
            'to_location_id' => 'required|exists:locations,id',
            'weight_grams' => 'required|numeric|min:0.01',
            'susut_grams' => 'nullable|numeric|min:0',
            'transfer_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ], [
            'grade_company_id.required' => 'Grade harus dipilih',
            'grade_company_id.exists' => 'Batch tidak valid',
            'to_location_id.required' => 'Lokasi tujuan (Jasa Cuci) harus dipilih',
            'weight_grams.required' => 'Berat harus diisi',
            'weight_grams.min' => 'Berat minimal 0.01 gram',
        ]);

        $gudangUtama = Location::where('name', 'Gudang Utama')->first();
        $validated['from_location_id'] = $gudangUtama->id;

        // Resolve SortingResult
        $sortingResult = \App\Models\SortingResult::findOrFail($validated['grade_company_id']);
        $validated['sorting_result_id'] = $sortingResult->id;
        // $validated['grade_company_id'] = $sortingResult->grade_company_id; // REMOVED: Keep as SortingResult ID for Step 2 and 3 validation

        // Calculate total weight to be deducted (transfer weight + shrinkage)
        $totalWeight = $validated['weight_grams'] + ($validated['susut_grams'] ?? 0);

        // Check BATCH stock
        $batchRemaining = $this->service->getBatchRemainingStock($validated['sorting_result_id'], $validated['from_location_id']);

        if ($batchRemaining < $totalWeight) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Stok batch tidak mencukupi! Dibutuhkan: " . number_format($totalWeight, 2) . " gr. Tersedia: " . number_format($batchRemaining, 2) . " gr.");
        }

        $request->session()->put('external_transfer_step1', $validated);

        return redirect()->route('barang.keluar.external-transfer.step2');
    }

    /**
     * External Transfer Step 2 - Show confirmation
     */
    public function externalTransferStep2()
    {
        $step1Data = session('external_transfer_step1');

        if (!$step1Data) {
            return redirect()->route('barang.keluar.external-transfer.step1')
                ->with('error', 'Silakan lengkapi data transfer terlebih dahulu');
        }

        // Resolve Grade from SortingResult (since ID is now SortingResult ID)
        $sortingResult = \App\Models\SortingResult::findOrFail($step1Data['grade_company_id']);
        $grade = $sortingResult->gradeCompany;
        
        $fromLocation = Location::findOrFail($step1Data['from_location_id']);
        $toLocation = Location::findOrFail($step1Data['to_location_id']);

        return view('admin.barang-keluar.external-transfer-step2', compact(
            'step1Data',
            'grade',
            'fromLocation',
            'toLocation'
        ));
    }

    /**
     * Process external transfer
     */
    public function externalTransfer(ExternalTransferRequest $request)
    {
        $data = $request->validated();

        // Resolve SortingResult and GradeCompany
        $sortingResult = \App\Models\SortingResult::findOrFail($data['grade_company_id']);
        $data['sorting_result_id'] = $sortingResult->id;
        $data['grade_company_id'] = $sortingResult->grade_company_id;

        // Check BATCH stock (Server-side Validation)
        $totalWeight = $data['weight_grams'] + ($data['susut_grams'] ?? 0);
        $batchRemaining = $this->service->getBatchRemainingStock($data['sorting_result_id'], $data['from_location_id']);

        if ($batchRemaining < $totalWeight) {
             return back()->with('error', "Stok batch tidak mencukupi! Dibutuhkan: " . number_format($totalWeight, 2) . " gr. Tersedia: " . number_format($batchRemaining, 2) . " gr.");
        }

        $this->service->externalTransfer($data);

        session()->forget('external_transfer_step1');

        return redirect()
            ->route('barang.keluar.external-transfer.step1')
            ->with('success', 'Transfer eksternal berhasil dicatat dan stok diperbarui.');
    }

    public function edit($id)
    {
        $transfer = \App\Models\StockTransfer::findOrFail($id);
        $grades = \App\Models\GradeCompany::orderBy('name')->get();
        $jasaCuciLocations = \App\Models\Location::where('name', 'NOT LIKE', '%IDM%')
            ->where('name', 'NOT LIKE', '%DMK%')
            ->where('name', '!=', 'Gudang Utama')
            ->orderBy('name')
            ->get();
        
        $gudangUtama = \App\Models\Location::where('name', 'Gudang Utama')->first();
        
        $availableStock = $this->service->getAvailableStock($transfer->grade_company_id, $gudangUtama->id);
        $availableStock += $transfer->weight_grams + ($transfer->susut_grams ?? 0);

        return view('admin.barang-keluar.external-transfer-edit', compact('transfer', 'grades', 'jasaCuciLocations', 'availableStock'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'grade_company_id' => 'required|exists:grades_company,id',
            'to_location_id' => 'required|exists:locations,id',
            'weight_grams' => 'required|numeric|min:0.01',
            'susut_grams' => 'nullable|numeric|min:0',
            'transfer_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $gudangUtama = \App\Models\Location::where('name', 'Gudang Utama')->first();
        $validated['from_location_id'] = $gudangUtama->id;

        $totalWeight = $validated['weight_grams'] + ($validated['susut_grams'] ?? 0);
        $availableStock = $this->service->getAvailableStock($validated['grade_company_id'], $validated['from_location_id']);
        
        $oldTransfer = \App\Models\StockTransfer::findOrFail($id);
        if ($oldTransfer->grade_company_id == $validated['grade_company_id']) {
            $availableStock += $oldTransfer->weight_grams + ($oldTransfer->susut_grams ?? 0);
        }

        if ($availableStock < $totalWeight) {
             return back()->with('error', "Stok di Gudang Utama tidak mencukupi! Dibutuhkan: " . number_format($totalWeight, 2) . " gr. Tersedia: " . number_format($availableStock, 2) . " gr.");
        }

        $this->service->updateExternalTransfer($id, $validated);

        return redirect()->route('barang.keluar.external-transfer.step1')
            ->with('success', 'Transfer eksternal berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $transfer = \App\Models\StockTransfer::findOrFail($id);
        $transfer->transactions()->delete();
        $transfer->delete();

        return redirect()->route('barang.keluar.external-transfer.step1')
            ->with('success', 'Transfer eksternal berhasil dihapus.');
    }
}
