<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GradeCompany extends Model
{
    use HasFactory;

    protected $table = 'grades_company';

    protected $fillable = [
        'name',
        'image_url',
        'description'
    ];

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
}
