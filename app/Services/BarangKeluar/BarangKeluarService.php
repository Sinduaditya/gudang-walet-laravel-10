<?php

namespace App\Services\BarangKeluar;

use App\Models\InventoryTransaction;
use App\Models\StockTransfer;
use App\Models\GradeCompany;
use App\Models\Location;
use App\Models\SortingResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BarangKeluarService
{
    /**
     * Get remaining stock for a specific batch (SortingResult)
     *
     * @param int $sortingResultId
     * @return float
     */

    /**
     * Helper untuk mengambil supplier_id dari sorting_result_id
     */
    // private function getSupplierIdFromSortingResult($sortingResultId)
    // {
    //     if (!$sortingResultId) return null;

    //     $sortingResult = SortingResult::with('receiptItem.purchaseReceipt')->find($sortingResultId);

    //     return $sortingResult->receiptItem->purchaseReceipt->supplier_id ?? null;
    // }

    private function getSupplierIdFromSortingResult($sortingResultId)
    {
        if (!$sortingResultId) {
            return null;
        }

        $sortingResult = SortingResult::with('receiptItem.purchaseReceipt')->find($sortingResultId);

        // Pengecekan bertingkat agar tidak error "Attempt to read property on null"
        if ($sortingResult && $sortingResult->receiptItem && $sortingResult->receiptItem->purchaseReceipt) {
            return $sortingResult->receiptItem->purchaseReceipt->supplier_id;
        }

        return null;
    }

    // public function getBatchRemainingStock(int $sortingResultId, ?int $locationId = null): float
    // {
    //     $initialWeight = \App\Models\SortingResult::where('id', $sortingResultId)->value('weight_grams') ?? 0;

    //     // If location is specified, only include initial weight if it's Gudang Utama (assuming batches start there)
    //     // We can fetch Gudang Utama ID or assume it's 1, but safer to check name if possible.
    //     // For performance, we might want to cache this ID, but for now query is fine.
    //     if ($locationId) {
    //         $gudangUtama = Location::where('name', 'Gudang Utama')->first();
    //         if ($gudangUtama && $locationId != $gudangUtama->id) {
    //             $initialWeight = 0;
    //         }
    //     }

    //     $query = InventoryTransaction::where('sorting_result_id', $sortingResultId);

    //     if ($locationId) {
    //         $query->where('location_id', $locationId);
    //     }

    //     $deductions = $query->sum('quantity_change_grams');

    //     return max(0, $initialWeight + $deductions);
    // }
    public function getBatchRemainingStock(int $sortingResultId, ?int $locationId = null): float
    {
        // 1. Dapatkan SortingResult
        $sortingResult = SortingResult::find($sortingResultId);
        if (!$sortingResult)
            return 0;

        $gradeCompanyId = $sortingResult->grade_company_id;

        // 2. Hitung Stok khusus Batch ini (di lokasi tertentu jika ada)
        $batchQuery = InventoryTransaction::where('sorting_result_id', $sortingResultId);
        if ($locationId) {
            $batchQuery->where('location_id', $locationId);
        }
        $batchStock = (float) $batchQuery->sum('quantity_change_grams');

        // 3. Hitung Total Stok Grade ini di lokasi tersebut
        $gradeQuery = InventoryTransaction::where('grade_company_id', $gradeCompanyId);
        if ($locationId) {
            $gradeQuery->where('location_id', $locationId);
        }
        $totalGradeLocationStock = (float) $gradeQuery->sum('quantity_change_grams');

        // 4. Hitung TOTAL STOK GRADE DI SELURUH GUDANG (Net Global)
        // Ini kunci perbaikan: agar dropdown tidak melebihi stok asli keseluruhan
        $globalNetStock = (float) InventoryTransaction::where('grade_company_id', $gradeCompanyId)
            ->sum('quantity_change_grams');

        // Dropdown harus menampilkan angka terkecil dari 3 pengecekan:
        // - Stok batch itu sendiri
        // - Stok grade di lokasi tersebut
        // - Stok grade secara global (agar minus di gudang lain ikut diperhitungkan)
        $finalStock = min($batchStock, $totalGradeLocationStock, $globalNetStock);

        return max(0, $finalStock);
    }

    /**
     * Proses penjualan langsung:
     * - Catat transaksi keluar (SALE_OUT)
     *
     * @param array $data
     * @return InventoryTransaction
     */
    public function sell(array $data): InventoryTransaction
    {
        return DB::transaction(function () use ($data) {
            $userId = Auth::id();

            $supplierId = $this->getSupplierIdFromSortingResult($data['sorting_result_id'] ?? null);

            return InventoryTransaction::create([
                'transaction_date' => $data['transaction_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'location_id' => $data['location_id'],
                'quantity_change_grams' => -abs($data['weight_grams']),
                'supplier_id' => $supplierId,
                'transaction_type' => 'SALE_OUT',
                'reference_id' => null,
                'sorting_result_id' => $data['sorting_result_id'] ?? null,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Proses transfer internal antar lokasi:
     * - Insert ke stock_transfers
     * - Buat dua baris inventory_transactions (TRANSFER_OUT & TRANSFER_IN)
     *
     * @param array $data
     * @return StockTransfer
     */
    public function transfer(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $userId = Auth::id();

            // Buat record utama untuk transfer
            $transfer = StockTransfer::create([
                'transfer_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'from_location_id' => $data['from_location_id'],
                'to_location_id' => $data['to_location_id'],
                'weight_grams' => $data['weight_grams'],
                'susut_grams' => $data['susut_grams'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'sorting_result_id' => $data['sorting_result_id'] ?? null,
                'created_by' => $userId,
            ]);

            // Buat dua transaksi inventory (OUT & IN)
            $this->createTransferTransactions($transfer, $data, $userId);

            return $transfer;
        });
    }

    /**
     * Buat dua transaksi inventory untuk transfer internal
     *
     * @param StockTransfer $transfer
     * @param array $data
     * @param int $userId
     * @return void
     */
    protected function createTransferTransactions(StockTransfer $transfer, array $data, int $userId): void
    {
        // Hitung total pengurangan (berat transfer + susut)
        $totalDeduction = abs($data['weight_grams']) + abs($data['susut_grams'] ?? 0);

        $supplierId = $this->getSupplierIdFromSortingResult($data['sorting_result_id'] ?? null);

        // TRANSFER_OUT dari lokasi asal (quantity negatif = berat + susut)
        InventoryTransaction::create([
            'transaction_date' => $data['transfer_date'] ?? now(),
            'grade_company_id' => $data['grade_company_id'],
            'location_id' => $data['from_location_id'],
            'supplier_id' => $supplierId,
            'quantity_change_grams' => -$totalDeduction,
            'transaction_type' => 'TRANSFER_OUT',
            'reference_id' => $transfer->id,
            'sorting_result_id' => $transfer->sorting_result_id,
            'created_by' => $userId,
        ]);

        // TRANSFER_IN ke lokasi tujuan (quantity positif = berat bersih)
        // KECUALI jika tujuan adalah DMK, maka barang dianggap hilang/keluar dari tracking (seperti penjualan)
        $toLocation = Location::find($data['to_location_id']);

        if ($toLocation && stripos($toLocation->name, 'DMK') === false) {
            InventoryTransaction::create([
                'transaction_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'location_id' => $data['to_location_id'],
                'supplier_id' => $supplierId,
                'quantity_change_grams' => abs($data['weight_grams']),
                'transaction_type' => 'TRANSFER_IN',
                'reference_id' => $transfer->id,
                'sorting_result_id' => $transfer->sorting_result_id,
                'created_by' => $userId,
            ]);
        }
    }

    /**
     * Proses transfer eksternal (dari supplier/partner ke internal):
     * - Insert ke stock_transfers
     * - Buat satu transaksi EXTERNAL_TRANSFER_IN (positif)
     *
     * @param array $data
     * @return StockTransfer
     */
    public function externalTransfer(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $userId = Auth::id();

            $transfer = StockTransfer::create([
                'transfer_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'from_location_id' => $data['from_location_id'], // Gudang Utama
                'to_location_id' => $data['to_location_id'], // Jasa Cuci
                'weight_grams' => $data['weight_grams'],
                'susut_grams' => $data['susut_grams'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'sorting_result_id' => $data['sorting_result_id'] ?? null,
                'created_by' => $userId,
            ]);

            // Hitung total pengurangan (berat transfer + susut)
            $totalDeduction = abs($data['weight_grams']) + abs($data['susut_grams'] ?? 0);

            $supplierId = $this->getSupplierIdFromSortingResult($data['sorting_result_id'] ?? null);

            // 1. EXTERNAL_TRANSFER_OUT (negatif) di Gudang Utama
            InventoryTransaction::create([
                'transaction_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'location_id' => $data['from_location_id'], // Gudang Utama
                'supplier_id' => $supplierId,
                'quantity_change_grams' => -$totalDeduction,
                'transaction_type' => 'EXTERNAL_TRANSFER_OUT',
                'reference_id' => $transfer->id,
                'sorting_result_id' => $transfer->sorting_result_id,
                'created_by' => $userId,
            ]);

            // 2. EXTERNAL_TRANSFER_IN (positif) di Jasa Cuci (Lokasi Tujuan)
            InventoryTransaction::create([
                'transaction_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'location_id' => $data['to_location_id'], // Jasa Cuci
                'supplier_id' => $supplierId,
                'quantity_change_grams' => abs($data['weight_grams']), // Hanya berat bersih yang masuk
                'transaction_type' => 'EXTERNAL_TRANSFER_IN',
                'reference_id' => $transfer->id,
                'sorting_result_id' => $transfer->sorting_result_id,
                'created_by' => $userId,
            ]);

            return $transfer;
        });
    }

    public function receiveInternal(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $userId = Auth::id();

            $transfer = StockTransfer::create([
                'transfer_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'from_location_id' => $data['from_location_id'], // IDM/DMK
                'to_location_id' => $data['to_location_id'], // Gudang Utama
                'weight_grams' => $data['weight_grams'],
                'notes' => $data['notes'] ?? null,
                'sorting_result_id' => $data['sorting_result_id'] ?? null,
                'created_by' => $userId,
            ]);

            $supplierId = $this->getSupplierIdFromSortingResult($data['sorting_result_id'] ?? null);

            // RECEIVE_INTERNAL_IN (positif) di Gudang Utama
            InventoryTransaction::create([
                'transaction_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'location_id' => $data['to_location_id'], // Gudang Utama
                'supplier_id' => $supplierId,
                'quantity_change_grams' => abs($data['weight_grams']),
                'transaction_type' => 'RECEIVE_INTERNAL_IN',
                'reference_id' => $transfer->id,
                'sorting_result_id' => $transfer->sorting_result_id,
                'created_by' => $userId,
            ]);

            return $transfer;
        });
    }

    public function receiveExternal(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $userId = Auth::id();

            $transfer = StockTransfer::create([
                'transfer_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'from_location_id' => $data['from_location_id'], // Jasa Cuci
                'to_location_id' => $data['to_location_id'], // Gudang Utama
                'weight_grams' => $data['weight_grams'],
                'susut_grams' => $data['susut_grams'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'sorting_result_id' => $data['sorting_result_id'] ?? null,
                'created_by' => $userId,
            ]);

            $supplierId = $this->getSupplierIdFromSortingResult($data['sorting_result_id'] ?? null);

            // 1. RECEIVE_EXTERNAL_IN (positif) di Gudang Utama
            InventoryTransaction::create([
                'transaction_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'location_id' => $data['to_location_id'], // Gudang Utama
                'supplier_id' => $supplierId,
                'quantity_change_grams' => abs($data['weight_grams']),
                'transaction_type' => 'RECEIVE_EXTERNAL_IN',
                'reference_id' => $transfer->id,
                'sorting_result_id' => $transfer->sorting_result_id,
                'created_by' => $userId,
            ]);

            // 2. RECEIVE_EXTERNAL_OUT (negatif) di Jasa Cuci (Lokasi Asal)
            // Total pengurangan = Berat diterima + Susut
            $totalDeduction = abs($data['weight_grams']) + abs($data['susut_grams'] ?? 0);

            InventoryTransaction::create([
                'transaction_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'location_id' => $data['from_location_id'], // Jasa Cuci
                'supplier_id' => $supplierId,
                'quantity_change_grams' => -$totalDeduction,
                'transaction_type' => 'RECEIVE_EXTERNAL_OUT',
                'reference_id' => $transfer->id,
                'sorting_result_id' => $transfer->sorting_result_id,
                'created_by' => $userId,
            ]);

            return $transfer;
        });
    }

    /**
     * Ambil stok per lokasi dengan grade dan lokasi relation
     *
     * @param int|null $gradeCompanyId Filter berdasarkan grade tertentu
     * @param int|null $locationId Filter berdasarkan lokasi tertentu
     * @return \Illuminate\Support\Collection
     */
    public function getStockPerLocation(?int $gradeCompanyId = null, ?int $locationId = null)
    {
        $query = InventoryTransaction::selectRaw('grade_company_id, location_id, SUM(quantity_change_grams) AS current_stock_grams');

        // Filter by grade jika diberikan
        if ($gradeCompanyId) {
            $query->where('grade_company_id', $gradeCompanyId);
        }

        // Filter by location jika diberikan
        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        $rows = $query
            ->groupBy('grade_company_id', 'location_id')
            ->having('current_stock_grams', '>', 0) // Hanya stok > 0
            ->get();

        // Attach relasi grade & lokasi untuk kemudahan akses
        $rows->load(['gradeCompany', 'location']);

        return $rows;
    }

    public function getAvailableStock(int $gradeCompanyId, int $locationId): float
    {
        $stock = (float) InventoryTransaction::where('grade_company_id', $gradeCompanyId)
            ->where('location_id', $locationId)
            ->sum('quantity_change_grams');

        if ($stock < 0) {
            \Illuminate\Support\Facades\Log::warning('Negative stock detected', [
                'grade_company_id' => $gradeCompanyId,
                'location_id' => $locationId,
                'stock' => $stock,
            ]);
        }

        return $stock;
    }

    public function getDisplayStock(int $gradeCompanyId, int $locationId): float
    {
        return max(0, $this->getAvailableStock($gradeCompanyId, $locationId));
    }
    /**
     * Atomic sell operation with row-level locking to prevent race conditions.
     * Uses SELECT FOR UPDATE to lock the inventory transaction rows during check.
     *
     * @param array $data
     * @return InventoryTransaction
     * @throws \Exception
     */
    public function sellWithLock(array $data): InventoryTransaction
    {
        return DB::transaction(function () use ($data) {
            $userId = Auth::id();
            $sortingResultId = $data['sorting_result_id'] ?? null;
            $locationId = $data['location_id'];
            $gradeCompanyId = $data['grade_company_id'];
            $weightGrams = abs($data['weight_grams']);

            // Lock and fetch batch remaining stock
            $batchRemaining = $this->getBatchRemainingStockWithLock($sortingResultId, $locationId);
            if ($batchRemaining < $weightGrams) {
                throw new \Exception('Stok batch tidak mencukupi. Tersedia: ' . number_format($batchRemaining, 2) . ' gr.');
            }

            // Lock and check global stock
            $sortingResult = SortingResult::lockForUpdate()->find($sortingResultId);
            $globalStock = $this->getGlobalStockWithLock($gradeCompanyId);
            if ($globalStock < $weightGrams) {
                throw new \Exception('Stok global tidak mencukupi. Tersedia: ' . number_format($globalStock, 2) . ' gr.');
            }

            // Lock and check location stock
            $locationStock = $this->getLocationStockWithLock($gradeCompanyId, $locationId);
            if ($locationStock < $weightGrams) {
                throw new \Exception('Stok lokasi tidak mencukupi. Tersedia: ' . number_format($locationStock, 2) . ' gr.');
            }

            $supplierId = $this->getSupplierIdFromSortingResult($sortingResultId);

            return InventoryTransaction::create([
                'transaction_date' => $data['transaction_date'] ?? now(),
                'grade_company_id' => $gradeCompanyId,
                'location_id' => $locationId,
                'quantity_change_grams' => -$weightGrams,
                'supplier_id' => $supplierId,
                'transaction_type' => 'SALE_OUT',
                'reference_id' => null,
                'sorting_result_id' => $sortingResultId,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Get batch remaining stock with row-level locking.
     */
    private function getBatchRemainingStockWithLock(int $sortingResultId, int $locationId): float
    {
        $sortingResult = SortingResult::lockForUpdate()->find($sortingResultId);
        if (!$sortingResult) return 0;

        $batchStock = (float) InventoryTransaction::where('sorting_result_id', $sortingResultId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->sum('quantity_change_grams');

        $globalStock = (float) InventoryTransaction::where('grade_company_id', $sortingResult->grade_company_id)
            ->lockForUpdate()
            ->sum('quantity_change_grams');

        $locationStock = (float) InventoryTransaction::where('grade_company_id', $sortingResult->grade_company_id)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->sum('quantity_change_grams');

        return max(0, min($batchStock, $locationStock, $globalStock));
    }

    /**
     * Get global stock with row-level locking.
     */
    private function getGlobalStockWithLock(int $gradeCompanyId): float
    {
        return (float) InventoryTransaction::where('grade_company_id', $gradeCompanyId)
            ->lockForUpdate()
            ->sum('quantity_change_grams');
    }

    /**
     * Get location stock with row-level locking.
     */
    private function getLocationStockWithLock(int $gradeCompanyId, int $locationId): float
    {
        return (float) InventoryTransaction::where('grade_company_id', $gradeCompanyId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->sum('quantity_change_grams');
    }


    public function hasEnoughStock(int $gradeCompanyId, int $locationId, float $requiredGrams): bool
    {
        // 1. Stok di gudang lokal
        $locationStock = $this->getAvailableStock($gradeCompanyId, $locationId);

        // 2. Stok di seluruh sistem (Net Global)
        $globalNetStock = (float) InventoryTransaction::where('grade_company_id', $gradeCompanyId)
            ->sum('quantity_change_grams');

        // Harus cukup di gudang lokal DAN cukup secara global
        return ($locationStock >= $requiredGrams) && ($globalNetStock >= $requiredGrams);
    }

    /**
     * Get ringkasan stok per grade (semua lokasi)
     *
     * @return \Illuminate\Support\Collection
     */
    public function getStockSummaryByGrade()
    {
        return InventoryTransaction::selectRaw('grade_company_id, SUM(quantity_change_grams) AS total_stock_grams')->groupBy('grade_company_id')->having('total_stock_grams', '>', 0)->with('gradeCompany')->get();
    }

    /**
     * Update transfer internal:
     * - Hapus transaksi lama (revert stok)
     * - Buat transaksi baru
     */
    public function updateTransfer(int $id, array $data): StockTransfer
    {
        return DB::transaction(function () use ($id, $data) {
            $transfer = StockTransfer::findOrFail($id);
            $userId = Auth::id();

            // 1. Hapus transaksi inventory lama (revert stok)
            $transfer->transactions()->delete();

            // 2. Update data transfer
            $transfer->update([
                'transfer_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'from_location_id' => $data['from_location_id'],
                'to_location_id' => $data['to_location_id'],
                'weight_grams' => $data['weight_grams'],
                'susut_grams' => $data['susut_grams'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'updated_by' => $userId, // Pastikan ada kolom updated_by atau abaikan jika tidak ada
            ]);

            // 3. Buat transaksi inventory baru sesuai tipe
            // Kita perlu tahu tipe transfernya. Karena method ini generic, kita bisa cek dari controller
            // Tapi untuk simplifikasi, kita asumsikan ini dipanggil oleh controller yang tahu konteksnya.
            // Namun, struktur StockTransfer tidak menyimpan "tipe" secara eksplisit selain lewat relasi inventory.
            // Jadi lebih aman jika logic create transaction dipisah atau dipass sebagai callback/parameter.

            // Refactor: Kita buat method update spesifik atau gunakan parameter type.
            // Untuk sekarang, mari kita buat updateTransferInternal, updateExternalTransfer, updateReceiveExternal

            return $transfer;
        });
    }

    public function updateTransferInternal(int $id, array $data): StockTransfer
    {
        return DB::transaction(function () use ($id, $data) {
            $transfer = StockTransfer::findOrFail($id);
            $userId = Auth::id();

            // 1. Hapus transaksi inventory lama
            $transfer->transactions()->delete();

            // 2. Update data transfer
            $transfer->update([
                'transfer_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'from_location_id' => $data['from_location_id'],
                'to_location_id' => $data['to_location_id'],
                'weight_grams' => $data['weight_grams'],
                'susut_grams' => $data['susut_grams'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            // 3. Buat transaksi baru (OUT & IN)
            $this->createTransferTransactions($transfer, $data, $userId);

            return $transfer;
        });
    }

    public function updateExternalTransfer(int $id, array $data): StockTransfer
    {
        return DB::transaction(function () use ($id, $data) {
            $transfer = StockTransfer::findOrFail($id);
            $userId = Auth::id();

            $transfer->transactions()->delete();

            $transfer->update([
                'transfer_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'from_location_id' => $data['from_location_id'],
                'to_location_id' => $data['to_location_id'],
                'weight_grams' => $data['weight_grams'],
                'susut_grams' => $data['susut_grams'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $totalDeduction = abs($data['weight_grams']) + abs($data['susut_grams'] ?? 0);

            $sortingResultId = $data['sorting_result_id'] ?? $transfer->sorting_result_id;
            $supplierId = $this->getSupplierIdFromSortingResult($sortingResultId);

            // 1. EXTERNAL_TRANSFER_OUT (negatif) di Gudang Utama
            InventoryTransaction::create([
                'transaction_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'location_id' => $data['from_location_id'],
                'supplier_id' => $supplierId,
                'quantity_change_grams' => -$totalDeduction,
                'transaction_type' => 'EXTERNAL_TRANSFER_OUT',
                'reference_id' => $transfer->id,
                'created_by' => $userId,
            ]);

            // 2. EXTERNAL_TRANSFER_IN (positif) di Jasa Cuci
            InventoryTransaction::create([
                'transaction_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'location_id' => $data['to_location_id'],
                'supplier_id' => $supplierId,
                'quantity_change_grams' => abs($data['weight_grams']),
                'transaction_type' => 'EXTERNAL_TRANSFER_IN',
                'reference_id' => $transfer->id,
                'created_by' => $userId,
            ]);

            return $transfer;
        });
    }

    public function updateReceiveExternal(int $id, array $data): StockTransfer
    {
        return DB::transaction(function () use ($id, $data) {
            $transfer = StockTransfer::findOrFail($id);
            $userId = Auth::id();

            $transfer->transactions()->delete();

            $transfer->update([
                'transfer_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'from_location_id' => $data['from_location_id'],
                'to_location_id' => $data['to_location_id'],
                'weight_grams' => $data['weight_grams'],
                'susut_grams' => $data['susut_grams'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $sortingResultId = $data['sorting_result_id'] ?? $transfer->sorting_result_id;
            $supplierId = $this->getSupplierIdFromSortingResult($sortingResultId);

            // 1. RECEIVE_EXTERNAL_IN (positif) di Gudang Utama
            InventoryTransaction::create([
                'transaction_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'location_id' => $data['to_location_id'],
                'supplier_id' => $supplierId,
                'quantity_change_grams' => abs($data['weight_grams']),
                'transaction_type' => 'RECEIVE_EXTERNAL_IN',
                'reference_id' => $transfer->id,
                'created_by' => $userId,
            ]);

            // 2. RECEIVE_EXTERNAL_OUT (negatif) di Jasa Cuci
            $totalDeduction = abs($data['weight_grams']) + abs($data['susut_grams'] ?? 0);

            InventoryTransaction::create([
                'transaction_date' => $data['transfer_date'] ?? now(),
                'grade_company_id' => $data['grade_company_id'],
                'location_id' => $data['from_location_id'],
                'supplier_id' => $supplierId,
                'quantity_change_grams' => -$totalDeduction,
                'transaction_type' => 'RECEIVE_EXTERNAL_OUT',
                'reference_id' => $transfer->id,
                'created_by' => $userId,
            ]);

            return $transfer;
        });
    }
    /**
     * Get Grade IDs based on Grading Source Filters (Supplier, Weight, Date)
     *
     * @param array $filters
     * @return array List of GradeCompany IDs
     */
    public function getGradesByFilter(array $filters): array
    {
        $query = \App\Models\SortingResult::query()->select('grade_company_id')->distinct();

        // Filter by Supplier Name
        if (!empty($filters['supplier_name'])) {
            $query->whereHas('receiptItem.purchaseReceipt.supplier', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['supplier_name'] . '%');
            });
        }

        // Filter by Weight Range (Grading Result Weight)
        if (!empty($filters['min_weight'])) {
            $query->where('weight_grams', '>=', $filters['min_weight']);
        }
        if (!empty($filters['max_weight'])) {
            $query->where('weight_grams', '<=', $filters['max_weight']);
        }

        // Filter by Grading Date
        if (!empty($filters['start_date'])) {
            $query->whereDate('grading_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('grading_date', '<=', $filters['end_date']);
        }

        // Filter by Outgoing Type (Automatic Filter)
        if (!empty($filters['outgoing_type'])) {
            $query->where('outgoing_type', $filters['outgoing_type']);
        }

        return $query->pluck('grade_company_id')->toArray();
    }

    /**
     * Get Grading Sources (Sorting Results) based on Outgoing Type
     *
     * @param string $outgoingType
     * @return \Illuminate\Database\Eloquent\Collection
     */
    /**
     * Get Grading Sources (Sorting Results) with adjusted stock weights.
     * This implements 'Global Budgeting' logic so the SUM of weights in dropdown
     * will never exceed the total Global Net Stock of the grade.
     *
     * OPTIMIZED: Pre-calculates all stock data in batch queries to avoid N+1.
     *
     * @param string $outgoingType
     * @param int $locationId
     * @return \Illuminate\Support\Collection
     */
    public function getGradingSourcesWithStock(string $outgoingType, int $locationId)
    {
        $sources = \App\Models\SortingResult::with(['gradeCompany', 'receiptItem.purchaseReceipt.supplier'])
            ->where('outgoing_type', $outgoingType)
            ->orderBy('grading_date', 'desc')
            ->get();

        if ($sources->isEmpty()) {
            return $sources;
        }

        // Pre-fetch all grade IDs grouped by parent
        $gradeIdsByParent = [];
        $orphanGradeIds = [];
        $gradeIdToParentMap = [];

        foreach ($sources as $source) {
            $grade = $source->gradeCompany;
            if (!$grade) continue;

            $gradeId = $grade->id;
            $parentId = $grade->parent_grade_company_id;
            $gradeIdToParentMap[$gradeId] = $parentId;

            if ($parentId) {
                if (!isset($gradeIdsByParent[$parentId])) {
                    $gradeIdsByParent[$parentId] = [];
                }
                $gradeIdsByParent[$parentId][] = $gradeId;
            } else {
                $orphanGradeIds[] = $gradeId;
            }
        }

        // Pre-calculate ALL budget pools in ONE query per parent group
        $budgetPools = [];
        foreach ($gradeIdsByParent as $parentId => $childIds) {
            $total = (float) InventoryTransaction::whereIn('grade_company_id', $childIds)
                ->sum('quantity_change_grams');
            $budgetPools["parent_{$parentId}"] = $total;
        }
        // Handle orphan grades (no parent)
        foreach ($orphanGradeIds as $gradeId) {
            $total = (float) InventoryTransaction::where('grade_company_id', $gradeId)
                ->sum('quantity_change_grams');
            $budgetPools["grade_{$gradeId}"] = $total;
        }

        // Pre-calculate ALL location budgets in ONE query
        $allGradeIds = array_merge($orphanGradeIds, array_merge(...array_values($gradeIdsByParent)));
        $locationBudgetResults = InventoryTransaction::select('grade_company_id')
            ->selectRaw('SUM(quantity_change_grams) as total_stock')
            ->whereIn('grade_company_id', $allGradeIds)
            ->where('location_id', $locationId)
            ->groupBy('grade_company_id')
            ->pluck('total_stock', 'grade_company_id')
            ->toArray();
        $locationBudgets = array_map('floatval', $locationBudgetResults);

        // Pre-calculate ALL batch stocks in ONE query
        $sourceIds = $sources->pluck('id')->toArray();
        $batchStockResults = InventoryTransaction::select('sorting_result_id')
            ->selectRaw('SUM(quantity_change_grams) as batch_stock')
            ->whereIn('sorting_result_id', $sourceIds)
            ->where('location_id', $locationId)
            ->groupBy('sorting_result_id')
            ->pluck('batch_stock', 'sorting_result_id')
            ->toArray();
        $batchStocks = array_map('floatval', $batchStockResults);

        // Now iterate using pre-calculated data (NO additional queries)
        return $sources->map(function ($source) use ($locationId, &$budgetPools, &$locationBudgets, $batchStocks, $gradeIdToParentMap) {
            $grade = $source->gradeCompany;
            if (!$grade) return null;

            $gradeId = $grade->id;
            $parentId = $gradeIdToParentMap[$gradeId] ?? null;
            $budgetKey = $parentId ? "parent_{$parentId}" : "grade_{$gradeId}";

            // Get pre-calculated values (default to 0 if not found)
            $batchStock = $batchStocks[$source->id] ?? 0;
            $locationBudget = $locationBudgets[$gradeId] ?? 0;
            $globalBudget = $budgetPools[$budgetKey] ?? 0;

            // Calculate display weight
            $displayWeight = max(0, min($batchStock, $locationBudget, $globalBudget));

            // Update running totals
            $locationBudgets[$gradeId] = ($locationBudgets[$gradeId] ?? 0) - $displayWeight;
            $budgetPools[$budgetKey] = ($budgetPools[$budgetKey] ?? 0) - $displayWeight;

            // Info tambahan untuk UI
            $source->adjusted_weight = $displayWeight;
            $source->real_global_stock = $budgetPools[$budgetKey] + $displayWeight;

            return $source;
        })->filter(function ($source) {
            return $source && $source->adjusted_weight > 0;
        });
    }
}
