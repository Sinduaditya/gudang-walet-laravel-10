<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Location;
use App\Models\GradeCompany;
use App\Services\BarangKeluar\BarangKeluarService;
use App\Http\Requests\BarangKeluar\TransferRequest;
use App\Models\StockTransfer;
use App\Exports\TransferInternalExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        try {
            $gudangUtama = Location::where('name', 'Gudang Utama')->first();
            if (!$gudangUtama) {
                return redirect()->back()->with('error', 'Lokasi "Gudang Utama" tidak ditemukan.');
            }

            // Ambil sumber grading dengan logika "Global Budgeting"
            // Ini memastikan jumlah di dropdown SINKRON dengan Net Global di Tracking Stok
            $gradingSources = $this->service->getGradingSourcesWithStock(\App\Models\SortingResult::OUTGOING_TYPE_INTERNAL, $gudangUtama->id);

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

            // dd($gradesWithStock);
            return view('admin.barang-keluar.transfer-step1', compact(
                'gradesWithStock',
                'dmkLocation',
                'transferInternalTransactions',
                'gudangUtama',
                'suppliers',
                'grades',
                'summary'
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('TransferInternalController transferStep1 error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data. Silakan coba lagi.');
        }
    }

    /**
     * Store Transfer Step 1 data to session
     */
    public function storeTransferStep1(Request $request)
    {
        try {
            $validated = $request->validate([
                'grade_company_id' => 'required|exists:sorting_results,id',
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

            $gudangUtama = Location::where('name', 'Gudang Utama')->first()
                ?? Location::where('id', 1)->first();

            if ($gudangUtama) {
                $validated['from_location_id'] = $gudangUtama->id;
            }

            $sortingResult = \App\Models\SortingResult::findOrFail($validated['grade_company_id']);
            $validated['sorting_result_id'] = $sortingResult->id;

            $totalWeight = $validated['weight_grams'] + ($validated['susut_grams'] ?? 0);

            $batchRemaining = $this->service->getBatchRemainingStock($validated['sorting_result_id'], $validated['from_location_id']);
            if ($batchRemaining < $totalWeight) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "Stok batch tidak mencukupi atau sudah habis terpakai transaksi lain! Tersedia: " . number_format($batchRemaining, 2) . " gr.");
            }

            if (!$this->service->hasEnoughStock($sortingResult->grade_company_id, $validated['from_location_id'], $totalWeight)) {
                $realStock = $this->service->getAvailableStock($sortingResult->grade_company_id, $validated['from_location_id']);
                return redirect()->back()
                    ->withInput()
                    ->with('error', "GAGAL: Stok fisik Grade " . $sortingResult->gradeCompany->name . " di gudang tidak mencukupi! Stok Nyata: " . number_format($realStock, 2) . " gr. (Dibutuhkan: " . number_format($totalWeight, 2) . " gr)");
            }

            $request->session()->put('transfer_step1', $validated);

            return redirect()->route('barang.keluar.transfer.step2');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('TransferInternalController storeTransferStep1 error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memproses data. Silakan coba lagi.')->withInput();
        }
    }

    /**
     * Transfer Step 2 - Show confirmation page
     */
    public function transferStep2()
    {
        try {
            $step1Data = session('transfer_step1');

            if (!$step1Data) {
                return redirect()->route('barang.keluar.transfer.step1')
                    ->with('error', 'Silakan lengkapi data transfer terlebih dahulu');
            }

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
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('TransferInternalController transferStep2 error: ' . $e->getMessage());
            return redirect()->route('barang.keluar.transfer.step1')->with('error', 'Terjadi kesalahan saat memuat data.');
        }
    }

    /**
     * Process transfer (final submission)
     */
    public function transfer(TransferRequest $request)
    {
        try {
            $data = $request->validated();

            $sortingResult = \App\Models\SortingResult::findOrFail($data['grade_company_id']);
            $data['sorting_result_id'] = $sortingResult->id;
            $data['grade_company_id'] = $sortingResult->grade_company_id;

            $totalWeight = $data['weight_grams'] + ($data['susut_grams'] ?? 0);
            $batchRemaining = $this->service->getBatchRemainingStock($data['sorting_result_id'], $data['from_location_id']);

            if ($batchRemaining < $totalWeight) {
                return back()->with('error', "Stok batch tidak mencukupi! Dibutuhkan: " . number_format($totalWeight, 2) . " gr. Tersedia: " . number_format($batchRemaining, 2) . " gr.");
            }

            if (!$this->service->hasEnoughStock($data['grade_company_id'], $data['from_location_id'], $totalWeight)) {
                $realStock = $this->service->getAvailableStock($data['grade_company_id'], $data['from_location_id']);
                return back()->with('error', "GAGAL: Stok fisik Grade di gudang tidak mencukupi secara global! Stok Nyata: " . number_format($realStock, 2) . " gr. (Dibutuhkan: " . number_format($totalWeight, 2) . " gr)");
            }

            $this->service->transfer($data);

            session()->forget('transfer_step1');

            return redirect()
                ->route('barang.keluar.transfer.step1')
                ->with('success', 'Transfer internal berhasil dicatat dan stok diperbarui.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('TransferInternalController transfer error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memproses transfer. Silakan coba lagi.');
        }
    }

    public function checkStock(Request $request)
    {
        try {
            $sortingResultId = (int) $request->query('grade_company_id');

            if (!$sortingResultId) {
                return response()->json(['ok' => false, 'message' => 'Batch required'], 400);
            }

            $gudangUtama = Location::where('name', 'Gudang Utama')->first();
            $locationId = $gudangUtama ? $gudangUtama->id : 1;

            $available = $this->service->getBatchRemainingStock($sortingResultId, $locationId);
            return response()->json(['ok' => true, 'available_grams' => (float) $available]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('TransferInternalController checkStock error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'message' => 'Terjadi kesalahan saat memeriksa stok'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $transfer = \App\Models\StockTransfer::lockForUpdate()->findOrFail($id);
                $userId = auth()->id();

                $totalDeduction = abs($transfer->weight_grams) + abs($transfer->susut_grams ?? 0);

                $outTx = $transfer->transactions()->where("transaction_type", "TRANSFER_OUT")->first();
                if ($outTx) {
                    InventoryTransaction::create([
                        "transaction_date" => now(),
                        "grade_company_id" => $transfer->grade_company_id,
                        "location_id" => $transfer->from_location_id,
                        "supplier_id" => $outTx->supplier_id,
                        "quantity_change_grams" => $totalDeduction,
                        "transaction_type" => "TRANSFER_REVERT_OUT",
                        "reference_id" => $transfer->id,
                        "sorting_result_id" => $transfer->sorting_result_id,
                        "created_by" => $userId,
                    ]);
                }

                $inTx = $transfer->transactions()->where("transaction_type", "TRANSFER_IN")->first();
                $toLocation = Location::find($transfer->to_location_id);
                if ($inTx && $toLocation && stripos($toLocation->name, "DMK") === false) {
                    InventoryTransaction::create([
                        "transaction_date" => now(),
                        "grade_company_id" => $transfer->grade_company_id,
                        "location_id" => $transfer->to_location_id,
                        "supplier_id" => $inTx->supplier_id,
                        "quantity_change_grams" => -abs($transfer->weight_grams),
                        "transaction_type" => "TRANSFER_REVERT_IN",
                        "reference_id" => $transfer->id,
                        "sorting_result_id" => $transfer->sorting_result_id,
                        "created_by" => $userId,
                    ]);
                }

                foreach ($transfer->transactions as $transaction) {
                    $transaction->deleted_by = $userId;
                    $transaction->save();
                    $transaction->delete();
                }

                $transfer->deleted_by = $userId;
                $transfer->save();
                $transfer->delete();

                return redirect()->route("barang.keluar.transfer.step1")
                    ->with("success", "Transfer internal berhasil dihapus dan stok dikembalikan.");
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("TransferInternalController destroy error: " . $e->getMessage());
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

            $fileName = 'transfer_internal_' . date('Y-m-d') . '.xlsx';
            return Excel::download(new TransferInternalExport($filters), $fileName);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('TransferInternalController export error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat export data.');
        }
    }
}
