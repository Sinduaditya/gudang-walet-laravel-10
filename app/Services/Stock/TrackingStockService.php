<?php

namespace App\Services\Stock;

use App\Models\Location;
use App\Models\GradeCompany;
use App\Models\StockTransfer;
use App\Models\InventoryTransaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
class TrackingStockService
{
    public function getGradeCompany(?string $search = null): LengthAwarePaginator
    {
        return GradeCompany::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function getGradeById(int $id): GradeCompany
    {
        return GradeCompany::findOrFail($id);
    }

    public function calculateGlobalStock(int $gradeId): int
    {
        return (int) round(InventoryTransaction::where('grade_company_id', $gradeId)->sum('quantity_change_grams'));
    }

    // public function getStockPerLocation(int $gradeId, ?string $search = null): Collection
    // {
    //     // Hitung stock dari inventory_transactions (akumulasi semua transaksi)
    //     $stockPerLocation = InventoryTransaction::query()
    //         ->select('location_id')
    //         ->selectRaw('SUM(quantity_change_grams) as total_stock')
    //         ->where('grade_company_id', $gradeId)
    //         ->groupBy('location_id')
    //         ->having('total_stock', '>', 0)
    //         ->pluck('total_stock', 'location_id');

    //     // Ambil semua lokasi yang PERNAH MENERIMA transfer untuk grade ini (dari stock_transfers)
    //     $transferLocations = StockTransfer::query()
    //         ->select('to_location_id')
    //         ->distinct()
    //         ->where('grade_company_id', $gradeId)
    //         ->pluck('to_location_id');

    //     // Gabung ID dari inventory_transactions + stock_transfers
    //     $allLocationIds = $stockPerLocation->keys()->merge($transferLocations)->unique();

    //     // Query lokasi berdasarkan ID gabungan
    //     $locations = Location::whereIn('id', $allLocationIds)
    //         ->when($search, function ($q) use ($search) {
    //             $q->where('name', 'like', "%{$search}%");
    //         })
    //         ->orderBy('name')
    //         ->get();

    //     // Map ke object dengan stock info
    //     return collect($locations->map(function ($location) use ($stockPerLocation) {
    //         return (object) [
    //             'location_id' => $location->id,
    //             'total_stock' => floatval($stockPerLocation[$location->id] ?? 0),
    //             'location' => $location,
    //         ];
    //     }));
    // }

    public function getStockPerLocation(int $gradeId, ?string $search = null): Collection
    {
        // Query utama: Group by Location DAN Supplier
        $query = InventoryTransaction::query()
            ->select('location_id', 'supplier_id') // Select supplier juga
            ->selectRaw('SUM(quantity_change_grams) as total_stock')
            ->where('grade_company_id', $gradeId)
            ->groupBy('location_id', 'supplier_id') // Grouping ganda
            ->having('total_stock', '>', 0);

        // Jika ada search berdasarkan nama lokasi
        if ($search) {
            $query->whereHas('location', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Eager load relasi untuk ditampilkan di View
        // Mengembalikan Collection of InventoryTransaction objects
        return $query->with(['location', 'supplier'])
                     ->orderBy('location_id') // Optional sorting
                     ->get();
    }

    public function getAllLocations(): Collection
    {
        return Location::orderBy('name')->get();
    }

    public function getSusutHistory(int $gradeId, array $filters): LengthAwarePaginator
    {
        return StockTransfer::query()
            ->with(['fromLocation', 'toLocation'])
            ->where('grade_company_id', $gradeId)

            ->when($filters['date'] ?? null, function ($q, $date) {
                $q->whereDate('transfer_date', $date);
            })

            ->when($filters['from_location_id'] ?? null, function ($q, $id) {
                $q->where('from_location_id', $id);
            })

            ->when($filters['to_location_id'] ?? null, function ($q, $id) {
                $q->where('to_location_id', $id);
            })
            ->latest('transfer_date')
            ->paginate(10)
            ->withQueryString();
    }
}
