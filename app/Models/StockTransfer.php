<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockTransfer extends Model
{
    use HasFactory;

    protected $table = 'stock_transfers';

    protected $fillable = [
        'transfer_date',
        'grade_company_id',
        'susut_grams',
        'from_location_id',
        'to_location_id',
        'weight_grams',
        'notes',
        'sorting_result_id',
        'created_by'
    ];

    public function gradeCompany()
    {
        return $this->belongsTo(GradeCompany::class);
    }

    public function fromLocation()
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'reference_id');
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'reference_id')
            ->whereIn('transaction_type', ['TRANSFER_OUT', 'TRANSFER_IN']);
    }

    public function sortingResult()
    {
        return $this->belongsTo(SortingResult::class, 'sorting_result_id');
    }

    /**
     * Get transaction OUT (negatif)
     */
    public function outTransaction()
    {
        return $this->hasOne(InventoryTransaction::class, 'reference_id')
            ->where('transaction_type', 'TRANSFER_OUT');
    }

    /**
     * Get transaction IN (positif)
     */
    public function inTransaction()
    {
        return $this->hasOne(InventoryTransaction::class, 'reference_id')
            ->where('transaction_type', 'TRANSFER_IN');
    }
}
