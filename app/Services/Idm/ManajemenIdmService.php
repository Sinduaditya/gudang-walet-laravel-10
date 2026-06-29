<?php

namespace App\Services\Idm;

use App\Models\GradeCompany;
use App\Models\IdmDetail;
use App\Models\IdmManagement;
use App\Models\InventoryTransaction;
use App\Models\Location;
use App\Models\SortingResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManajemenIdmService
{
    // Transaksi outflow yang menandakan output ManajemenIDM sudah keluar — pakai untuk block edit/delete
    private const OUTFLOW_TYPES = [
        'SALE_OUT',
        'TRANSFER_OUT',
        'EXTERNAL_TRANSFER_OUT',
        'RECEIVE_EXTERNAL_OUT',
        'IDM_TRANSFER_OUT',
    ];

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = IdmManagement::with(['supplier', 'gradeCompany', 'sourceItems'])
            ->orderByDesc('id');

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['grade_company_id'])) {
            $query->where('grade_company_id', $filters['grade_company_id']);
        }

        if (!empty($filters['category_grade'])) {
            $query->whereHas('sourceItems', function ($q) use ($filters) {
                $q->where('category_grade', $filters['category_grade']);
            });
        }

        return $query->paginate(10)->withQueryString();
    }

    public function getAvailableItems(string $category, array $filters): LengthAwarePaginator
    {
        $query = SortingResult::where('category_grade', $category)
            ->whereNull('idm_management_id')
            ->with([
                'receiptItem' => fn ($q) => $q->withTrashed(),
                'receiptItem.purchaseReceipt' => fn ($q) => $q->withTrashed(),
                'receiptItem.purchaseReceipt.supplier' => fn ($q) => $q->withTrashed(),
            ])
            ->orderByDesc('id');

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->whereHas('receiptItem.purchaseReceipt', function ($q) use ($filters) {
                $q->withTrashed()->where('supplier_id', $filters['supplier_id']);
            });
        }

        if (!empty($filters['search'])) {
            $query->whereHas('gradeCompany', function ($q) use ($filters) {
                $q->where('grade_name', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->paginate(12)->withQueryString();
    }

    public function getItemsByIds(array $ids): Collection
    {
        return SortingResult::whereIn('id', $ids)
            ->with([
                'receiptItem' => fn ($q) => $q->withTrashed(),
                'receiptItem.purchaseReceipt' => fn ($q) => $q->withTrashed(),
                'receiptItem.purchaseReceipt.supplier' => fn ($q) => $q->withTrashed(),
            ])
            ->get();
    }

    public function create(array $itemIds, array $data): IdmManagement
    {
        return DB::transaction(function () use ($itemIds, $data) {
            $items         = SortingResult::whereIn('id', $itemIds)->get();
            $initialWeight = $items->sum('weight_grams');

            $firstItem            = $items->first();
            $supplierId           = optional($firstItem->receiptItem?->purchaseReceipt)->supplier_id;
            $sourceGradeCompanyId = $data['grade_company_id'];

            $outputs = $this->buildOutputs($data['details'] ?? [], $sourceGradeCompanyId);

            $this->validateOutputs($outputs, $initialWeight);

            $totalOutput = array_sum(array_column($outputs, 'weight'));
            $shrinkage   = $initialWeight - $totalOutput;

            $mgmt = IdmManagement::create([
                'supplier_id'      => $supplierId,
                'grade_company_id' => $sourceGradeCompanyId,
                'initial_weight'   => $initialWeight,
                'shrinkage'        => $shrinkage,
                'grading_date'     => now(),
            ]);

            $this->createDetailsAndTransactions($mgmt, $outputs, $sourceGradeCompanyId, $initialWeight, $supplierId);

            SortingResult::whereIn('id', $itemIds)->update(['idm_management_id' => $mgmt->id]);

            return $mgmt;
        });
    }

    public function update(int $id, array $data): IdmManagement
    {
        return DB::transaction(function () use ($id, $data) {
            $mgmt = IdmManagement::with('details')->findOrFail($id);

            $this->assertNoOutflow($mgmt, 'Data tidak dapat diubah — output sudah keluar via transfer/sale.');

            $outputs = $this->buildOutputs($data['details'] ?? [], $mgmt->grade_company_id);

            $this->validateOutputs($outputs, $mgmt->initial_weight);

            $totalOutput = array_sum(array_column($outputs, 'weight'));
            $shrinkage   = $mgmt->initial_weight - $totalOutput;

            $this->revertRegradingTransactions($mgmt);

            $mgmt->details()->delete();

            $mgmt->update(['shrinkage' => $shrinkage]);

            $this->createDetailsAndTransactions(
                $mgmt,
                $outputs,
                $mgmt->grade_company_id,
                $mgmt->initial_weight,
                $mgmt->supplier_id
            );

            return $mgmt->fresh();
        });
    }

    public function find(int $id): IdmManagement
    {
        $mgmt = IdmManagement::with([
            'supplier',
            'gradeCompany',
            'details.gradeCompany',
            'sourceItems',
        ])->findOrFail($id);

        $mgmt->is_transferred = $this->hasOutflow($mgmt);

        return $mgmt;
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $mgmt = IdmManagement::with('details')->findOrFail($id);

            $this->assertNoOutflow($mgmt, 'Tidak bisa hapus — output sudah keluar via transfer/sale. Hapus transfer/sale terlebih dahulu.');

            $this->revertRegradingTransactions($mgmt);

            SortingResult::where('idm_management_id', $id)->update(['idm_management_id' => null]);

            $mgmt->details()->delete();
            $mgmt->delete();
        });
    }

    // ===== Internal helpers =====

    private function buildOutputs(array $details, int $sourceGradeCompanyId): array
    {
        $idmGc     = GradeCompany::where('name', 'IDM')->first();
        $perutanGc = GradeCompany::where('name', 'PERUTAN')->first();
        $kakianGc  = GradeCompany::where('name', 'KAKIAN')->first();
        $aluGc     = GradeCompany::where('name', 'ALU/AFKIR')->first();

        return [
            'IDM' => [
                'weight'           => (float) ($details['IDM']['weight'] ?? 0),
                'grade_company_id' => $idmGc?->id,
            ],
            'KAKIAN'  => ['weight' => (float) ($details['KAKIAN']['weight']  ?? 0), 'grade_company_id' => $kakianGc?->id],
            'PERUTAN' => ['weight' => (float) ($details['PERUTAN']['weight'] ?? 0), 'grade_company_id' => $perutanGc?->id],
            'ALU'     => ['weight' => (float) ($details['ALU']['weight']     ?? 0), 'grade_company_id' => $aluGc?->id],
        ];
    }

    private function validateOutputs(array $outputs, float $initialWeight): void
    {
        if ($outputs['IDM']['weight'] <= 0 || !$outputs['IDM']['grade_company_id']) {
            throw new \Exception('Berat IDM dan grade IDM wajib diisi.');
        }

        foreach (['KAKIAN', 'PERUTAN', 'ALU'] as $k) {
            if ($outputs[$k]['weight'] > 0 && !$outputs[$k]['grade_company_id']) {
                throw new \Exception("Grade untuk {$k} tidak ditemukan di database. Jalankan db:seed.");
            }
        }

        $totalOutput = array_sum(array_column($outputs, 'weight'));
        if ($totalOutput - $initialWeight > 0.001) {
            throw new \Exception('Total berat output melebihi berat awal. Periksa input.');
        }
    }

    private function createDetailsAndTransactions(
        IdmManagement $mgmt,
        array $outputs,
        int $sourceGradeCompanyId,
        float $initialWeight,
        ?int $supplierId
    ): void {
        $gudangUtama = Location::where('name', 'Gudang Utama')->firstOrFail();
        $userId      = Auth::id();

        // 1. Deduct input grade (IDM A / IDM B)
        InventoryTransaction::create([
            'transaction_date'      => now(),
            'grade_company_id'      => $sourceGradeCompanyId,
            'location_id'           => $gudangUtama->id,
            'supplier_id'           => $supplierId,
            'quantity_change_grams' => -$initialWeight,
            'transaction_type'      => 'IDM_REGRADING_OUT',
            'reference_id'          => $mgmt->id,
            'created_by'            => $userId,
        ]);

        // 2. Per-output: buat idm_detail + (kalau weight > 0) inventory_transaction IDM_REGRADING_IN
        foreach ($outputs as $name => $out) {
            IdmDetail::create([
                'idm_management_id' => $mgmt->id,
                'grade_idm_name'    => $name,
                'grade_company_id'  => $out['grade_company_id'],
                'weight'            => $out['weight'],
            ]);

            if ($out['weight'] > 0 && $out['grade_company_id']) {
                InventoryTransaction::create([
                    'transaction_date'      => now(),
                    'grade_company_id'      => $out['grade_company_id'],
                    'location_id'           => $gudangUtama->id,
                    'supplier_id'           => $supplierId,
                    'quantity_change_grams' => $out['weight'],
                    'transaction_type'      => 'IDM_REGRADING_IN',
                    'reference_id'          => $mgmt->id,
                    'created_by'            => $userId,
                ]);
            }
        }
    }

    private function revertRegradingTransactions(IdmManagement $mgmt): void
    {
        $userId = Auth::id();

        $txs = InventoryTransaction::where('reference_id', $mgmt->id)
            ->whereIn('transaction_type', ['IDM_REGRADING_OUT', 'IDM_REGRADING_IN'])
            ->get();

        foreach ($txs as $tx) {
            InventoryTransaction::create([
                'transaction_date'      => now(),
                'grade_company_id'      => $tx->grade_company_id,
                'location_id'           => $tx->location_id,
                'supplier_id'           => $tx->supplier_id,
                'quantity_change_grams' => -$tx->quantity_change_grams,
                'transaction_type'      => $tx->transaction_type === 'IDM_REGRADING_OUT'
                    ? 'IDM_REGRADING_REVERT_OUT'
                    : 'IDM_REGRADING_REVERT_IN',
                'reference_id'          => $mgmt->id,
                'created_by'            => $userId,
            ]);

            $tx->deleted_by = $userId;
            $tx->save();
            $tx->delete();
        }
    }

    private function hasOutflow(IdmManagement $mgmt): bool
    {
        $outputGradeIds = $mgmt->details->pluck('grade_company_id')->filter()->unique();

        if ($outputGradeIds->isEmpty()) {
            return false;
        }

        return InventoryTransaction::where('reference_id', '!=', $mgmt->id)
            ->whereIn('grade_company_id', $outputGradeIds)
            ->whereIn('transaction_type', self::OUTFLOW_TYPES)
            ->exists();
    }

    private function assertNoOutflow(IdmManagement $mgmt, string $message): void
    {
        if ($this->hasOutflow($mgmt)) {
            throw new \Exception($message);
        }
    }
}
