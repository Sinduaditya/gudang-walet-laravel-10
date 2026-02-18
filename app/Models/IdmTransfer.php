<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class IdmTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'idm_transfers';

    protected $fillable = [
        'transfer_date',
        'transfer_code',
        'sum_goods',
        'price_transfer',
        'average_idm_price',
        'total_non_idm_price',
        'total_idm_price',
        'notes',
    ];

    public function details()
    {
        return $this->hasMany(IdmTransferDetail::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
