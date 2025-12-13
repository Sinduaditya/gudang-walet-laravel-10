<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Location;
use App\Models\GradeCompany;
use App\Services\BarangKeluar\BarangKeluarService;
use App\Http\Requests\BarangKeluar\TransferRequest;
use App\Models\StockTransfer;
use Illuminate\Http\Request;

class TransferInternalController extends Controller
{
    protected BarangKeluarService $service;

    public function __construct(BarangKeluarService $service)
    {
        $this->service = $service;
    }

    /**
     * Step 1: Form transfer internal + riwayat
     */
    public function transferStep1(Request $request)
    {
        $gudangUtama = Location::where('name', 'Gudang Utama')->first();
        if (!$gudangUtama) {
            return redirect()->back()->with('error', 'Lokasi "Gudang Utama" tidak ditemukan.');
        }

        // Fetch Grading Sources for "Internal"
        $gradingSources = $this->service->getGradingSources(\App\Models\SortingResult::OUTGOING_TYPE_INTERNAL);

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

        $dmkLocation = Location::where('name', 'DMK')->first();

        // Fetch Suppliers and Grades for filters
        $suppliers = \App\Models\Supplier::all();
        $grades = \App\Models\GradeCompany::all();

        // History Transactions (Internal Transfer Out)
        $query = StockTransfer::whereHas('transactions', function ($q) {
                $q->where('transaction_type', 'TRANSFER_OUT');
            })
            ->with(['gradeCompany', 'fromLocation', 'toLocation', 'sortingResult.receiptItem.purchaseReceipt.supplier'])
            ->orderBy('transfer_date', 'desc');

        // Apply Filters
        if ($request->filled('start_date')) {
            $query->whereDate('transfer_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('transfer_date', '<=', $request->end_date);
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
                return $group->sum('weight_grams');
            });

        $transferInternalTransactions = $query->paginate(10)->withQueryString();

        return view('admin.barang-keluar.transfer-step1', compact(
            'gradesWithStock', 
            'dmkLocation', 
            'transferInternalTransactions', 
            'gudangUtama',
            'suppliers',
            'grades',
            'summary'
        ));
    }

    /**
     * Store Transfer Step 1 data to session
     */
    public function storeTransferStep1(Request $request)
    {
        $validated = $request->validate([
            'grade_company_id' => 'required|exists:sorting_results,id', // Validate against sorting_results
            'from_location_id' => 'required|exists:locations,id',
            'to_location_id' => 'required|exists:locations,id|different:from_location_id',
            'weight_grams' => 'required|numeric|min:0.01',
            'susut_grams' => 'nullable|numeric|min:0',
            'transfer_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ], [
            'grade_company_id.required' => 'Grade harus dipilih',
            'grade_company_id.exists' => 'Batch tidak valid',
            'from_location_id.required' => 'Lokasi asal harus dipilih',
            'from_location_id.exists' => 'Lokasi asal tidak valid',
            'to_location_id.required' => 'Lokasi tujuan harus dipilih',
            'to_location_id.exists' => 'Lokasi tujuan tidak valid',
            'to_location_id.different' => 'Lokasi tujuan harus berbeda dengan lokasi asal',
            'weight_grams.required' => 'Berat harus diisi',
            'weight_grams.min' => 'Berat minimal 0.01 gram',
            'transfer_date.date' => 'Format tanggal tidak valid',
            'notes.max' => 'Catatan maksimal 500 karakter',
        ]);

        // PENTING: Paksa from_location_id selalu Gudang Utama untuk keamanan
        $gudangUtama = Location::where('name', 'Gudang Utama')->first()
                    ?? Location::where('id', 1)->first();

        if ($gudangUtama) {
            $validated['from_location_id'] = $gudangUtama->id;
        }

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

        // Store in session
        $request->session()->put('transfer_step1', $validated);

        return redirect()->route('barang.keluar.transfer.step2');
    }

