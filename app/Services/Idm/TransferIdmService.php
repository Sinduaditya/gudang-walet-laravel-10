<?php

namespace App\Services\Idm;

use App\Models\IdmTransfer;
use App\Models\IdmTransferDetail;
use App\Models\IdmDetail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\InventoryTransaction;
use App\Models\Location;
use Illuminate\Support\Facades\Auth;

class TransferIdmService
{
    // ... (existing methods getTransfers, getAvailableIdmDetails, generateTransferCode) ...

    public function getTransfers($filters = [])
    {
        $query = IdmTransfer::withCount('details');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('transfer_date', [$filters['start_date'], $filters['end_date']]);
        }

        if (!empty($filters['search'])) {
            $query->where('transfer_code', 'like', '%' . $filters['search'] . '%');
        }

        return $query->latest()->paginate(10);
    }

    public function getAvailableIdmDetails($filters = [])
    {
        $query = IdmDetail::with(['idmManagement.supplier', 'idmManagement.gradeCompany'])
            ->whereDoesntHave('transferDetails'); // Ensure not already transferred

        // Filter: Grading Date (from IdmManagement)
        if (!empty($filters['grading_date'])) {
            $query->whereHas('idmManagement', function ($q) use ($filters) {
                $q->whereDate('grading_date', $filters['grading_date']);
            });
        }

        // Filter: Supplier
        if (!empty($filters['supplier_id'])) {
            $query->whereHas('idmManagement', function ($q) use ($filters) {
                $q->where('supplier_id', $filters['supplier_id']);
            });
        }

        // Filter: Grade Company
        if (!empty($filters['grade_company_id'])) {
            $query->whereHas('idmManagement', function ($q) use ($filters) {
                $q->where('grade_company_id', $filters['grade_company_id']);
            });
        }

        // Filter: Grade IDM
        if (!empty($filters['grade_idm_name'])) {
            $query->where('grade_idm_name', 'like', '%' . $filters['grade_idm_name'] . '%');
        }

        // Filter: IDM Type (Category from SortingResult)
        if (!empty($filters['idm_type'])) {
            $query->whereHas('idmManagement.sourceItems', function ($q) use ($filters) {
                $q->where('category_grade', $filters['idm_type']);
            });
        }

        return $query->get();
    }

    public function generateTransferCode($date)
    {
        // Format: month-dayyear e.g. jan-1025
        $carbonDate = Carbon::parse($date);
        $month = strtolower($carbonDate->format('M')); // jan
        $dayYear = $carbonDate->format('dy'); // 1025 (day 10, year 25)
        
        $baseCode = "{$month}-{$dayYear}";
        $code = $baseCode;
        $counter = 1;

        // Verify uniqueness
        while (IdmTransfer::where('transfer_code', $code)->exists()) {
            $code = "{$baseCode}-{$counter}";
            $counter++;
        }

        return $code;
    }

    public function storeTransfer($data)
    {
        return DB::transaction(function () use ($data) {
            $transfer = IdmTransfer::create([
                'transfer_date' => $data['transfer_date'],
                'transfer_code' => $this->generateTransferCode($data['transfer_date']),
                'sum_goods' => count($data['items']),
                'price_transfer' => $data['total_price'],
                'average_idm_price' => $data['average_idm_price'],
                'total_non_idm_price' => $data['total_non_idm_price'],
                'total_idm_price' => $data['total_idm_price'],
                'notes' => $data['notes'] ?? null,
            ]);

            // Determine Source Location
            $sourceLocationId = $data['source_location_id'] ?? null;
            if (!$sourceLocationId) {
                // Fallback to defaults if not provided (though UI provides it)
                $gudangUtama = Location::where('name', 'Gudang Utama')->first();
                $sourceLocationId = $gudangUtama ? $gudangUtama->id : null;
            }

            $userId = Auth::id();

            foreach ($data['items'] as $item) {
                IdmTransferDetail::create([
                    'idm_transfer_id' => $transfer->id,
                    'idm_detail_id' => $item['id'],
                    'item_name' => $item['grade_idm_name'] ?? 'Unknown',
                    'grade_idm_name' => $item['grade_idm_name'],
                    'weight' => $item['weight'],
                    'price' => $item['price'],
                    'total_price' => $item['total_price'],
                ]);

                // Record Inventory Transaction (Deduct from Source Location)
                if ($sourceLocationId) {
                    // Need to fetch details to get relations for Inventory Transaction
                    $detailModel = IdmDetail::with(['idmManagement.sourceItems'])->find($item['id']);
                    
                    if ($detailModel) {
                        $sortingResult = $detailModel->idmManagement->sourceItems->first();
                        $management = $detailModel->idmManagement;
                        
                        // Calculate Proportional Weight (Item Weight + Share of Shrinkage)
                        // Formula: Item Weight * (Initial Weight / Sum of Details Weight)
                        $totalOutputWeight = $management->details->sum('weight');
                        $deductionWeight = $item['weight'];

                        if ($totalOutputWeight > 0 && $management->initial_weight > 0) {
                            $factor = $management->initial_weight / $totalOutputWeight;
                            $deductionWeight = round($item['weight'] * $factor);
                        }

                        // Ensure precise rounding not needed if DB handles float, but logically consistent
                        InventoryTransaction::create([
                            'transaction_date' => $transfer->transfer_date,
                            'grade_company_id' => $detailModel->idmManagement->grade_company_id,
                            'location_id' => $sourceLocationId,
                            'supplier_id' => $detailModel->idmManagement->supplier_id,
                            // Deduct weight in grams (proportional)
                            'quantity_change_grams' => -($deductionWeight), 
                            'transaction_type' => 'IDM_TRANSFER_OUT',
                            'reference_id' => $transfer->id,
                            'sorting_result_id' => $sortingResult->id ?? null,
                            'created_by' => $userId,
                        ]);
                    }
                }
            }

            return $transfer;
        });
    }

    public function updateTransfer($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $transfer = IdmTransfer::findOrFail($id);
            
            // Check if date changed to regenerate code
            if ($transfer->transfer_date != $data['transfer_date']) {
                $transfer->transfer_code = $this->generateTransferCode($data['transfer_date']);
            }

            $transfer->transfer_date = $data['transfer_date'];
            $transfer->sum_goods = count($data['items']);
            $transfer->price_transfer = $data['total_price'];
            $transfer->average_idm_price = $data['average_idm_price'];
            $transfer->total_non_idm_price = $data['total_non_idm_price'];
            $transfer->total_idm_price = $data['total_idm_price'];
            $transfer->notes = $data['notes'] ?? null;
            $transfer->save();

            // Sync items: Delete all existing details and recreate
            $transfer->details()->delete();

            // Revert Inventory Transactions for this transfer before recreating
            InventoryTransaction::where('transaction_type', 'IDM_TRANSFER_OUT')
                ->where('reference_id', $transfer->id)
                ->delete();

            $gudangUtama = Location::where('name', 'Gudang Utama')->first();
            $userId = Auth::id();

            foreach ($data['items'] as $item) {
                IdmTransferDetail::create([
                    'idm_transfer_id' => $transfer->id,
                    'idm_detail_id' => $item['id'], // Ensure this maps to idm_detail_id
                    'item_name' => $item['grade_idm_name'] ?? 'Unknown',
                    'grade_idm_name' => $item['grade_idm_name'],
                    'weight' => $item['weight'],
                    'price' => $item['price'],
                    'total_price' => $item['total_price'],
                ]);

                // Record Inventory Transaction
                if ($gudangUtama) {
                    $detailModel = IdmDetail::with(['idmManagement.sourceItems'])->find($item['id']);
                    
                    if ($detailModel) {
                        $sortingResult = $detailModel->idmManagement->sourceItems->first();
                        $management = $detailModel->idmManagement;

                        // Calculate Proportional Weight
                        $totalOutputWeight = $management->details->sum('weight');
                        $deductionWeight = $item['weight'];

                        if ($totalOutputWeight > 0 && $management->initial_weight > 0) {
                            $factor = $management->initial_weight / $totalOutputWeight;
                            $deductionWeight = round($item['weight'] * $factor);
                        }
                        
                        InventoryTransaction::create([
                            'transaction_date' => $transfer->transfer_date,
                            'grade_company_id' => $detailModel->idmManagement->grade_company_id,
                            'location_id' => $gudangUtama->id,
                            'supplier_id' => $detailModel->idmManagement->supplier_id,
                            'quantity_change_grams' => -($deductionWeight), 
                            'transaction_type' => 'IDM_TRANSFER_OUT',
                            'reference_id' => $transfer->id,
                            'sorting_result_id' => $sortingResult->id ?? null,
                            'created_by' => $userId,
                        ]);
                    }
                }
            }

            return $transfer;
        });
    }

    public function deleteTransfer($id)
    {
        return DB::transaction(function () use ($id) {
            $transfer = IdmTransfer::findOrFail($id);
            
            // Revert Inventory Transactions
            InventoryTransaction::where('transaction_type', 'IDM_TRANSFER_OUT')
                ->where('reference_id', $transfer->id)
                ->delete();
                
            $transfer->delete();
            return true;
        });
    }

    public function getTransferById($id)
    {
        return IdmTransfer::with(['details.idmDetail.idmManagement.supplier'])->findOrFail($id);
    }
}
