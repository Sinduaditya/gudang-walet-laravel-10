<?php

namespace App\Services\SortMaterial;

use App\Exports\SortMaterialExport;
use App\Models\GradeCompany;
use App\Models\InventoryTransaction;
use App\Models\Location;
use App\Models\ParentGradeCompany;
use App\Models\SortingResult;
use App\Models\SortMaterial;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SortMaterialService
{
    /**
     * Parent grades yang menggunakan sistem Sortir Global
     */
    private const GLOBAL_PARENT_GRADES = ['ALU', 'AA2 AF JUAL', 'AF', 'Indomie P'];

    /**
     * Destination options untuk tracking aliran barang
     */
    public const DESTINATIONS = [
        'mangkok' => 'Mangkok',
        'idm' => 'IDM',
        'aa' => 'AA',
        'af' => 'Lempeng',
    ];

    public function getAll(?string $search = null)
    {
        $query = SortMaterial::with('parentGradeCompany');

        if ($search) {
            $query->whereHas('parentGradeCompany', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate(10)->withQueryString();
    }

    public function getById(int $id)
    {
        return SortMaterial::findOrFail($id);
    }

    /**
     * =============================================
     * EXISTING LOGIC - DON'T MODIFY
     * =============================================
     */
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $sortMaterial = SortMaterial::create($data);
            $parentGrade = ParentGradeCompany::findOrFail($data['parent_grade_company_id']);
            $parentGrade->increment('stock', $data['weight']);
            return $sortMaterial;
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $sortMaterial = $this->getById($id);
            $oldWeight = $sortMaterial->weight;
            $oldParentId = $sortMaterial->parent_grade_company_id;

            $sortMaterial->update($data);

            $oldParentGrade = ParentGradeCompany::findOrFail($oldParentId);
            $oldParentGrade->decrement('stock', $oldWeight);

            $newParentGrade = ParentGradeCompany::findOrFail($data['parent_grade_company_id']);
            $newParentGrade->increment('stock', $data['weight']);

            return $sortMaterial;
        });
    }

    public function delete(int $id)
    {
        return DB::transaction(function () use ($id) {
            $sortMaterial = $this->getById($id);
            $originalWeight = $sortMaterial->weight;
            $originalDestination = $sortMaterial->destination;
            $sortingResultId = $sortMaterial->sorting_result_id;

            // Find the original SALE_OUT transaction from this sorting
            if ($sortingResultId) {
                $originalTx = InventoryTransaction::where('sorting_result_id', $sortingResultId)
                    ->where('transaction_type', 'SALE_OUT')
                    ->first();

                if ($originalTx) {
                    // Create revert transaction to restore stock
                    InventoryTransaction::create([
                        'transaction_date' => now(),
                        'grade_company_id' => $originalTx->grade_company_id,
                        'location_id' => $originalTx->location_id,
                        'supplier_id' => $originalTx->supplier_id,
                        'quantity_change_grams' => abs($originalTx->quantity_change_grams),
                        'transaction_type' => 'SALE_REVERT',
                        'reference_id' => $originalTx->id,
                        'sorting_result_id' => $sortingResultId,
                        'notes' => 'Revert delete sortir bahan ID: ' . $id . ' - destination: ' . ($originalDestination ?? 'unknown'),
                        'created_by' => auth()->id(),
                    ]);
                }

                // Delete sorting_result (cascade nullifies inventory_transactions reference)
                SortingResult::where('id', $sortingResultId)->delete();
            }

            $sortMaterial->deleted_by = auth()->id();
            $sortMaterial->save();
            $sortMaterial->delete();
            return true;
        });
    }

    /**
     * =============================================
     * NEW: GLOBAL SORTING LOGIC FOR ALU, AF, INDOMIE P
     * =============================================
     */

    /**
     * Get grades dengan stock GLOBAL per parent grade ALU, AF, Indomie P
     */
    public function getGradesWithGlobalStock(): Collection
    {
        $parentGrades = ParentGradeCompany::whereIn('name', self::GLOBAL_PARENT_GRADES)->get();

        $childGradeIds = GradeCompany::whereIn('parent_grade_company_id', $parentGrades->pluck('id'))
            ->pluck('id')
            ->toArray();

        $globalStocks = InventoryTransaction::select('grade_company_id')
            ->selectRaw('SUM(quantity_change_grams) as total_stock')
            ->whereIn('grade_company_id', $childGradeIds)
            ->whereNull('deleted_at')
            ->groupBy('grade_company_id')
            ->pluck('total_stock', 'grade_company_id')
            ->toArray();

        $grades = GradeCompany::with(['parentGradeCompany'])
            ->whereIn('parent_grade_company_id', $parentGrades->pluck('id'))
            ->orderBy('name')
            ->get();

        $grades->each(function ($grade) use ($globalStocks) {
            $grade->global_stock = isset($globalStocks[$grade->id])
                ? (float) $globalStocks[$grade->id]
                : 0.0;
        });

        return $grades->filter(function ($grade) {
            return $grade->global_stock > 0;
        })->values();
    }

    /**
     * Get global stock untuk satu grade company
     */
    public function getGlobalStock(int $gradeCompanyId): float
    {
        return (float) InventoryTransaction::where('grade_company_id', $gradeCompanyId)
            ->whereNull('deleted_at')
            ->sum('quantity_change_grams');
    }

    /**
     * Get grade dengan relasinya
     */
    public function getGradeWithRelations(int $gradeCompanyId): ?GradeCompany
    {
        return GradeCompany::with(['parentGradeCompany'])->find($gradeCompanyId);
    }

    /**
     * Get supplier info dari sorting results terakhir
     */
    public function getSupplierInfo(int $gradeCompanyId): ?Supplier
    {
        $sortingResult = SortingResult::with(['receiptItem.purchaseReceipt.supplier'])
            ->where('grade_company_id', $gradeCompanyId)
            ->orderBy('grading_date', 'desc')
            ->first();

        if ($sortingResult && $sortingResult->receiptItem && $sortingResult->receiptItem->purchaseReceipt) {
            return $sortingResult->receiptItem->purchaseReceipt->supplier;
        }

        return null;
    }

    /**
     * Check apakah grade termasuk ALU (parent grade ALU)
     */
    public function isAlu(GradeCompany $grade): bool
    {
        return $grade->parentGradeCompany && $grade->parentGradeCompany->name === 'ALU';
    }

    /**
     * Check apakah grade termasuk AA2 AF JUAL (parent grade AA2 AF JUAL)
     */
    public function isAfJual(GradeCompany $grade): bool
    {
        return $grade->parentGradeCompany && $grade->parentGradeCompany->name === 'AA2 AF JUAL';
    }

    /**
     * Check apakah grade termasuk ALU atau AA2 AF JUAL (SALE_OUT only, no GRADING_IN)
     */
    public function isGlobalSortExit(GradeCompany $grade): bool
    {
        return $this->isAlu($grade) || $this->isAfJual($grade);
    }

    /**
     * Get list destinations untuk dropdown
     * Returns different destinations based on parent grade name
     */
    public function getDestinations(?string $parentGradeName = null): array
    {
        // Default destinations (ALU and others)
        $defaultDestinations = [
            'mangkok' => 'Mangkok',
            'idm' => 'IDM',
            'aa' => 'AA',
            'af' => 'Lempeng',
        ];

        // AA2 AF JUAL only has IDM as destination
        if ($parentGradeName === 'AA2 AF JUAL') {
            return [
                'idm' => 'IDM',
            ];
        }

        return $defaultDestinations;
    }

    /**
     * Proses sortir masuk stok
     * - ALU: SALE_OUT (kurangi stock) + SortMaterial tracking
     * - AF/Indomie P: GRADING_IN (tambah stock) + SALE_OUT sisa
     */
    public function processSortirMasukStok(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $grade = GradeCompany::with(['parentGradeCompany'])->findOrFail($data['grade_company_id']);
            $inputWeight = (float) $data['weight_grams'];
            $destination = $data['destination'] ?? null;
            $sortDate = $data['sort_date'] ?? now();

            $gudangUtama = Location::where('name', 'Gudang Utama')->firstOrFail();

            $sortingResult = SortingResult::where('grade_company_id', $grade->id)
                ->orderBy('grading_date', 'desc')
                ->first();

            $supplierId = null;
            if ($sortingResult && $sortingResult->receiptItem && $sortingResult->receiptItem->purchaseReceipt) {
                $supplierId = $sortingResult->receiptItem->purchaseReceipt->supplier_id;
            }

            $globalStock = $this->getGlobalStock($grade->id);

            if ($inputWeight > $globalStock) {
                throw new \Exception("Berat input ({$inputWeight}gr) tidak boleh melebihi stock global (" . number_format($globalStock, 0) . "gr)");
            }

            $sisaWeight = $globalStock - $inputWeight;

            // Create SortingResult for tracking
            $sortingData = [
                'grading_date' => $sortDate,
                'grade_company_id' => $grade->id,
                'weight_grams' => $inputWeight,
                'quantity' => 1,
                'notes' => 'Sortir Global - ' . ($destination ? ucfirst($destination) : 'Masuk Stok'),
                'created_by' => Auth::id(),
            ];

            if ($sortingResult && $sortingResult->receipt_item_id) {
                $sortingData['receipt_item_id'] = $sortingResult->receipt_item_id;
            }

            $sortingResultCreate = SortingResult::create($sortingData);

            $transactionOut = null;
            $transactionIn = null;

            if ($this->isAlu($grade) || $this->isAfJual($grade)) {
                // ALU & AA2 AF JUAL: SALE_OUT saja (kurangi stock dari source)
                $transactionOut = InventoryTransaction::create([
                    'transaction_date' => $sortDate,
                    'grade_company_id' => $grade->id,
                    'location_id' => $gudangUtama->id,
                    'supplier_id' => $supplierId,
                    'quantity_change_grams' => -abs($inputWeight),
                    'transaction_type' => 'SALE_OUT',
                    'reference_id' => $sortingResultCreate->id,
                    'sorting_result_id' => $sortingResultCreate->id,
                    'notes' => 'Sortir Global - ' . ($this->isAlu($grade) ? 'ALU' : 'AA2 AF JUAL') . ' keluar ke ' . ($destination ? ucfirst($destination) : 'stok'),
                    'created_by' => Auth::id(),
                ]);
            } else {
                // AF/Indomie P: GRADING_IN (tambah stock) + SALE_OUT sisa
                $transactionIn = InventoryTransaction::create([
                    'transaction_date' => $sortDate,
                    'grade_company_id' => $grade->id,
                    'location_id' => $gudangUtama->id,
                    'supplier_id' => $supplierId,
                    'quantity_change_grams' => abs($inputWeight),
                    'transaction_type' => 'GRADING_IN',
                    'reference_id' => $sortingResultCreate->id,
                    'sorting_result_id' => $sortingResultCreate->id,
                    'created_by' => Auth::id(),
                ]);

                if ($sisaWeight > 0) {
                    $transactionOut = InventoryTransaction::create([
                        'transaction_date' => $sortDate,
                        'grade_company_id' => $grade->id,
                        'location_id' => $gudangUtama->id,
                        'supplier_id' => $supplierId,
                        'quantity_change_grams' => -abs($sisaWeight),
                        'transaction_type' => 'SALE_OUT',
                        'reference_id' => $sortingResultCreate->id,
                        'sorting_result_id' => $sortingResultCreate->id,
                        'notes' => 'Sortir Global - Sisa dari proses masuk stok (AF/Indomie P)',
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            // Update parent stock
            $this->updateParentGradeStock($grade->parent_grade_company_id);

            // Create SortMaterial record for tracking stock "Sortir"
            $destinationMap = [
                'mangkok' => 2,
                'idm' => 3,
                'aa' => 5,
                'af' => 1, // LEMPENG
            ];

            $targetParentId = isset($destinationMap[$destination])
                ? $destinationMap[$destination]
                : $grade->parent_grade_company_id;

            $sortMaterial = SortMaterial::create([
                'sort_date' => $sortDate,
                'parent_grade_company_id' => $targetParentId,
                'grade_company_id' => $grade->id,
                'weight' => $inputWeight,
                'description' => 'Sortir Global - Masuk Stok' . ($destination ? ' (' . ucfirst($destination) . ')' : ''),
                'destination' => $destination,
                'sorting_result_id' => $sortingResultCreate->id,
            ]);

            return [
                'grading_in' => $transactionIn,
                'sale_out' => $transactionOut,
                'sorting_result' => $sortingResultCreate,
                'sort_material' => $sortMaterial,
            ];
        });
    }

    /**
     * Proses penjualan langsung (SALE_OUT)
     */
    public function processPenjualanLangsung(array $data): InventoryTransaction
    {
        return DB::transaction(function () use ($data) {
            $grade = GradeCompany::with(['parentGradeCompany'])->findOrFail($data['grade_company_id']);
            $inputWeight = (float) $data['weight_grams'];
            $sortDate = $data['sort_date'] ?? now();

            $gudangUtama = Location::where('name', 'Gudang Utama')->firstOrFail();

            $sortingResult = SortingResult::where('grade_company_id', $grade->id)
                ->orderBy('grading_date', 'desc')
                ->first();

            $supplierId = null;
            if ($sortingResult && $sortingResult->receiptItem && $sortingResult->receiptItem->purchaseReceipt) {
                $supplierId = $sortingResult->receiptItem->purchaseReceipt->supplier_id;
            }

            $globalStock = $this->getGlobalStock($grade->id);

            if ($inputWeight > $globalStock) {
                throw new \Exception("Berat input ({$inputWeight}gr) tidak boleh melebihi stock global (" . number_format($globalStock, 0) . "gr)");
            }

            $sortingData2 = [
                'grading_date' => $sortDate,
                'grade_company_id' => $grade->id,
                'weight_grams' => $inputWeight,
                'quantity' => 1,
                'notes' => 'Sortir Global - Penjualan Langsung',
                'created_by' => Auth::id(),
            ];

            if ($sortingResult && $sortingResult->receipt_item_id) {
                $sortingData2['receipt_item_id'] = $sortingResult->receipt_item_id;
            }

            $sortingResultCreate = SortingResult::create($sortingData2);

            $saleOut = InventoryTransaction::create([
                'transaction_date' => $sortDate,
                'grade_company_id' => $grade->id,
                'location_id' => $gudangUtama->id,
                'supplier_id' => $supplierId,
                'quantity_change_grams' => -abs($inputWeight),
                'transaction_type' => 'SALE_OUT',
                'reference_id' => $sortingResultCreate->id,
                'sorting_result_id' => $sortingResultCreate->id,
                'created_by' => Auth::id(),
            ]);

            $this->updateParentGradeStock($grade->parent_grade_company_id);

            return $saleOut;
        });
    }

    /**
     * Update stock di parent_grade_company
     */
    private function updateParentGradeStock(int $parentGradeCompanyId): void
    {
        $childGradeIds = GradeCompany::where('parent_grade_company_id', $parentGradeCompanyId)
            ->pluck('id')
            ->toArray();

        $totalStock = InventoryTransaction::whereIn('grade_company_id', $childGradeIds)
            ->sum('quantity_change_grams');

        ParentGradeCompany::find($parentGradeCompanyId)?->update(['stock' => $totalStock]);
    }
}
