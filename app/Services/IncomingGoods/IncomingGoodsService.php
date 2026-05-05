<?php

namespace App\Services\IncomingGoods;

use App\Exports\IncomingGoodsExport;
use App\Models\PurchaseReceipt;
use App\Models\ReceiptItem;
use App\Models\Supplier;
use App\Models\GradeSupplier;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class IncomingGoodsService
{
    public function getAllReceipts($filters = [])
    {
        $query = PurchaseReceipt::with(['supplier', 'receiptItems.gradeSupplier']);

        // Apply date filters
        if (!empty($filters['month'])) {
            $query->whereMonth('receipt_date', $filters['month']);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('receipt_date', $filters['year']);
        }

        return $query->latest('receipt_date')->paginate(10);
    }

    /**
     * Get all suppliers for dropdown
     */
    public function getSuppliers()
    {
        return Supplier::orderBy('name')->get();
    }

    /**
     * Get all grade suppliers for card checkbox
     */
    public function getGradeSuppliers()
    {
        return GradeSupplier::orderBy('name')->get();
    }

    /**
     * Get selected grade suppliers by IDs
     */
    public function getSelectedGrades(array $gradeIds)
    {
        return GradeSupplier::whereIn('id', $gradeIds)->orderBy('name')->get();
    }

    /**
     * Get supplier by ID
     */
    public function getSupplierById($supplierId)
    {
        return Supplier::findOrFail($supplierId);
    }

    /**
     * Get receipt by ID with relationships
     */
    public function getReceiptById($id)
    {
        return PurchaseReceipt::with([
            'supplier',
            'receiptItems.gradeSupplier',
            'receiptItems.sortingResults',
        ])->findOrFail($id);
    }

    /**
     * Create purchase receipt and items (Final Step)
     */
    public function createPurchaseReceipt(array $step1Data, array $step2Data, array $step3Data)
    {
        try {
            return DB::transaction(function () use ($step1Data, $step2Data, $step3Data) {

                // Create parent record (Purchase Receipt)
                $receipt = PurchaseReceipt::create([
                    'supplier_id' => $step1Data['supplier_id'],
                    'receipt_date' => $step1Data['receipt_date'],
                    'unloading_date' => $step1Data['unloading_date'],
                    'notes' => $step1Data['notes'] ?? null,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                foreach ($step1Data['grade_ids'] as $gradeId) {
                    $beratAwal = $step2Data['berat_awal'][$gradeId] ?? 0;
                    $kadarAir = $step2Data['kadar_air'][$gradeId] ?? 0;
                    $beratAkhir = $step3Data['berat_akhir'][$gradeId] ?? 0;

                    // Calculate difference
                    $selisih = $beratAkhir - $beratAwal;

                    // ✅ FIX: Calculate percentage correctly
                    $percentageDifference = null;
                    if ($beratAwal > 0) {
                        $percentageDifference = ($selisih / $beratAwal) * 100;
                    }

                    $isFlagged = $percentageDifference !== null
                        && abs($percentageDifference) > ReceiptItem::FLAG_THRESHOLD_PERCENT;

                    ReceiptItem::create([
                        'purchase_receipt_id' => $receipt->id,
                        'grade_supplier_id' => $gradeId,
                        'supplier_weight_grams' => $beratAwal,
                        'warehouse_weight_grams' => $beratAkhir,
                        'difference_grams' => $selisih,
                        'percentage_difference' => $percentageDifference,  // ✅ Correct calculation
                        'moisture_percentage' => $kadarAir,
                        'is_flagged_red' => $isFlagged,  // ✅ 2% threshold
                        'status' => ReceiptItem::STATUS_MENTAH,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }

                return $receipt->load(['supplier', 'receiptItems.gradeSupplier']);
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Update purchase receipt and items
     */
    public function updateReceipt($id, array $data)
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $receipt = PurchaseReceipt::with('receiptItems.sortingResults')->findOrFail($id);

                // Validasi: Jika ada item yang sudah di-grading, blok edit
                foreach ($receipt->receiptItems as $item) {
                    if ($item->sortingResults()->exists()) {
                        throw new \Exception('Tidak dapat edit. Items sudah di-grading.');
                    }
                }

                // Update receipt basic info
                $receipt->update([
                    'supplier_id' => $data['supplier_id'],
                    'receipt_date' => $data['receipt_date'],
                    'unloading_date' => $data['unloading_date'],
                    'notes' => $data['notes'] ?? null,
                    'updated_by' => auth()->id(),
                ]);

                // Delete existing items (dengan audit trail deleted_by)
                $receipt->receiptItems()->get()->each(function ($item) {
                    $item->deleted_by = auth()->id();
                    $item->save();
                    $item->delete();
                });

                // Create new items
                foreach ($data['items'] as $itemData) {
                    $supplierWeight = $itemData['supplier_weight_grams'];
                    $warehouseWeight = $itemData['warehouse_weight_grams'];
                    $difference = $warehouseWeight - $supplierWeight;

                    $percentageDifference = null;
                    if ($supplierWeight > 0) {
                        $percentageDifference = ($difference / $supplierWeight) * 100;
                    }

                    $isFlagged = $percentageDifference !== null
                        && abs($percentageDifference) > ReceiptItem::FLAG_THRESHOLD_PERCENT;

                    ReceiptItem::create([
                        'purchase_receipt_id' => $receipt->id,
                        'grade_supplier_id' => $itemData['grade_supplier_id'],
                        'supplier_weight_grams' => $supplierWeight,
                        'warehouse_weight_grams' => $warehouseWeight,
                        'difference_grams' => $difference,
                        'percentage_difference' => $percentageDifference,
                        'moisture_percentage' => $itemData['moisture_percentage'] ?? null,
                        'is_flagged_red' => $isFlagged,
                        'status' => ReceiptItem::STATUS_MENTAH,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }

                return $receipt->load(['supplier', 'receiptItems.gradeSupplier']);
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Delete purchase receipt and its items
     */
    public function deleteReceipt($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $receipt = PurchaseReceipt::with('receiptItems.sortingResults')->findOrFail($id);

                // Validasi: Jika ada item yang sudah di-grading, blok delete
                foreach ($receipt->receiptItems as $item) {
                    if ($item->sortingResults()->exists()) {
                        throw new \Exception('Tidak dapat hapus. Items sudah di-grading.');
                    }
                }

                // delete related items (trigger observer)
                $receipt->receiptItems()->get()->each(function ($item) {
                    $item->deleted_by = auth()->id();
                    $item->save();
                    $item->delete();
                });

                $receipt->deleted_by = auth()->id();
                $receipt->save();
                $receipt->delete();

                return true;
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Clear wizard session data
     */
    public function clearWizardSession()
    {
        session()->forget(['step1_data', 'step2_data']);
    }

    /**
     * Export Location to Excel
     *
     */
    public function exportToExcel($filters = [])
    {
        try {
            // Generate filename with filter info
            $filename = 'laporan_barang_masuk';

            if (!empty($filters['month'])) {
                $filename .= '_bulan_' . $filters['month'];
            }
            if (!empty($filters['year'])) {
                $filename .= '_tahun_' . $filters['year'];
            }

            $filename .= '_' . date('Y-m-d') . '.xlsx';

            return Excel::download(new IncomingGoodsExport($filters), $filename);
        } catch (\Exception $e) {
            Log::error('Export failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
