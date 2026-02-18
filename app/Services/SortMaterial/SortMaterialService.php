<?php

namespace App\Services\SortMaterial;

use App\Exports\SortMaterialExport;
use App\Models\SortMaterial;
use App\Models\ParentGradeCompany;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SortMaterialService
{
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

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $sortMaterial = SortMaterial::create($data);

            // Update ParentGradeCompany stock
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

            // Revert old stock
            $oldParentGrade = ParentGradeCompany::findOrFail($oldParentId);
            $oldParentGrade->decrement('stock', $oldWeight);

            // Add new stock
            $newParentGrade = ParentGradeCompany::findOrFail($data['parent_grade_company_id']);
            $newParentGrade->increment('stock', $data['weight']);

            return $sortMaterial;
        });
    }

    public function delete(int $id)
    {
        return DB::transaction(function () use ($id) {
            $sortMaterial = $this->getById($id);

            // Decrease stock before deleting
            $sortMaterial->parentGradeCompany->decrement('stock', $sortMaterial->weight);

            $sortMaterial->deleted_by = auth()->id();
            $sortMaterial->save();
            $sortMaterial->delete();
            return true;
        });
    }
}
