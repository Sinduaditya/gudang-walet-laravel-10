<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Sale;
use App\Models\Location;
use App\Models\Supplier;
use App\Models\SaleItem;
use App\Models\GradeCompany;
use App\Models\StockTransfer;
use App\Models\SortingResult;
use App\Models\PurchaseReceipt;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    /**
     * Get statistics cards data
     */
    public function getStatisticsCards()
    {
        $today = Carbon::today();

        return [
            'barang_masuk_hari_ini' => $this->getBarangMasukHariIni($today),
            'barang_keluar_hari_ini' => $this->getBarangKeluarHariIni($today),
            'barang_di_grading' => $this->getBarangDiGrading($today),
            'total_supplier_aktif' => $this->getTotalSupplierAktif(),
        ];
    }

    /**
     * GRAFIK FLOW BERAT BARANG MASUK DAN KELUAR PERHARINYA
     */
    public function getFlowBarangMasukKeluar($days = 7)
    {
        $dates = collect();
        for ($i = $days - 1; $i >= 0; $i--) {
            $dates->push(Carbon::today()->subDays($i));
        }

        $masukData = [];
        $keluarData = [];
        $labels = [];

        foreach ($dates as $date) {
            // Barang masuk dari purchase receipts
            $masuk =
                PurchaseReceipt::whereDate('unloading_date', $date)
                    ->with('receiptItems')
                    ->get()
                    ->sum(function ($receipt) {
                        return $receipt->receiptItems->sum('warehouse_weight_grams');
                    }) / 1000; // Convert to kg

            // Barang keluar dari inventory transactions
            $keluar = InventoryTransaction::whereDate('transaction_date', $date)->where('quantity_change_grams', '<', 0)->sum(DB::raw('ABS(quantity_change_grams)')) / 1000; // Convert to kg

            $masukData[] = round($masuk, 2);
            $keluarData[] = round($keluar, 2);
            $labels[] = $date->format('D');
        }

        return [
            'labels' => $labels,
            'masuk' => $masukData,
            'keluar' => $keluarData,
        ];
    }

    /**
     * GRAFIK FLOW BARANG YANG DI KIRIM KE DMK PERHARINYA
     */
    public function getFlowBarangKeDMK($days = 7)
    {
        $dates = collect();
        for ($i = $days - 1; $i >= 0; $i--) {
            $dates->push(Carbon::today()->subDays($i));
        }

        $dmkLocation = Location::where('name', 'LIKE', '%DMK%')->orWhere('name', 'LIKE', '%Demak%')->first();

        $dmkData = [];
        $labels = [];

        foreach ($dates as $date) {
            $dmkKirim = 0;

            if ($dmkLocation) {
                // Dari stock transfers
                $dmkKirim += StockTransfer::whereDate('transfer_date', $date)->where('to_location_id', $dmkLocation->id)->sum('weight_grams') / 1000;

                // Dari sales ke DMK
                $dmkKirim +=
                    SaleItem::whereHas('sale', function ($query) use ($date) {
                        $query->whereDate('sale_date', $date);
                    })
                        ->whereHas('sale', function ($query) {
                            $query->where('buyer_name', 'LIKE', '%DMK%')->orWhere('buyer_name', 'LIKE', '%Demak%');
                        })
                        ->sum('weight_grams') / 1000;
            }

            $dmkData[] = round($dmkKirim, 2);
            $labels[] = $date->format('D');
        }

        return [
            'labels' => $labels,
            'data' => $dmkData,
        ];
    }

    /**
     * GRAFIK FLOW BARANG YANG DI KIRIM KE JASA CUCI / NAMA JASA CUCI
     */
    public function getFlowBarangKeJasaCuci()
    {
    
        $jasaCuciLocations = Location::where('name', 'NOT LIKE', '%Gudang Utama%')
            ->where('name', 'NOT LIKE', '%DMK%')
            ->where('name', 'NOT LIKE', '%Demak%')
            ->pluck('id');

        if ($jasaCuciLocations->isEmpty()) {
            // Jika tidak ada lokasi jasa cuci, return data kosong
            return [
                'labels' => [],
                'data' => [],
            ];
        }

        // Ambil transaksi transfer ke lokasi jasa cuci bulan ini (dari StockTransfer)
        $jasaCuciData = StockTransfer::with(['toLocation'])
            ->whereIn('to_location_id', $jasaCuciLocations)
            ->whereMonth('transfer_date', Carbon::now()->month)
            ->whereYear('transfer_date', Carbon::now()->year)
            ->get()
            ->groupBy('toLocation.name')
            ->map(function ($transfers) {
                return $transfers->sum('weight_grams') / 1000; // Convert to kg
            })
            ->sortDesc()
            ->take(20);

        return [
            'labels' => $jasaCuciData->keys()->toArray(),
            'data' => $jasaCuciData->values()->map(fn($val) => round($val, 2))->toArray(),
        ];
    }

    /**
     * GRAFIK BARANG MASUK PER SUPPLIER
     */
    public function getBarangMasukPerSupplier()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $supplierData = Supplier::with([
            'purchaseReceipts' => function ($query) use ($currentMonth, $currentYear) {
                $query->whereMonth('unloading_date', $currentMonth)->whereYear('unloading_date', $currentYear)->with('receiptItems');
            },
        ])
            ->get()
            ->map(function ($supplier) {
                $totalWeight =
                    $supplier->purchaseReceipts->sum(function ($receipt) {
                        return $receipt->receiptItems->sum('warehouse_weight_grams');
                    }) / 1000; // Convert to kg

                return [
                    'name' => $supplier->name,
                    'weight' => round($totalWeight, 2),
                ];
            })
            ->where('weight', '>', 0)
            ->sortByDesc('weight')
            ->take(10);

        return [
            'labels' => $supplierData->pluck('name')->toArray(),
            'data' => $supplierData->pluck('weight')->toArray(),
        ];
    }

    /**
     * GRAFIK TOTAL BARANG YANG DI GRADING PERHARI
     */
    public function getTotalBarangGradingPerHari($days = 7)
    {
        $dates = collect();
        for ($i = $days - 1; $i >= 0; $i--) {
            $dates->push(Carbon::today()->subDays($i));
        }

        $gradingData = [];
        $labels = [];

        foreach ($dates as $date) {
            $totalGrading = SortingResult::whereDate('grading_date', $date)->sum('weight_grams') / 1000; // Convert to kg

            $gradingData[] = round($totalGrading, 2);
            $labels[] = $date->format('D');
        }

        return [
            'labels' => $labels,
            'data' => $gradingData,
        ];
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities($limit = 5)
    {
        $activities = collect();

        // Barang masuk (dari purchase receipts)
        $recentReceipts = PurchaseReceipt::with(['supplier', 'receiptItems'])
            ->latest('unloading_date')
            ->take($limit)
            ->get()
            ->map(function ($receipt) {
                $totalWeight = $receipt->receiptItems->sum('warehouse_weight_grams') / 1000;
                return [
                    'type' => 'incoming',
                    'icon' => 'plus',
                    'color' => 'green',
                    'message' => "Barang masuk <strong>{$totalWeight}kg</strong> dari <strong class=\"text-blue-600\">{$receipt->supplier->name}</strong>",
                    'time' => Carbon::parse($receipt->unloading_date)->diffForHumans(),
                    'created_at' => Carbon::parse($receipt->unloading_date),
                ];
            });

        // Grading selesai
        $recentGrading = SortingResult::with(['gradeCompany', 'receiptItem.purchaseReceipt.supplier'])
            ->latest('grading_date')
            ->take($limit)
            ->get()
            ->map(function ($grading) {
                $weight = $grading->weight_grams / 1000;
                $gradeName = $grading->gradeCompany ? $grading->gradeCompany->name : 'Unknown Grade';
                return [
                    'type' => 'grading',
                    'icon' => 'grid',
                    'color' => 'blue',
                    'message' => "Proses grading selesai. Menghasilkan <strong>{$weight}kg {$gradeName}</strong>.",
                    'time' => Carbon::parse($grading->grading_date)->diffForHumans(),
                    'created_at' => Carbon::parse($grading->grading_date),
                ];
            });

        // Barang keluar (dari inventory transactions)
        $recentOutgoing = InventoryTransaction::with(['gradeCompany', 'location'])
            ->where('quantity_change_grams', '<', 0)
            ->latest('transaction_date')
            ->take($limit)
            ->get()
            ->map(function ($transaction) {
                $weight = abs($transaction->quantity_change_grams) / 1000;
                return [
                    'type' => 'outgoing',
                    'icon' => 'arrow-right',
                    'color' => 'red',
                    'message' => "Barang keluar <strong>{$weight}kg</strong> dikirim ke <strong class=\"text-blue-600\">{$transaction->location->name}</strong>.",
                    'time' => Carbon::parse($transaction->transaction_date)->diffForHumans(),
                    'created_at' => Carbon::parse($transaction->transaction_date),
                ];
            });

        return $activities->merge($recentReceipts)->merge($recentGrading)->merge($recentOutgoing)->sortByDesc('created_at')->take($limit)->values();
    }

    /**
     * Helper methods for statistics cards
     */
    private function getBarangMasukHariIni($date)
    {
        // Coba kedua field tanggal yang mungkin
        $receipts = PurchaseReceipt::where(function ($query) use ($date) {
            $query->whereDate('unloading_date', $date)->orWhereDate('receipt_date', $date);
        })
            ->with('receiptItems')
            ->get();

        $totalGrams = $receipts->sum(function ($receipt) {
            return $receipt->receiptItems->sum('warehouse_weight_grams');
        });

        // Debug logging yang lebih detail
        Log::info('Dashboard Debug - Barang Masuk Hari Ini', [
            'date_filter' => $date->format('Y-m-d'),
            'today' => Carbon::today()->format('Y-m-d'),
            'receipts_count' => $receipts->count(),
            'total_grams' => $totalGrams,
            'total_kg' => $totalGrams / 1000,
            'all_receipts_today' => PurchaseReceipt::with('receiptItems')
                ->get()
                ->map(function ($receipt) {
                    return [
                        'id' => $receipt->id,
                        'receipt_date' => $receipt->receipt_date?->format('Y-m-d'),
                        'unloading_date' => $receipt->unloading_date?->format('Y-m-d'),
                        'supplier' => $receipt->supplier->name ?? 'No supplier',
                        'items_count' => $receipt->receiptItems->count(),
                        'total_weight' => $receipt->receiptItems->sum('warehouse_weight_grams'),
                    ];
                }),
            'sql_query' => PurchaseReceipt::where(function ($query) use ($date) {
                $query->whereDate('unloading_date', $date)->orWhereDate('receipt_date', $date);
            })->toSql(),
        ]);

        return $totalGrams / 1000;
    }

    private function getBarangKeluarHariIni($date)
    {
        return InventoryTransaction::whereDate('transaction_date', $date)->where('quantity_change_grams', '<', 0)->sum(DB::raw('ABS(quantity_change_grams)')) / 1000; // Convert to kg
    }

    private function getBarangDiGrading($date)
    {
        return SortingResult::whereDate('grading_date', $date)->sum('weight_grams') / 1000; // Convert to kg
    }

    private function getTotalSupplierAktif()
    {
        // Supplier yang ada transaksi dalam 30 hari terakhir
        return Supplier::whereHas('purchaseReceipts', function ($query) {
            $query->where('unloading_date', '>=', Carbon::now()->subDays(30));
        })->count();
    }

    /**
     * Get current stock summary
     */
    public function getCurrentStockSummary()
    {
        return InventoryTransaction::with(['gradeCompany', 'location'])
            ->select(['grade_company_id', 'location_id', DB::raw('SUM(quantity_change_grams) as total_stock')])
            ->groupBy(['grade_company_id', 'location_id'])
            ->having('total_stock', '>', 0)
            ->get()
            ->map(function ($stock) {
                return [
                    'grade' => $stock->gradeCompany->name,
                    'location' => $stock->location->name,
                    'stock_kg' => round($stock->total_stock / 1000, 2),
                ];
            });
    }
}
