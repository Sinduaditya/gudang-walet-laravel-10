<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Location;
use App\Models\GradeCompany;
use App\Services\BarangKeluar\BarangKeluarService;
use Illuminate\Http\Request;

class ReceiveInternalController extends Controller
{
    protected BarangKeluarService $service;

    public function __construct(BarangKeluarService $service)
    {
        $this->service = $service;
    }

    /**
     * Step 1: Form terima barang internal (dari IDM/DMK)
     */
    public function receiveInternalStep1(Request $request)
    {
        $grades = GradeCompany::all();
        
        $locations = Location::where('name', 'LIKE', '%IDM%')
            ->orWhere('name', 'LIKE', '%DMK%')
            ->get();

        // Riwayat penerimaan internal
        $query = InventoryTransaction::where('transaction_type', 'RECEIVE_INTERNAL_IN')
            ->with(['gradeCompany', 'location', 'stockTransfer.fromLocation'])
            ->whereHas('stockTransfer.fromLocation', function($q) {
                $q->where('name', 'LIKE', '%IDM%')
                  ->orWhere('name', 'LIKE', '%DMK%');
            });

        if ($request->filled('grade_id')) {
            $query->where('grade_company_id', $request->grade_id);
        }

        $receiveInternalTransactions = $query->latest('transaction_date')
            ->latest('id')
            ->paginate(10);

        return view('admin.barang-keluar.receive-internal-step1', compact(
            'grades',
            'locations',
            'receiveInternalTransactions'
        ));
    }

    /**
     * AJAX endpoint untuk cek stok per grade di lokasi internal
     */
    public function checkInternalStock(Request $request)
    {
        $gradeCompanyId = $request->get('grade_company_id');
        $fromLocationId = $request->get('from_location_id');

        if (!$gradeCompanyId) {
            return response()->json([
                'success' => false,
                'message' => 'Grade company ID diperlukan'
            ]);
        }

        // ✅ Get semua lokasi internal jika from_location_id tidak specified
        if ($fromLocationId) {
            $locations = Location::where('id', $fromLocationId)->get();
        } else {
            $locations = Location::where('name', 'LIKE', '%IDM%')
                ->orWhere('name', 'LIKE', '%DMK%')
                ->get();
        }

        $stockData = [];
        $totalStock = 0;

        foreach ($locations as $location) {
            $stock = $this->service->getAvailableStock($gradeCompanyId, $location->id);
            
            if ($stock > 0) {
                $stockData[] = [
                    'location_id' => $location->id,
                    'location_name' => $location->name,
                    'stock_grams' => $stock,
                    'stock_kg' => round($stock / 1000, 2),
                    'formatted_stock' => number_format($stock, 0, ',', '.') . ' gr'
                ];
                $totalStock += $stock;
            }
        }

        $grade = GradeCompany::find($gradeCompanyId);

        return response()->json([
            'success' => true,
            'grade_name' => $grade ? $grade->name : 'Unknown',
            'total_stock_grams' => $totalStock,
            'total_stock_kg' => round($totalStock / 1000, 2),
            'formatted_total_stock' => number_format($totalStock, 0, ',', '.') . ' gr',
            'locations' => $stockData,
            'has_stock' => $totalStock > 0
        ]);
    }

    /**
     * Store Step 1 data to session
     */
    public function storeReceiveInternalStep1(Request $request)
    {
        $validated = $request->validate([
            'grade_company_id' => 'required|exists:grades_company,id',
            'from_location_id' => 'required|exists:locations,id',
            'weight_grams' => 'required|numeric|min:0.01',
            'transfer_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ], [
            'grade_company_id.required' => 'Grade harus dipilih',
            'from_location_id.required' => 'Lokasi asal harus dipilih',
            'weight_grams.required' => 'Berat harus diisi',
        ]);

        $availableStock = $this->service->getAvailableStock(
            $validated['grade_company_id'], 
            $validated['from_location_id']
        );

        if ($availableStock < $validated['weight_grams']) {
            return back()
                ->withInput()
                ->withErrors([
                    'weight_grams' => "Stok tidak mencukupi. Tersedia: " . number_format($availableStock, 2) . " gram"
                ]);
        }

        $gudangUtama = Location::where('name', 'Gudang Utama')->first();
        $validated['to_location_id'] = $gudangUtama->id;

        $request->session()->put('receive_internal_step1', $validated);

        return redirect()->route('barang.keluar.receive-internal.step2');
    }

    /**
     * Step 2 - Confirmation
     */
    public function receiveInternalStep2()
    {
        $step1Data = session('receive_internal_step1');

        if (!$step1Data) {
            return redirect()->route('barang.keluar.receive-internal.step1')
                ->with('error', 'Silakan lengkapi data terlebih dahulu');
        }

        $grade = GradeCompany::findOrFail($step1Data['grade_company_id']);
        $fromLocation = Location::findOrFail($step1Data['from_location_id']);
        $toLocation = Location::findOrFail($step1Data['to_location_id']);

        return view('admin.barang-keluar.receive-internal-step2', compact(
            'step1Data',
            'grade',
            'fromLocation',
            'toLocation'
        ));
    }

    /**
     * Process receive internal
     */
    public function receiveInternal(Request $request)
    {
        $step1Data = session('receive_internal_step1');
        if (!$step1Data) {
            return redirect()->route('barang.keluar.receive-internal.step1')
                ->with('error', 'Data tidak ditemukan');
        }

        $this->service->receiveInternal($step1Data);

        session()->forget('receive_internal_step1');

        return redirect()
            ->route('barang.keluar.receive-internal.step1')
            ->with('success', 'Penerimaan barang internal berhasil dicatat dan stok diperbarui.');
    }
}