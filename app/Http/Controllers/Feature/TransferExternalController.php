<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Location;
use App\Models\GradeCompany;
use App\Services\BarangKeluar\BarangKeluarService;
use App\Http\Requests\BarangKeluar\ExternalTransferRequest;
use App\Exports\TransferExternalExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        try {
            $gudangUtama = Location::where('name', 'Gudang Utama')->first();
            if (!$gudangUtama) {
                return redirect()->back()->with('error', 'Lokasi "Gudang Utama" tidak ditemukan.');
            }

            // Ambil sumber grading dengan logika "Global Budgeting"
            $gradingSources = $this->service->getGradingSourcesWithStock(\App\Models\SortingResult::OUTGOING_TYPE_EXTERNAL, $gudangUtama->id);

            $gradesWithStock = $gradingSources->map(function ($source) {
                return [
                    'id' => $source->id,
                    'name' => $source->gradeCompany->name ?? 'Unknown',
                    'supplier_name' => $source->receiptItem->purchaseReceipt->supplier->name ?? 'Unknown',
                    'supplier_id' => $source->receiptItem->purchaseReceipt->supplier_id ?? null,
                    'grading_date' => $source->grading_date ? $source->grading_date->format('d M Y') : '-',
                    'batch_stock_grams' => $source->adjusted_weight,
                    'total_stock_grams' => $source->real_global_stock,
                ];
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
        } catch (\Throwable $e) {
            Log::error('Error in externalTransferStep1: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data transfer. Silakan coba lagi.');
        }
    }

    /**
     * Store External Transfer Step 1 data to session
     */
    public function storeExternalTransferStep1(Request $request)
    {
        try {
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

            // 1. Cek stok BATCH (Link ke sorting_result)
            $batchRemaining = $this->service->getBatchRemainingStock($validated['sorting_result_id'], $validated['from_location_id']);
            if ($batchRemaining < $totalWeight) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "Stok batch tidak mencukupi atau sudah habis terpakai transaksi lain! Tersedia: " . number_format($batchRemaining, 2) . " gr.");
            }

            // 2. Cek stok NYATA di Gudang (Total Grade di lokasi tersebut)
            if (!$this->service->hasEnoughStock($sortingResult->grade_company_id, $validated['from_location_id'], $totalWeight)) {
                $realStock = $this->service->getAvailableStock($sortingResult->grade_company_id, $validated['from_location_id']);
                return redirect()->back()
                    ->withInput()
                    ->with('error', "GAGAL: Stok fisik Grade " . $sortingResult->gradeCompany->name . " di gudang tidak mencukupi! Stok Nyata: " . number_format($realStock, 2) . " gr. (Dibutuhkan: " . number_format($totalWeight, 2) . " gr)");
            }

            $request->session()->put('external_transfer_step1', $validated);

            return redirect()->route('barang.keluar.external-transfer.step2');
        } catch (\Throwable $e) {
            Log::error('Error in storeExternalTransferStep1: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan data transfer. Silakan coba lagi.');
        }
    }

    /**
     * External Transfer Step 2 - Show confirmation
     */
    public function externalTransferStep2()
    {
        try {
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
        } catch (\Throwable $e) {
            Log::error('Error in externalTransferStep2: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('barang.keluar.external-transfer.step1')
                ->with('error', 'Terjadi kesalahan saat memuat halaman konfirmasi. Silakan coba lagi.');
        }
    }

    /**
     * Process external transfer
     */
    public function externalTransfer(ExternalTransferRequest $request)
    {
        try {
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

            // 2. Cek stok NYATA di Gudang (Total Grade di lokasi tersebut)
            if (!$this->service->hasEnoughStock($sortingResult->grade_company_id, $data['from_location_id'], $totalWeight)) {
                $realStock = $this->service->getAvailableStock($sortingResult->grade_company_id, $data['from_location_id']);
                return back()->with('error', "GAGAL: Stok fisik Grade di gudang tidak mencukupi secara global! Stok Nyata: " . number_format($realStock, 2) . " gr. (Dibutuhkan: " . number_format($totalWeight, 2) . " gr)");
            }

            $this->service->externalTransfer($data);

            session()->forget('external_transfer_step1');

            return redirect()
                ->route('barang.keluar.external-transfer.step1')
                ->with('success', 'Transfer eksternal berhasil dicatat dan stok diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Error in externalTransfer: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memproses transfer. Silakan coba lagi.');
        }
    }

    public function destroy($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $transfer = \App\Models\StockTransfer::lockForUpdate()->findOrFail($id);
                $userId = auth()->id();
                
                $totalDeduction = abs($transfer->weight_grams) + abs($transfer->susut_grams ?? 0);
                
                $outTx = $transfer->transactions()->where("transaction_type", "EXTERNAL_TRANSFER_OUT")->first();
                $inTx = $transfer->transactions()->where("transaction_type", "EXTERNAL_TRANSFER_IN")->first();
                
                if ($outTx) {
                    InventoryTransaction::create([
                        "transaction_date" => now(),
                        "grade_company_id" => $transfer->grade_company_id,
                        "location_id" => $transfer->from_location_id,
                        "supplier_id" => $outTx->supplier_id,
                        "quantity_change_grams" => $totalDeduction,
                        "transaction_type" => "EXTERNAL_TRANSFER_REVERT_OUT",
                        "reference_id" => $transfer->id,
                        "sorting_result_id" => $transfer->sorting_result_id,
                        "created_by" => $userId,
                    ]);
                }
                
                if ($inTx) {
                    InventoryTransaction::create([
                        "transaction_date" => now(),
                        "grade_company_id" => $transfer->grade_company_id,
                        "location_id" => $transfer->to_location_id,
                        "supplier_id" => $inTx->supplier_id,
                        "quantity_change_grams" => -abs($transfer->weight_grams),
                        "transaction_type" => "EXTERNAL_TRANSFER_REVERT_IN",
                        "reference_id" => $transfer->id,
                        "sorting_result_id" => $transfer->sorting_result_id,
                        "created_by" => $userId,
                    ]);
                }
                
                $transfer->transactions()->delete();
                $transfer->deleted_by = $userId;
                $transfer->save();
                $transfer->delete();
                
                return redirect()->route("barang.keluar.external-transfer.step1")
                    ->with("success", "Transfer eksternal berhasil dihapus dan stok dikembalikan.");
            });
        } catch (\Throwable $e) {
            Log::error("Error in destroy: " . $e->getMessage(), [
                "user_id" => auth()->id(),
                "trace" => $e->getTraceAsString()
            ]);
            return redirect()->back()->with("error", "Terjadi kesalahan saat menghapus transfer.");
        }
    }

    public function export(Request $request)
    {
        try {
            $filters = [
                'start_date'      => $request->get('start_date'),
                'end_date'        => $request->get('end_date'),
                'supplier_id'     => $request->get('supplier_id'),
                'grade_company_id'=> $request->get('grade_company_id'),
            ];

            $fileName = 'transfer_eksternal_' . date('Y-m-d') . '.xlsx';
            return Excel::download(new TransferExternalExport($filters), $fileName);
        } catch (\Throwable $e) {
            Log::error('TransferExternalController export error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat export data.');
        }
    }
}
