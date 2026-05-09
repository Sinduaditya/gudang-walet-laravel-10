<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\GradeCompany;
use App\Models\Location;
use App\Services\BarangKeluar\BarangKeluarService;
use App\Http\Requests\BarangKeluar\SellRequest;
use App\Exports\PenjualanExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        try {
            $defaultLocation = Location::where('name', 'Gudang Utama')->first();
            if (!$defaultLocation) {
                return redirect()->back()->with('error', 'Lokasi "Gudang Utama" tidak ditemukan.');
            }

            // Ambil sumber grading dengan logika "Global Budgeting"
            $gradingSources = $this->service->getGradingSourcesWithStock(\App\Models\SortingResult::OUTGOING_TYPE_INTERNAL, $defaultLocation->id);

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
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PenjualanController sellForm error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data. Silakan coba lagi.');
        }
    }

    public function checkStock(Request $request)
    {
        try {
            // This is now checking BATCH stock because grade_company_id param is actually sorting_result_id
            $sortingResultId = (int) $request->query('grade_company_id');

            if (!$sortingResultId) {
                return response()->json(['ok' => false, 'message' => 'Batch required'], 400);
            }

            $defaultLocation = Location::where('name', 'Gudang Utama')->first();
            $locationId = $defaultLocation ? $defaultLocation->id : 1;

            $available = $this->service->getBatchRemainingStock($sortingResultId, $locationId);
            return response()->json(['ok' => true, 'available_grams' => (float) $available]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PenjualanController checkStock error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'message' => 'Terjadi kesalahan saat memeriksa stok'], 500);
        }
    }

    /**
     * Store sale
     */
    public function sell(SellRequest $request)
    {
        try {
            $defaultLocation = Location::where('name', 'Gudang Utama')->first();

            $data = $request->validated();

            // Resolve SortingResult and GradeCompany
            $sortingResult = \App\Models\SortingResult::findOrFail($data['grade_company_id']);
            $data['sorting_result_id'] = $sortingResult->id;
            $data['grade_company_id'] = $sortingResult->grade_company_id;
            $data['location_id'] = $defaultLocation->id;

            // 1. Cek stok BATCH (Link ke sorting_result)
            $batchRemaining = $this->service->getBatchRemainingStock($data['sorting_result_id'], $data['location_id']);
            if ($batchRemaining < $data['weight_grams']) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Stok batch (' . $sortingResult->gradeCompany->name . ') tidak mencukupi atau sudah habis terpakai transaksi lain. Tersedia: ' . number_format($batchRemaining, 2) . ' gr.');
            }

            // 2. Cek stok NYATA di Gudang (Total Grade di lokasi tersebut)
            if (!$this->service->hasEnoughStock($data['grade_company_id'], $data['location_id'], $data['weight_grams'])) {
                $realStock = $this->service->getAvailableStock($data['grade_company_id'], $data['location_id']);
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Stok fisik di gudang tidak mencukupi untuk Grade ini! Stok Nyata: ' . number_format($realStock, 2) . ' gr. Anda mencoba menjual: ' . number_format($data['weight_grams'], 2) . ' gr.');
            }

            $this->service->sell($data);

            return redirect()->route('barang.keluar.sell.form')
                ->with('success', 'Penjualan berhasil dicatat dan stok diperbarui.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PenjualanController sell error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memproses penjualan. Silakan coba lagi.');
        }
    }

    public function destroy($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $tx = InventoryTransaction::lockForUpdate()->findOrFail($id);
                $oldQuantity = $tx->quantity_change_grams;

                InventoryTransaction::create([
                    'transaction_date' => now(),
                    'grade_company_id' => $tx->grade_company_id,
                    'location_id' => $tx->location_id,
                    'quantity_change_grams' => abs($oldQuantity),
                    'supplier_id' => $tx->supplier_id,
                    'transaction_type' => 'SALE_REVERT',
                    'reference_id' => $tx->id,
                    'sorting_result_id' => $tx->sorting_result_id,
                    'notes' => 'Revert dari delete penjualan ID: ' . $id,
                    'created_by' => auth()->id(),
                ]);

                $tx->deleted_by = auth()->id();
                $tx->save();
                $tx->delete();

                return redirect()->route('barang.keluar.sell.form')
                    ->with('success', 'Transaksi penjualan dihapus dan stok dikembalikan.');
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PenjualanController destroy error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus transaksi.');
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

            $fileName = 'penjualan_langsung_' . date('Y-m-d') . '.xlsx';
            return Excel::download(new PenjualanExport($filters), $fileName);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PenjualanController export error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat export data.');
        }
    }
}
