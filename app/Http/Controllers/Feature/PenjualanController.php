<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\GradeCompany;
use App\Models\Location;
use App\Services\BarangKeluar\BarangKeluarService;
use App\Services\SortMaterial\SortMaterialService;
use App\Http\Requests\BarangKeluar\SellRequest;
use App\Exports\PenjualanExport;
use App\Exports\PenjualanSortirExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenjualanController extends Controller
{
    protected BarangKeluarService $service;
    protected SortMaterialService $sortService;

    public function __construct(BarangKeluarService $service, SortMaterialService $sortService)
    {
        $this->service     = $service;
        $this->sortService = $sortService;
    }

    /**
     * Tampilkan form penjualan + riwayat (grading & sortir)
     */
    public function sellForm(Request $request)
    {
        try {
            $defaultLocation = Location::where('name', 'Gudang Utama')->first();
            if (!$defaultLocation) {
                return redirect()->back()->with('error', 'Lokasi "Gudang Utama" tidak ditemukan.');
            }

            // ── STOK GRADING ──────────────────────────────────────
            $gradingSources = $this->service->getGradingSourcesWithStock(
                \App\Models\SortingResult::OUTGOING_TYPE_PENJUALAN_LANGSUNG,
                $defaultLocation->id
            );

            $gradesWithStock = $gradingSources->map(function ($source) {
                return [
                    'id'               => $source->id,
                    'name'             => $source->gradeCompany->name ?? 'Unknown',
                    'supplier_name'    => $source->receiptItem?->purchaseReceipt?->supplier?->name
                                          ?? $source->idmOutput?->idmManagement?->supplier?->name
                                          ?? 'Unknown',
                    'supplier_id'      => $source->receiptItem?->purchaseReceipt?->supplier_id
                                          ?? $source->idmOutput?->idmManagement?->supplier_id
                                          ?? null,
                    'grading_date'     => $source->grading_date ? $source->grading_date->format('d M Y') : '-',
                    'batch_stock_grams' => $source->adjusted_weight,
                    'total_stock_grams' => $source->real_global_stock,
                    'is_idm_output'    => !is_null($source->idm_output_id),
                ];
            });

            // Pisahkan: Grading form hanya tampilkan batch grading reguler.
            // IDM-SR dipindah ke variable khusus untuk tab "Stok Hasil Manajemen IDM".
            $idmGradesWithStock = $gradesWithStock->where('is_idm_output', true)->values();
            $gradesWithStock    = $gradesWithStock->where('is_idm_output', false)->values();

            $suppliers = \App\Models\Supplier::all();
            $grades    = \App\Models\GradeCompany::all();

            // Riwayat penjualan grading (EXCLUDE penjualan dari IDM)
            $query = InventoryTransaction::where('transaction_type', 'SALE_OUT')
                ->whereDoesntHave('sortingResult', function ($q) {
                    $q->whereNotNull('idm_output_id');
                })
                ->with(['gradeCompany', 'location', 'sortingResult.receiptItem.purchaseReceipt.supplier'])
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc');

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

            $summaryQuery        = clone $query;
            $summary             = $summaryQuery->get()
                ->groupBy('gradeCompany.name')
                ->map(function ($group) {
                    return $group->sum(fn($tx) => abs($tx->quantity_change_grams));
                });

            $penjualanTransactions = $query->paginate(10)->withQueryString();

            // ── RIWAYAT PENJUALAN DARI MANAJEMEN IDM ──────────────
            // Tampilkan SALE_OUT yang bersumber dari IDM-SR proxy (sorting_result.idm_output_id IS NOT NULL)
            $idmQuery = InventoryTransaction::where('transaction_type', 'SALE_OUT')
                ->whereHas('sortingResult', function ($q) {
                    $q->whereNotNull('idm_output_id');
                })
                ->with([
                    'gradeCompany',
                    'location',
                    'sortingResult.idmOutput.idmManagement.supplier',
                ])
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc');

            if ($request->filled('idm_start_date')) {
                $idmQuery->whereDate('transaction_date', '>=', $request->idm_start_date);
            }
            if ($request->filled('idm_end_date')) {
                $idmQuery->whereDate('transaction_date', '<', $request->idm_end_date);
            }
            if ($request->filled('idm_supplier_id')) {
                $idmQuery->whereHas('sortingResult.idmOutput.idmManagement', function ($q) use ($request) {
                    $q->where('supplier_id', $request->idm_supplier_id);
                });
            }
            if ($request->filled('idm_grade_company_id')) {
                $idmQuery->where('grade_company_id', $request->idm_grade_company_id);
            }

            $idmSummary = (clone $idmQuery)->get()
                ->groupBy('gradeCompany.name')
                ->map(function ($group) {
                    return $group->sum(fn($tx) => abs($tx->quantity_change_grams));
                });

            $idmPenjualanTransactions = $idmQuery->paginate(10, ['*'], 'idm_page')->withQueryString();

            // ── STOK SORTIR ───────────────────────────────────────
            $this->sortService->recalculateAllParentStocks();
            $sortStocks           = $this->sortService->getAvailableSortStock();
            $sortGradesWithStock  = $this->sortService->getAvailableSortGradesWithStock();
            $parentGrades         = \App\Models\ParentGradeCompany::orderBy('name')->get();

            $sortFilters = [
                'start_date'              => $request->get('sort_start_date'),
                'end_date'                => $request->get('sort_end_date'),
                'parent_grade_company_id' => $request->get('sort_parent_grade_id'),
            ];
            $sortSaleTransactions = $this->sortService->getSortSales($sortFilters);

            // Summary sortir sales
            $sortSummary = $this->sortService->getSortSales($sortFilters + ['no_paginate' => true]);

            return view('admin.barang-keluar.sell', compact(
                'gradesWithStock',
                'idmGradesWithStock',
                'penjualanTransactions',
                'idmPenjualanTransactions',
                'idmSummary',
                'defaultLocation',
                'suppliers',
                'grades',
                'summary',
                'sortStocks',
                'sortGradesWithStock',
                'parentGrades',
                'sortSaleTransactions',
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PenjualanController sellForm error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data. Silakan coba lagi.');
        }
    }

    public function checkStock(Request $request)
    {
        try {
            $sortingResultId = (int) $request->query('grade_company_id');

            if (!$sortingResultId) {
                return response()->json(['ok' => false, 'message' => 'Batch required'], 400);
            }

            $defaultLocation = Location::where('name', 'Gudang Utama')->first();
            $locationId      = $defaultLocation ? $defaultLocation->id : 1;

            $available = $this->service->getBatchRemainingStock($sortingResultId, $locationId);
            return response()->json(['ok' => true, 'available_grams' => (float) $available]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PenjualanController checkStock error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'message' => 'Terjadi kesalahan saat memeriksa stok'], 500);
        }
    }

    /**
     * Store penjualan dari stok grading
     */
    public function sell(SellRequest $request)
    {
        try {
            $defaultLocation = Location::where('name', 'Gudang Utama')->first();
            $data            = $request->validated();

            $sortingResult           = \App\Models\SortingResult::findOrFail($data['grade_company_id']);
            $data['sorting_result_id'] = $sortingResult->id;
            $data['grade_company_id']  = $sortingResult->grade_company_id;
            $data['location_id']       = $defaultLocation->id;

            $batchRemaining = $this->service->getBatchRemainingStock($data['sorting_result_id'], $data['location_id']);
            if ($batchRemaining < $data['weight_grams']) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Stok batch (' . $sortingResult->gradeCompany->name . ') tidak mencukupi. Tersedia: ' . number_format($batchRemaining, 2) . ' gr.');
            }

            if (!$this->service->hasEnoughStock($data['grade_company_id'], $data['location_id'], $data['weight_grams'])) {
                $realStock = $this->service->getAvailableStock($data['grade_company_id'], $data['location_id']);
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Stok fisik di gudang tidak mencukupi! Stok Nyata: ' . number_format($realStock, 2) . ' gr.');
            }

            $this->service->sell($data);

            return redirect()->route('barang.keluar.sell.form')
                ->with('success', 'Penjualan berhasil dicatat dan stok diperbarui.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PenjualanController sell error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memproses penjualan. Silakan coba lagi.');
        }
    }

    /**
     * Store penjualan dari stok sortir bahan
     */
    public function sellFromSort(Request $request)
    {
        $request->validate([
            'parent_grade_company_id' => 'required|exists:parent_grade_companies,id',
            'grade_company_id'        => 'nullable|exists:grades_company,id',
            'weight'                  => 'required|numeric|min:0.01',
            'sale_date'               => 'nullable|date',
            'notes'                   => 'nullable|string|max:500',
        ], [
            'parent_grade_company_id.required' => 'Parent Grade harus dipilih.',
            'parent_grade_company_id.exists'   => 'Parent Grade tidak valid.',
            'grade_company_id.exists'          => 'Detail Grade Company tidak valid.',
            'weight.required'                  => 'Berat harus diisi.',
            'weight.min'                       => 'Berat minimal 0.01 gram.',
        ]);

        try {
            $this->sortService->sellFromSort($request->only([
                'parent_grade_company_id', 'grade_company_id', 'weight', 'sale_date', 'notes',
            ]));

            return redirect()->route('barang.keluar.sell.form', ['active_tab' => 'sortir'])
                ->with('success', 'Penjualan dari Sortir Bahan berhasil dicatat dan stok dikurangi.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PenjualanController sellFromSort error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Hapus penjualan grading + revert stok
     */
    public function destroy($id)
    {
        \Log::info("========== PENJUALAN DELETE START ==========");
        \Log::info("Delete penjualan ID: $id");

        try {
            return DB::transaction(function () use ($id) {
                $tx = InventoryTransaction::lockForUpdate()->findOrFail($id);

                \Log::info("TX found: type=" . $tx->transaction_type . ", sorting_result_id=" . ($tx->sorting_result_id ?? 'NULL') . ", grade=" . $tx->grade_company_id);

                if ($tx->deleted_at) {
                    return redirect()->route('barang.keluar.sell.form')
                        ->with('error', 'Transaksi sudah dihapus sebelumnya.');
                }

                // Validasi 1: Direct check via sorting_result_id
                if ($tx->sorting_result_id) {
                    $sr = \App\Models\SortingResult::find($tx->sorting_result_id);
                    \Log::info("Check 1 - SR found: " . ($sr ? 'YES' : 'NO') . ", idm_management_id=" . ($sr ? ($sr->idm_management_id ?? 'NULL') : 'N/A'));

                    if ($sr && !is_null($sr->idm_management_id)) {
                        \Log::info("CHECK 1 BLOCKED: SR has idm_management_id");
                        return redirect()->route('barang.keluar.sell.form')
                            ->with('error', 'Tidak dapat menghapus penjualan dari grading yang sedang di-regrading di Manajemen IDM #' . $sr->idm_management_id . '. Batalkan proses IDM terlebih dahulu.');
                    }
                } else {
                    \Log::info("Check 1 SKIPPED: sorting_result_id is NULL");
                }

                // Validasi 2: Check apakah grade ini sedang digunakan sebagai input di IDM mana pun
                $lockedByIdm = \App\Models\SortingResult::where('grade_company_id', $tx->grade_company_id)
                    ->whereNotNull('idm_management_id')
                    ->whereNull('deleted_at')
                    ->exists();

                \Log::info("Check 2 - Grade-based IDM lock: " . ($lockedByIdm ? 'LOCKED' : 'FREE'));

                if ($lockedByIdm) {
                    $lockedIdm = \App\Models\SortingResult::where('grade_company_id', $tx->grade_company_id)
                        ->whereNotNull('idm_management_id')
                        ->whereNull('deleted_at')
                        ->first();

                    \Log::info("CHECK 2 BLOCKED: Grade locked by IDM #" . $lockedIdm->idm_management_id);
                    return redirect()->route('barang.keluar.sell.form')
                        ->with('error', 'Tidak dapat menghapus penjualan — grade ini sedang di-regrading di Manajemen IDM #' . $lockedIdm->idm_management_id . '. Batalkan proses IDM terlebih dahulu.');
                }

                // ✅ PENTING: Soft-delete SALE_OUT sudah cukup untuk "undo" transaksi
                //
                // Alasan TIDAK membuat SALE_REVERT:
                // - SALE_OUT: -100 (soft-delete → excluded dari SUM)
                // - Soft-delete otomatis mengembalikan stok ke semula
                // - Jika tambah SALE_REVERT: -100 + (+100) = 0 jadi +100, stock DOUBLED!
                // -
                // Ledger sebelum delete:
                //   GRADING_IN: +100
                //   SALE_OUT: -100
                //   Total: 0
                //
                // Ledger setelah delete (HANYA soft-delete, NO REVERT):
                //   GRADING_IN: +100
                //   SALE_OUT: -100 (deleted_at != NULL → excluded)
                //   Total: 100 ✓
                //
                // Jika buat SALE_REVERT (SALAH):
                //   GRADING_IN: +100
                //   SALE_OUT: -100 (soft-deleted, excluded)
                //   SALE_REVERT: +100
                //   Total: 200 ✗ DOUBLED!

                if ($tx->sorting_result_id) {
                    $sortMaterial = \App\Models\SortMaterial::where('sorting_result_id', $tx->sorting_result_id)->first();
                    if ($sortMaterial) {
                        $sortMaterial->deleted_by = auth()->id();
                        $sortMaterial->save();
                        $sortMaterial->delete();
                    }
                }

                $tx->deleted_by = auth()->id();
                $tx->save();
                $tx->delete();

                \Log::info("========== PENJUALAN DELETE END ==========");
                return redirect()->route('barang.keluar.sell.form')
                    ->with('success', 'Transaksi penjualan dihapus dan stok dikembalikan.');
            });
        } catch (\Exception $e) {
            \Log::error("ERROR in PenjualanController destroy: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Hapus penjualan dari sortir bahan + revert stok
     */
    public function destroySortSale(int $id)
    {
        try {
            $this->sortService->deleteSale($id);
            return redirect()->route('barang.keluar.sell.form', ['active_tab' => 'sortir'])
                ->with('success', 'Penjualan sortir bahan dihapus dan stok dikembalikan.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PenjualanController destroySortSale error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        try {
            $filters = [
                'start_date'       => $request->get('start_date'),
                'end_date'         => $request->get('end_date'),
                'supplier_id'      => $request->get('supplier_id'),
                'grade_company_id' => $request->get('grade_company_id'),
            ];

            $fileName = 'penjualan_langsung_' . date('Y-m-d') . '.xlsx';
            return Excel::download(new PenjualanExport($filters), $fileName);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PenjualanController export error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat export data.');
        }
    }

    public function exportSortSale(Request $request)
    {
        try {
            $filters = [
                'start_date'              => $request->get('start_date'),
                'end_date'                => $request->get('end_date'),
                'parent_grade_company_id' => $request->get('parent_grade_company_id'),
                'grade_company_id'        => $request->get('grade_company_id'),
            ];

            $fileName = 'penjualan_sortir_bahan_' . date('Y-m-d') . '.xlsx';
            return Excel::download(new PenjualanSortirExport($filters), $fileName);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PenjualanController exportSortSale error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat export data.');
        }
    }
}
