<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseReceipt extends Model
{
    use HasFactory;

    protected $table = 'purchase_receipts';

    protected $fillable = [
        'supplier_id',
        'receipt_date',
        'unloading_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'unloading_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function receiptItems()
    {
        return $this->hasMany(ReceiptItem::class, 'purchase_receipt_id','id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
