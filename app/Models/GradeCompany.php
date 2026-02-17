<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeCompany extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'grades_company';

    protected $fillable = [
        'name',
        'image_url',
        'description',
        'parent_grade_company_id',
    ];

    public function parentGradeCompany()
    {
        return $this->belongsTo(ParentGradeCompany::class);
    }

    public function sortMaterials()
    {
        return $this->hasMany(SortMaterial::class);
    }

    public function sortingResults()
    {
        return $this->hasMany(SortingResult::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockTransfers()
    {
        return $this->hasMany(StockTransfer::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
