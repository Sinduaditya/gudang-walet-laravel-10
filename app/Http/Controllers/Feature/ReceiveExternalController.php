<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Location;
use App\Models\GradeCompany;
use App\Services\BarangKeluar\BarangKeluarService;
use Illuminate\Http\Request;

class ReceiveExternalController extends Controller
{
    protected BarangKeluarService $service;

    public function __construct(BarangKeluarService $service)
    {
        $this->service = $service;
    }

    /**
     * Step 1: Form terima barang eksternal (dari Jasa Cuci)
     */
    public function receiveExternalStep1(Request $request)
    {
        $grades = GradeCompany::all();
        $suppliers = \App\Models\Supplier::all();
        
        $locations = Location::where('name', 'NOT LIKE', '%IDM%')
            ->where('name', 'NOT LIKE', '%DMK%')
            ->where('name', 'NOT LIKE', '%Gudang Utama%')
            ->get();

        $query = InventoryTransaction::where('transaction_type', 'RECEIVE_EXTERNAL_IN')
            ->with([
                'gradeCompany', 
                'location', 
                'stockTransfer.fromLocation',
                'sortingResult.receiptItem.purchaseReceipt.supplier'
            ])
            ->whereHas('stockTransfer.fromLocation', function($q) {
                $q->where('name', 'NOT LIKE', '%IDM%')
                  ->where('name', 'NOT LIKE', '%DMK%');
            });

        if ($request->filled('grade_id')) {
            $query->where('grade_company_id', $request->grade_id);
        }

        if ($request->filled('supplier_id')) {
            $query->whereHas('sortingResult.receiptItem.purchaseReceipt.supplier', function($q) use ($request) {
                $q->where('id', $request->supplier_id);
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        $receiveExternalTransactions = $query->latest('transaction_date')
            ->latest('id')
            ->paginate(10);

        return view('admin.barang-keluar.receive-external-step1', compact(
            'grades',
            'suppliers',
            'locations',
            'receiveExternalTransactions'
        ));
    }

    /**
     * AJAX endpoint untuk cek stok yang dikirim ke jasa cuci
     */
    public function checkExternalStock(Request $request)
    {
        $gradeCompanyId = $request->get('grade_company_id');

        if (!$gradeCompanyId) {
            return response()->json([
                'success' => false,
                'message' => 'Grade harus dipilih'
            ]);
        }

        // Get all relevant external locations (Jasa Cuci)
        $query = Location::where('name', 'NOT LIKE', '%IDM%')
            ->where('name', 'NOT LIKE', '%DMK%')
            ->where('name', 'NOT LIKE', '%Gudang Utama%');

        if ($request->filled('from_location_id')) {
            $query->where('id', $request->from_location_id);
        }

        $locations = $query->get();

        $locationData = [];
        $totalPending = 0;

        foreach ($locations as $location) {
            $sent = $this->getSentStockToLocation($gradeCompanyId, $location->id);
            $received = $this->getReceivedStockFromLocation($gradeCompanyId, $location->id);
            $pending = abs($sent) - $received; // Ensure sent is positive

            // Only include if there is pending stock or if it's the requested location (if we were filtering, but we return all)
            if ($pending > 0.01) { // Use small threshold for float comparison
                $locationData[] = [
                    'location_id' => $location->id,
                    'location_name' => $location->name,
                    'stock_grams' => $pending,
                    'stock_kg' => number_format($pending / 1000, 2, ',', '.'),
                    'formatted_stock' => number_format($pending, 0, ',', '.') . ' gr',
                ];
                $totalPending += $pending;
            }
        }

        $grade = GradeCompany::find($gradeCompanyId);

        return response()->json([
            'success' => true,
            'grade_name' => $grade ? $grade->name : 'Unknown',
            'total_stock_grams' => $totalPending,
            'total_stock_kg' => number_format($totalPending / 1000, 2, ',', '.'),
            'formatted_total_stock' => number_format($totalPending, 0, ',', '.') . ' gr',
            'has_stock' => $totalPending > 0,
            'locations' => $locationData,
        ]);
    }

    /**
     * Get total stok yang pernah dikirim ke lokasi external
     */
    private function getSentStockToLocation(int $gradeCompanyId, int $locationId): float
    {
        return \App\Models\StockTransfer::where('grade_company_id', $gradeCompanyId)
            ->where('to_location_id', $locationId)
            ->whereHas('transactions', function($q) {
                $q->where('transaction_type', 'EXTERNAL_TRANSFER_OUT');
            })
            ->sum('weight_grams');
    }

    /**
     * Get total stok yang sudah diterima dari lokasi external (Berat + Susut)
     */
    private function getReceivedStockFromLocation(int $gradeCompanyId, int $locationId): float
    {
        // Kita harus menghitung total pengurangan stok pending, yaitu:
        // Berat yang diterima (masuk ke gudang) + Susut (hilang)
        // Data ini ada di tabel stock_transfers karena InventoryTransaction RECEIVE_EXTERNAL_IN hanya mencatat berat bersih yang masuk.
        
        $transfers = \App\Models\StockTransfer::where('grade_company_id', $gradeCompanyId)
            ->where('from_location_id', $locationId)
            ->whereHas('transactions', function($q) {
                $q->where('transaction_type', 'RECEIVE_EXTERNAL_IN');
            })
            ->get();

        $totalReceived = 0;
        foreach ($transfers as $transfer) {
            $totalReceived += $transfer->weight_grams + ($transfer->susut_grams ?? 0);
        }

        return $totalReceived;
    }

    /**
     * Store Step 1 data to session
     */
    public function storeReceiveExternalStep1(Request $request)
    {
        $validated = $request->validate([
            'grade_company_id' => 'required|exists:grades_company,id',
            'from_location_id' => 'required|exists:locations,id',
            'weight_grams' => 'required|numeric|min:0.01',
            'susut_grams' => 'nullable|numeric|min:0',
            'transfer_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ], [
            'grade_company_id.required' => 'Grade harus dipilih',
            'from_location_id.required' => 'Lokasi asal harus dipilih',
            'weight_grams.required' => 'Berat harus diisi',
            'weight_grams.min' => 'Berat minimal 0.01 gram',
        ]);

        $sentStock = abs($this->getSentStockToLocation($validated['grade_company_id'], $validated['from_location_id']));
        $receivedStock = $this->getReceivedStockFromLocation($validated['grade_company_id'], $validated['from_location_id']);
        $pendingStock = $sentStock - $receivedStock;

        // Calculate total weight to be deducted from pending stock (received weight + shrinkage)
        $totalDeduction = $validated['weight_grams'] + ($validated['susut_grams'] ?? 0);

        if ($totalDeduction > $pendingStock) {
            return back()
                ->withInput()
                ->withErrors([
                    'weight_grams' => "Total berat (Diterima + Susut) melebihi stok yang pending. Maksimal: " . number_format($pendingStock, 2) . " gram"
                ]);
        }

        // Set to_location_id ke Gudang Utama
        $gudangUtama = Location::where('name', 'Gudang Utama')->first();
        $validated['to_location_id'] = $gudangUtama->id;

        // ✅ AUTO-POPULATE sorting_result_id dari transfer terakhir ke lokasi ini
        // Kita cari transfer terakhir yang dikirim KE lokasi ini (to_location_id = from_location_id kita sekarang)
        // dengan grade yang sama.
        $lastTransfer = \App\Models\StockTransfer::where('grade_company_id', $validated['grade_company_id'])
            ->where('to_location_id', $validated['from_location_id']) // Kita terima DARI lokasi yang dulu kita kirim KE sana
            ->whereNotNull('sorting_result_id')
            ->latest('transfer_date')
            ->latest('id')
            ->first();

        if ($lastTransfer) {
            $validated['sorting_result_id'] = $lastTransfer->sorting_result_id;
        }

        $request->session()->put('receive_external_step1', $validated);

        return redirect()->route('barang.keluar.receive-external.step2');
    }

    /**
     * Step 2 - Confirmation
     */
    public function receiveExternalStep2()
    {
        $step1Data = session('receive_external_step1');

        if (!$step1Data) {
            return redirect()->route('barang.keluar.receive-external.step1')
                ->with('error', 'Silakan lengkapi data terlebih dahulu');
        }

        $grade = GradeCompany::findOrFail($step1Data['grade_company_id']);
        $fromLocation = Location::findOrFail($step1Data['from_location_id']);
        $toLocation = Location::findOrFail($step1Data['to_location_id']);

        return view('admin.barang-keluar.receive-external-step2', compact(
            'step1Data',
            'grade',
            'fromLocation',
            'toLocation'
        ));
    }

    /**
     * Process receive external
     */
    /**
     * Process receive external (Direct submission from Step 1)
     */
    public function receiveExternal(Request $request)
    {
        $validated = $request->validate([
            'grade_company_id' => 'required|exists:grades_company,id',
            'from_location_id' => 'required|exists:locations,id',
            'weight_grams' => 'required|numeric|min:0.01',
            'susut_grams' => 'nullable|numeric|min:0',
            'transfer_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ], [
            'grade_company_id.required' => 'Grade harus dipilih',
            'from_location_id.required' => 'Lokasi asal harus dipilih',
            'weight_grams.required' => 'Berat harus diisi',
            'weight_grams.min' => 'Berat minimal 0.01 gram',
        ]);

        $sentStock = abs($this->getSentStockToLocation($validated['grade_company_id'], $validated['from_location_id']));
        $receivedStock = $this->getReceivedStockFromLocation($validated['grade_company_id'], $validated['from_location_id']);
        $pendingStock = $sentStock - $receivedStock;

        // Calculate total weight to be deducted from pending stock (received weight + shrinkage)
        $totalDeduction = $validated['weight_grams'] + ($validated['susut_grams'] ?? 0);

        if ($totalDeduction > $pendingStock) {
            return back()
                ->withInput()
                ->withErrors([
                    'weight_grams' => "Total berat (Diterima + Susut) melebihi stok yang pending. Maksimal: " . number_format($pendingStock, 2) . " gram"
                ]);
        }

        // Set to_location_id ke Gudang Utama
        $gudangUtama = Location::where('name', 'Gudang Utama')->first();
        $validated['to_location_id'] = $gudangUtama->id;

        // ✅ AUTO-POPULATE sorting_result_id dari transfer terakhir ke lokasi ini
        $lastTransfer = \App\Models\StockTransfer::where('grade_company_id', $validated['grade_company_id'])
            ->where('to_location_id', $validated['from_location_id'])
            ->whereNotNull('sorting_result_id')
            ->latest('transfer_date')
            ->latest('id')
            ->first();

        if ($lastTransfer) {
            $validated['sorting_result_id'] = $lastTransfer->sorting_result_id;
        }

        $this->service->receiveExternal($validated);

        return redirect()
            ->route('barang.keluar.receive-external.step1')
            ->with('success', 'Penerimaan barang eksternal berhasil dicatat dan stok diperbarui.');
    }

    public function edit($id)
    {
        $transfer = \App\Models\StockTransfer::findOrFail($id);
        $grades = \App\Models\GradeCompany::all();
        $locations = \App\Models\Location::where('name', 'NOT LIKE', '%IDM%')
            ->where('name', 'NOT LIKE', '%DMK%')
            ->where('name', 'NOT LIKE', '%Gudang Utama%')
            ->get();

        // Calculate pending stock for validation display
        $sentStock = abs($this->getSentStockToLocation($transfer->grade_company_id, $transfer->from_location_id));
        $receivedStock = $this->getReceivedStockFromLocation($transfer->grade_company_id, $transfer->from_location_id);
        // We subtract the current transaction's weight/shrinkage from receivedStock to simulate "before this transaction" state
        $currentDeduction = $transfer->weight_grams + ($transfer->susut_grams ?? 0);
        $receivedStock -= $currentDeduction;
        
        $pendingStock = $sentStock - $receivedStock;

        return view('admin.barang-keluar.receive-external-edit', compact('transfer', 'grades', 'locations', 'pendingStock'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'grade_company_id' => 'required|exists:grades_company,id',
            'from_location_id' => 'required|exists:locations,id',
            'weight_grams' => 'required|numeric|min:0.01',
            'susut_grams' => 'nullable|numeric|min:0',
            'transfer_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $sentStock = abs($this->getSentStockToLocation($validated['grade_company_id'], $validated['from_location_id']));
        $receivedStock = $this->getReceivedStockFromLocation($validated['grade_company_id'], $validated['from_location_id']);
        
        // If editing the same transaction, we need to exclude its contribution to receivedStock
        $oldTransfer = \App\Models\StockTransfer::findOrFail($id);
        if ($oldTransfer->grade_company_id == $validated['grade_company_id'] && 
            $oldTransfer->from_location_id == $validated['from_location_id']) {
            $receivedStock -= ($oldTransfer->weight_grams + ($oldTransfer->susut_grams ?? 0));
        }

        $pendingStock = $sentStock - $receivedStock;
        $totalDeduction = $validated['weight_grams'] + ($validated['susut_grams'] ?? 0);

        if ($totalDeduction > $pendingStock) {
            return back()->with('error', "Total berat (Diterima + Susut) melebihi stok yang pending. Maksimal: " . number_format($pendingStock, 2) . " gram");
        }

        $gudangUtama = \App\Models\Location::where('name', 'Gudang Utama')->first();
        $validated['to_location_id'] = $gudangUtama->id;

        $this->service->updateReceiveExternal($id, $validated);

        return redirect()->route('barang.keluar.receive-external.step1')
            ->with('success', 'Penerimaan barang eksternal berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $transfer = \App\Models\StockTransfer::findOrFail($id);
        $transfer->transactions()->delete();
        $transfer->delete();

        return redirect()->route('barang.keluar.receive-external.step1')
            ->with('success', 'Penerimaan eksternal berhasil dihapus.');
    }
}