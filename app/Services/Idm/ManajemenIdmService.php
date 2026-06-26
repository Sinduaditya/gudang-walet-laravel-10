<?php

namespace App\Services\Idm;

use App\Models\IdmDetail;
use App\Models\IdmManagement;
use App\Models\SortingResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ManajemenIdmService
{
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
            $items = SortingResult::whereIn('id', $itemIds)->get();
            $initialWeight = $items->sum('weight_grams');

            $perutanWeight = (float) ($data['details']['perutan']['weight'] ?? 0);
            $kakianWeight  = (float) ($data['details']['kakian']['weight'] ?? 0);
            $idmWeight     = (float) ($data['details']['idm']['weight'] ?? 0);
            $shrinkage     = $initialWeight - ($perutanWeight + $kakianWeight + $idmWeight);

            if ($shrinkage < 0) {
                throw new \Exception('Total berat (perutan + kakian + IDM) melebihi berat awal. Periksa kembali input berat.');
            }

            $firstItem    = $items->first();
            $supplierId   = optional($firstItem->receiptItem?->purchaseReceipt)->supplier_id;

            $idmManagement = IdmManagement::create([
                'supplier_id'      => $supplierId,
                'grade_company_id' => $data['grade_company_id'],
                'initial_weight'   => $initialWeight,
                'shrinkage'        => $shrinkage,
                'grading_date'     => now(),
            ]);

            foreach (['perutan' => $perutanWeight, 'kakian' => $kakianWeight, 'idm' => $idmWeight] as $name => $weight) {
                IdmDetail::create([
                    'idm_management_id' => $idmManagement->id,
                    'grade_idm_name'    => $name,
                    'weight'            => $weight,
                ]);
            }

            SortingResult::whereIn('id', $itemIds)->update(['idm_management_id' => $idmManagement->id]);

            return $idmManagement;
        });
    }

    public function update(int $id, array $data): IdmManagement
    {
        return DB::transaction(function () use ($id, $data) {
            $idmManagement = IdmManagement::with('details.transferDetails')->findOrFail($id);

            $hasTransferred = $idmManagement->details->some(fn ($d) => $d->transferDetails->isNotEmpty());
            if ($hasTransferred) {
                throw new \Exception('Data tidak dapat diubah karena sudah dikeluarkan melalui Transfer IDM.');
            }

            $perutanWeight = (float) ($data['details']['perutan']['weight'] ?? 0);
            $kakianWeight  = (float) ($data['details']['kakian']['weight'] ?? 0);
            $idmWeight     = (float) ($data['details']['idm']['weight'] ?? 0);
            $shrinkage     = $idmManagement->initial_weight - ($perutanWeight + $kakianWeight + $idmWeight);

            if ($shrinkage < 0) {
                throw new \Exception('Total berat (perutan + kakian + IDM) melebihi berat awal. Periksa kembali input berat.');
            }

            $idmManagement->update(['shrinkage' => $shrinkage]);

            $idmManagement->details()->delete();

            foreach (['perutan' => $perutanWeight, 'kakian' => $kakianWeight, 'idm' => $idmWeight] as $name => $weight) {
                IdmDetail::create([
                    'idm_management_id' => $idmManagement->id,
                    'grade_idm_name'    => $name,
                    'weight'            => $weight,
                ]);
            }

            return $idmManagement->fresh();
        });
    }

    public function find(int $id): IdmManagement
    {
        $idmManagement = IdmManagement::with(['supplier', 'gradeCompany', 'details.transferDetails', 'sourceItems'])
            ->findOrFail($id);

        $idmManagement->is_transferred = $idmManagement->details->some(fn ($d) => $d->transferDetails->isNotEmpty());

        return $idmManagement;
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $idmManagement = IdmManagement::with('details.transferDetails')->findOrFail($id);

            $hasTransferred = $idmManagement->details->some(fn ($d) => $d->transferDetails->isNotEmpty());
            if ($hasTransferred) {
                throw new \Exception('Data tidak dapat dihapus karena sudah dikeluarkan melalui Transfer IDM. Hapus Transfer IDM terlebih dahulu.');
            }

            SortingResult::where('idm_management_id', $id)->update(['idm_management_id' => null]);

            $idmManagement->details()->delete();
            $idmManagement->delete();
        });
    }
}
