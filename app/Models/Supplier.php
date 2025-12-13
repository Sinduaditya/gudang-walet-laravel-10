<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'address',
        'contact_person'
    ];

    public function purchaseReceipts()
    {
        return $this->hasMany(PurchaseReceipt::class);
    }
}
