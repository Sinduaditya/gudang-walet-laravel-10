<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'locations';

    protected $fillable = [
        'name',
        'description',
        'is_jasa_cuci',
    ];

    protected $casts = [
        'is_jasa_cuci' => 'boolean',
    ];

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function stockTransfersFrom()
    {
        return $this->hasMany(StockTransfer::class, 'from_location_id');
    }

    public function stockTransfersTo()
    {
        return $this->hasMany(StockTransfer::class, 'to_location_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Cek apakah lokasi ini sudah pernah dipakai di transaksi
     * (stock_transfer, inventory_transaction, atau sale_item).
     *
     * @return bool
     */
    public function hasTransactions(): bool
    {
        return $this->stockTransfersFrom()->exists()
            || $this->stockTransfersTo()->exists()
            || $this->inventoryTransactions()->exists();
    }
}
