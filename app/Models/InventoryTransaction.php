<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryTransaction extends Model
{
    use HasFactory, SoftDeletes;

    const SORT_IN          = 'SORT_IN';
    const SORT_OUT         = 'SORT_OUT';
    const SORT_GRADING_IN  = 'SORT_GRADING_IN';
    const SORT_GRADING_OUT = 'SORT_GRADING_OUT';

    const CAT_SALE              = 'SALE';
    const CAT_TRANSFER          = 'TRANSFER';
    const CAT_EXTERNAL_TRANSFER = 'EXTERNAL_TRANSFER';
    const CAT_RECEIVE_EXTERNAL  = 'RECEIVE_EXTERNAL';
    const CAT_RECEIVE_INTERNAL  = 'RECEIVE_INTERNAL';
    const CAT_GRADING           = 'GRADING';
    const CAT_IDM               = 'IDM';
    const CAT_SORT              = 'SORT';
    const CAT_ADJUSTMENT        = 'ADJUSTMENT';

    protected $fillable = [
        'transaction_date',
        'grade_company_id',
        'parent_grade_company_id',
        'location_id',
        'supplier_id',
        'quantity_change_grams',
        'transaction_type',
        'category',
        'is_revert',
        'reference_id',
        'sorting_result_id',
        'created_by',
        'deleted_by',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'quantity_change_grams' => 'float',
        'is_revert' => 'boolean',
    ];

    /**
     * Relasi ke GradeCompany
     */
    public function gradeCompany()
    {
        return $this->belongsTo(GradeCompany::class, 'grade_company_id');
    }

    /**
     * Relasi ke Location
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * Relasi ke User (creator)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke SortingResult
     */
    public function sortingResult()
    {
        return $this->belongsTo(SortingResult::class, 'sorting_result_id');
    }

    /**
     * Relasi ke StockTransfer (jika transaction_type = TRANSFER_IN/OUT)
     */
    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class, 'reference_id');
    }

    /**
     * Scope untuk transaksi keluar saja
     */
    public function scopeOutgoing($query)
    {
        return $query->where('quantity_change_grams', '<', 0)->where('is_revert', false);
    }

    public function scopeIncoming($query)
    {
        return $query->where('quantity_change_grams', '>', 0)->where('is_revert', false);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeReverts($query)
    {
        return $query->where('is_revert', true);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function parentGradeCompany()
    {
        return $this->belongsTo(ParentGradeCompany::class, 'parent_grade_company_id');
    }
}
