<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_date',
        'grade_company_id',
        'location_id',
        'supplier_id',
        'quantity_change_grams',
        'transaction_type',
        'reference_id',
        'sorting_result_id',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'quantity_change_grams' => 'float',
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
        return $query->whereIn('transaction_type', ['SALE_OUT', 'TRANSFER_OUT', 'IDM_TRANSFER_OUT']);
    }

    /**
     * Scope untuk transaksi masuk saja
     */
    public function scopeIncoming($query)
    {
        return $query->whereIn('transaction_type', ['PURCHASE_IN', 'TRANSFER_IN']);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