    /**
     * Transfer Step 2 - Show confirmation page
     */
    public function transferStep2()
    {
        $step1Data = session('transfer_step1');

        // If no step 1 data, redirect back to step 1
        if (!$step1Data) {
            return redirect()->route('barang.keluar.transfer.step1')
                ->with('error', 'Silakan lengkapi data transfer terlebih dahulu');
        }

        // Get related models for display
        // Resolve Grade from SortingResult (since ID is now SortingResult ID)
        $sortingResult = \App\Models\SortingResult::findOrFail($step1Data['grade_company_id']);
        $grade = $sortingResult->gradeCompany;
        
        $fromLocation = Location::findOrFail($step1Data['from_location_id']);
        $toLocation = Location::findOrFail($step1Data['to_location_id']);

        return view('admin.barang-keluar.transfer-step2', compact(
            'step1Data',
            'grade',
            'fromLocation',
            'toLocation'
        ));
    }

    /**
     * Process transfer (final submission)
     */
    public function transfer(TransferRequest $request)
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

        $this->service->transfer($data);

        // Clear session after successful transfer
        session()->forget('transfer_step1');

        return redirect()
            ->route('barang.keluar.transfer.step1')
            ->with('success', 'Transfer internal berhasil dicatat dan stok diperbarui.');
    }

    public function checkStock(Request $request)
    {
        $sortingResultId = (int) $request->query('grade_company_id');
        
        if (!$sortingResultId) {
            return response()->json(['ok' => false, 'message' => 'Batch required'], 400);
        }

        $gudangUtama = Location::where('name', 'Gudang Utama')->first();
        $locationId = $gudangUtama ? $gudangUtama->id : 1;

        $available = $this->service->getBatchRemainingStock($sortingResultId, $locationId);
        return response()->json(['ok' => true, 'available_grams' => (float)$available]);
    }

    public function edit($id)
    {
        $transfer = \App\Models\StockTransfer::findOrFail($id);
        $grades = \App\Models\GradeCompany::orderBy('name')->get();
        $locations = \App\Models\Location::where('name', 'NOT LIKE', '%Jasa Cuci%')
            ->where('name', '!=', 'Gudang Utama')
            ->orderBy('name')
            ->get();
        
        // Get stock for the current location and grade to show available
        $availableStock = $this->service->getAvailableStock($transfer->grade_company_id, $transfer->from_location_id);
        // Add back the current transfer weight because we are editing it
        $availableStock += $transfer->weight_grams + ($transfer->susut_grams ?? 0);

        return view('admin.barang-keluar.transfer-edit', compact('transfer', 'grades', 'locations', 'availableStock'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'grade_company_id' => 'required|exists:grades_company,id',
            'from_location_id' => 'required|exists:locations,id',
            'to_location_id' => 'required|exists:locations,id|different:from_location_id',
            'weight_grams' => 'required|numeric|min:0.01',
            'susut_grams' => 'nullable|numeric|min:0',
            'transfer_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check stock availability (excluding current transaction)
        $totalWeight = $validated['weight_grams'] + ($validated['susut_grams'] ?? 0);
        $availableStock = $this->service->getAvailableStock($validated['grade_company_id'], $validated['from_location_id']);
        
        // If editing same location/grade, we need to add back the old weight to available stock check
        $oldTransfer = \App\Models\StockTransfer::findOrFail($id);
        if ($oldTransfer->grade_company_id == $validated['grade_company_id'] && 
            $oldTransfer->from_location_id == $validated['from_location_id']) {
            $availableStock += $oldTransfer->weight_grams + ($oldTransfer->susut_grams ?? 0);
        }

        if ($availableStock < $totalWeight) {
             return back()->with('error', "Stok tidak mencukupi! Dibutuhkan: " . number_format($totalWeight, 2) . " gr. Tersedia: " . number_format($availableStock, 2) . " gr.");
        }

        $this->service->updateTransferInternal($id, $validated);

        return redirect()->route('barang.keluar.transfer.step1')
            ->with('success', 'Transfer internal berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $transfer = \App\Models\StockTransfer::findOrFail($id);

        // Delete associated inventory transactions
        $transfer->transactions()->delete();

        // Delete the transfer record
        $transfer->delete();

        return redirect()->route('barang.keluar.transfer.step1')
            ->with('success', 'Transfer internal berhasil dihapus dan stok dikembalikan.');
    }
}
