<?php

namespace App\Services\GradingGoods;

use Exception;
use App\Models\ReceiptItem;
use App\Models\GradeCompany;
use App\Models\InventoryTransaction;
use App\Models\Location;
use App\Models\SortingResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GradingGoodsService
{
    public function getAllGradeCompanies()
    {
        return GradeCompany::orderBy('name')->get();
    }

    public function getAllGrading($filters = [], $perPage = 15)
    {
        $query = ReceiptItem::select([
                'receipt_items.id as receipt_item_id',
                'receipt_items.warehouse_weight_grams',
                'receipt_items.supplier_weight_grams',
                'receipt_items.status',
                'grades_supplier.name as grade_supplier_name',
                'purchase_receipts.receipt_date',
                'suppliers.name as supplier_name',
                DB::raw('MIN(sorting_results.grading_date) as grading_date'),
                DB::raw('COUNT(sorting_results.id) as total_grades'),
                DB::raw('SUM(sorting_results.weight_grams) as total_grading_weight'),
                DB::raw('MIN(sorting_results.id) as first_sorting_id')
            ])
            ->join('sorting_results', 'receipt_items.id', '=', 'sorting_results.receipt_item_id')
            ->leftJoin('grades_supplier', 'receipt_items.grade_supplier_id', '=', 'grades_supplier.id')
            ->leftJoin('purchase_receipts', 'receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
            ->leftJoin('suppliers', 'purchase_receipts.supplier_id', '=', 'suppliers.id')
            ->groupBy([
                'receipt_items.id',
                'receipt_items.warehouse_weight_grams',
                'receipt_items.supplier_weight_grams',
                'receipt_items.status',
                'grades_supplier.name',
                'purchase_receipts.receipt_date',
                'suppliers.name'
            ])
            ->orderBy('grading_date', 'desc')
            ->orderBy('suppliers.name', 'asc');

        // Apply filters
        if (!empty($filters['month'])) {
            $query->whereMonth('sorting_results.grading_date', $filters['month']);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('sorting_results.grading_date', $filters['year']);
        }

        $results = $query->paginate($perPage)->appends(request()->query());

        $results->getCollection()->transform(function ($item) {
            $warehouseWeight = $item->warehouse_weight_grams ?? 0;
            $totalGradingWeight = $item->total_grading_weight ?? 0;
            $item->grading_difference = $totalGradingWeight - $warehouseWeight;

            return $item;
        });

        return $results;
    }

    public function getAllGradingForExport($filters = [])
    {
        $query = ReceiptItem::select([
                'receipt_items.id as receipt_item_id',
                'receipt_items.warehouse_weight_grams',
                'receipt_items.supplier_weight_grams',
                'receipt_items.status',
                'grades_supplier.name as grade_supplier_name',
                'purchase_receipts.receipt_date',
                'suppliers.name as supplier_name',
                DB::raw('MIN(sorting_results.grading_date) as grading_date'),
                DB::raw('COUNT(sorting_results.id) as total_grades'),
                DB::raw('SUM(sorting_results.weight_grams) as total_grading_weight'),
                DB::raw('MIN(sorting_results.id) as first_sorting_id')
            ])
            ->join('sorting_results', 'receipt_items.id', '=', 'sorting_results.receipt_item_id')
            ->leftJoin('grades_supplier', 'receipt_items.grade_supplier_id', '=', 'grades_supplier.id')
            ->leftJoin('purchase_receipts', 'receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
            ->leftJoin('suppliers', 'purchase_receipts.supplier_id', '=', 'suppliers.id')
            ->groupBy([
                'receipt_items.id',
                'receipt_items.warehouse_weight_grams',
                'receipt_items.supplier_weight_grams',
                'receipt_items.status',
                'grades_supplier.name',
                'purchase_receipts.receipt_date',
                'suppliers.name'
            ])
            ->orderBy('grading_date', 'desc')
            ->orderBy('suppliers.name', 'asc');

        // Apply filters
        if (!empty($filters['month'])) {
            $query->whereMonth('sorting_results.grading_date', $filters['month']);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('sorting_results.grading_date', $filters['year']);
        }

        $results = $query->get();

        $results->transform(function ($item) {
            $warehouseWeight = $item->warehouse_weight_grams ?? 0;
            $totalGradingWeight = $item->total_grading_weight ?? 0;

            $item->grading_difference = $totalGradingWeight - $warehouseWeight;

            return $item;
        });

        return $results;
    }

    public function getSortingResultsByReceiptItem($receiptItemId)
    {
        return SortingResult::with(['gradeCompany', 'receiptItem.gradeSupplier', 'receiptItem.purchaseReceipt.supplier'])
            ->where('receipt_item_id', $receiptItemId)
            ->orderBy('weight_grams', 'desc')
            ->get();
    }

    public function getReceiptItemsByGradeSupplierName($name = null)
    {
        $query = ReceiptItem::select(
            'receipt_items.id',
            'receipt_items.warehouse_weight_grams',
            'receipt_items.supplier_weight_grams',
            'grades_supplier.name as grade_supplier_name',
            'grades_supplier.image_url as grade_supplier_image_url',
            'purchase_receipts.id as purchase_receipt_id',
            'purchase_receipts.receipt_date',
            'suppliers.name as supplier_name',
        )
            ->join('purchase_receipts', 'receipt_items.purchase_receipt_id', '=', 'purchase_receipts.id')
            ->leftJoin('grades_supplier', 'receipt_items.grade_supplier_id', '=', 'grades_supplier.id')
            ->leftJoin('suppliers', 'purchase_receipts.supplier_id', '=', 'suppliers.id')
            ->where('receipt_items.status', ReceiptItem::STATUS_MENTAH)
            ->whereDoesntHave('sortingResults')
            ->orderBy('purchase_receipts.receipt_date', 'desc');

        if (!empty($name)) {
            $query->where('grades_supplier.name', 'like', '%' . $name . '%');
        }

        return $query->get();
    }

    public function createSortingResultStep1($gradingDate, $receiptItemId)
    {
        $data = [
            'grading_date' => $gradingDate,
            'receipt_item_id' => $receiptItemId,
            'grade_company_id' => null,
            'weight_grams' => null,
            'quantity' => null,
            'percentage_difference' => null,
            'notes' => null,
            'created_by' => Auth::id(),
        ];

        return SortingResult::create($data);
    }

    public function getSortingResultWithRelations($id)
    {
        return SortingResult::with(['receiptItem.purchaseReceipt.supplier', 'receiptItem.gradeSupplier', 'gradeCompany'])->find($id);
    }

    public function updateMultipleSortingResults($receiptItemId, array $gradesData, $globalNotes = null)
    {
        try {
            return DB::transaction(function () use ($receiptItemId, $gradesData, $globalNotes) {
                $receiptItem = ReceiptItem::findOrFail($receiptItemId);

                // Hapus semua sorting results lama untuk receipt item ini
                $oldSortingResults = SortingResult::where('receipt_item_id', $receiptItemId)->get();
                foreach ($oldSortingResults as $oldResult) {
                    $this->deleteInventoryFromGrading($oldResult->id);
                }
                SortingResult::where('receipt_item_id', $receiptItemId)->delete();

                $createdResults = [];

                // Buat sorting result baru untuk setiap grade
                foreach ($gradesData as $index => $gradeData) {
                    $gradeCompany = GradeCompany::firstOrCreate(
                        ['name' => $gradeData['grade_company_name']],
                        ['name' => $gradeData['grade_company_name']]
                    );

                    $warehouseWeight = $receiptItem->warehouse_weight_grams;

                    // ✅ FIX: Pastikan casting ke integer
                    $weightGrams = (int) $gradeData['weight_grams'];
                    $quantity = (int) $gradeData['quantity'];

                    $percentageDifference = $warehouseWeight > 0
                        ? abs((($weightGrams - $warehouseWeight) / $warehouseWeight) * 100)
                        : 0;

                    // Gabungkan catatan
                    $notes = collect([
                        $globalNotes,
                        $gradeData['notes'] ?? null,
                        $index > 0 ? 'Grade ke-' . ($index + 1) . ' dari grading berganda' : null
                    ])->filter()->implode('. ');

                    $sortingResult = SortingResult::create([
                        'grading_date' => $gradeData['grading_date'],
                        'receipt_item_id' => $receiptItemId,
                        'quantity' => $quantity, // ✅ Sudah integer
                        'grade_company_id' => $gradeCompany->id,
                        'weight_grams' => $weightGrams, // ✅ Sudah integer
                        'percentage_difference' => round($percentageDifference, 2),
                        'notes' => $notes,
                        'outgoing_type' => $gradeData['outgoing_type'] ?? null,
                        'category_grade' => $gradeData['category_grade'] ?? null,
                        'created_by' => auth()->id(),
                    ]);

                    $this->createInventoryFromGrading($sortingResult);
                    $createdResults[] = $sortingResult;
                }

                return $createdResults;
            });
        } catch (Exception $e) {
            Log::error('Gagal update multiple grading: ' . $e->getMessage(), [
                'receipt_item_id' => $receiptItemId,
                'error' => $e->getMessage(),
                'grades_data' => $gradesData
            ]);
            throw $e;
        }
    }

    public function updateSortingResultStep2Multiple($sortingResultId, array $grades, $globalNotes = null)
    {
        try {
            return DB::transaction(function () use ($sortingResultId, $grades, $globalNotes) {
                // Ambil data original SEBELUM dihapus
                $originalSortingResult = SortingResult::with(['receiptItem', 'receiptItem.purchaseReceipt.supplier'])->find($sortingResultId);

                if (!$originalSortingResult) {
                    throw new Exception('Data grading tidak ditemukan');
                }

                // Simpan data yang diperlukan sebelum dihapus
                $gradingDate = $originalSortingResult->grading_date;
                $receiptItemId = $originalSortingResult->receipt_item_id;
                $receiptItem = $originalSortingResult->receiptItem;
                $originalWeight = $receiptItem->warehouse_weight_grams;

                // Hapus sorting result yang existing
                SortingResult::where('id', $sortingResultId)->delete();

                $createdResults = [];

                // Buat sorting result untuk setiap grade
                foreach ($grades as $index => $gradeData) {
                    // Cari atau buat grade company
                    $gradeCompany = GradeCompany::firstOrCreate(['name' => $gradeData['grade_company_name']], ['name' => $gradeData['grade_company_name']]);

                    // ✅ FIX: Pastikan casting ke integer
                    $weightGrams = (int) $gradeData['weight_grams'];
                    $quantity = (int) $gradeData['quantity'];

                    // ✅ FIX: Hitung persentase berdasarkan berat asal gudang (bukan selisih dengan original)
                    $percentageDifference = $originalWeight > 0 ? abs((($weightGrams - $originalWeight) / $originalWeight) * 100) : 0;

                    // Gabungkan catatan
                    $notes = collect([$globalNotes, $gradeData['notes'] ?? null, $index > 0 ? 'Grade ke-' . ($index + 1) . ' dari grading berganda' : null])
                        ->filter()
                        ->implode('. ');

                    // Buat sorting result baru
                    $sortingResult = SortingResult::create([
                        'grading_date' => $gradingDate,
                        'receipt_item_id' => $receiptItemId,
                        'quantity' => $quantity, // ✅ Sudah integer
                        'grade_company_id' => $gradeCompany->id,
                        'weight_grams' => $weightGrams, // ✅ Sudah integer
                        'percentage_difference' => round($percentageDifference, 2),
                        'notes' => $notes,
                        'outgoing_type' => $gradeData['outgoing_type'] ?? null,
                        'category_grade' => $gradeData['category_grade'] ?? null,
                        'created_by' => auth()->id(),
                    ]);

                    $this->createInventoryFromGrading($sortingResult);

                    $createdResults[] = $sortingResult;
                }

                // Update status receipt item menjadi selesai disortir
                $receiptItem->update(['status' => ReceiptItem::STATUS_SELESAI_DISORTIR]);

                Log::info('Multiple grades grading completed', [
                    'receipt_item_id' => $receiptItem->id,
                    'grades_count' => count($grades),
                    'total_weight' => collect($grades)->sum('weight_grams'),
                    'original_weight' => $originalWeight,
                ]);

                return $createdResults;
            });
        } catch (Exception $e) {
            Log::error('Gagal melakukan grading berganda: ' . $e->getMessage(), [
                'sorting_result_id' => $sortingResultId,
                'grades_count' => count($grades),
                'user_id' => auth()->id(),
                'error_trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function updateFullGrading($sortingResultId, array $data)
    {
        return DB::transaction(function () use ($sortingResultId, $data) {
            $sorting = SortingResult::findOrFail($sortingResultId);
            $receiptItem = ReceiptItem::findOrFail($data['receipt_item_id']);
            $gradeCompany = GradeCompany::firstOrCreate(['name' => $data['grade_company_name']], ['image_url' => null, 'description' => null]);

            $warehouseWeight = floatval($receiptItem->warehouse_weight_grams);
            $gradingWeight = floatval($data['weight_grams']);

            // ✅ FIX: Hitung persentase selisih dengan berat gudang (selalu positif)
            $percentage = null;
            if ($warehouseWeight > 0) {
                $percentage = round(abs(($gradingWeight - $warehouseWeight) / $warehouseWeight) * 100, 2);
            }

            $sorting->grading_date = $data['grading_date'];
            $sorting->receipt_item_id = $data['receipt_item_id'];
            $sorting->quantity = $data['quantity'];
            $sorting->grade_company_id = $gradeCompany->id;
            $sorting->weight_grams = $gradingWeight;
            $sorting->notes = $data['notes'];
            $sorting->percentage_difference = $percentage;
            $sorting->save();

            $this->updateInventoryFromGrading($sorting);

            return $sorting;
        });
    }

    public function deleteGrading($receiptItemId)
    {
        try {
            return DB::transaction(function () use ($receiptItemId) {
                // ✅ Hapus semua sorting results untuk receipt item ini
                $sortingResults = SortingResult::where('receipt_item_id', $receiptItemId)->get();

                foreach ($sortingResults as $sorting) {
                    $this->deleteInventoryFromGrading($sorting->id);
                    $sorting->delete();
                }

                // ✅ Kembalikan status receipt item ke MENTAH
                $receiptItem = ReceiptItem::find($receiptItemId);
                if ($receiptItem) {
                    $receiptItem->update(['status' => ReceiptItem::STATUS_MENTAH]);
                }

                return true;
            });
        } catch (Exception $e) {
            Log::error('Gagal hapus grading: ' . $e->getMessage());
            throw $e;
        }
    }

    private function createInventoryFromGrading(SortingResult $sortingResult)
    {
        $defaultLocation = Location::where('name', 'Gudang Utama')->first();

        if (!$defaultLocation || !$sortingResult->grade_company_id || !$sortingResult->weight_grams) {
            return;
        }

        // 1. CARI SUPPLIER ID
        // Alur: SortingResult -> ReceiptItem -> PurchaseReceipt -> Supplier
        $supplierId = null;
        if ($sortingResult->receiptItem &&
            $sortingResult->receiptItem->purchaseReceipt) {
            $supplierId = $sortingResult->receiptItem->purchaseReceipt->supplier_id;
        }

        InventoryTransaction::create([
            'transaction_date' => $sortingResult->grading_date,
            'grade_company_id' => $sortingResult->grade_company_id,
            'location_id' => $defaultLocation->id,
            'supplier_id' => $supplierId, // <--- SIMPAN ID SUPPLIER DISINI
            'quantity_change_grams' => abs($sortingResult->weight_grams),
            'transaction_type' => 'GRADING_IN',
            'reference_id' => $sortingResult->id,
            'sorting_result_id' => $sortingResult->id,
            'outgoing_type' => $sortingResult->outgoing_type,
            'category_grade' => $sortingResult->category_grade,
            'created_by' => $sortingResult->created_by,
        ]);

        Log::info('Inventory created from grading', ['sorting_result_id' => $sortingResult->id]);
    }

    private function updateInventoryFromGrading(SortingResult $sortingResult)
    {
        $inventoryTx = InventoryTransaction::where('transaction_type', 'GRADING_IN')
            ->where('reference_id', $sortingResult->id)
            ->first();

        if ($inventoryTx) {
            $inventoryTx->update([
                'transaction_date' => $sortingResult->grading_date,
                'grade_company_id' => $sortingResult->grade_company_id,
                'quantity_change_grams' => abs($sortingResult->weight_grams),
            ]);
        } else {
            $this->createInventoryFromGrading($sortingResult);
        }
    }

    private function deleteInventoryFromGrading($sortingResultId)
    {
        InventoryTransaction::where('transaction_type', 'GRADING_IN')
            ->where('reference_id', $sortingResultId)
            ->delete();
    }
}
