<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdmTransferDetail extends Model
{
    use HasFactory;

    protected $table = 'idm_transfer_details';

    protected $fillable = [
        'idm_transfer_id',
        'idm_detail_id',
        'item_name',
        'grade_idm_name',
        'weight',
        'price',
        'total_price',
    ];

    public function transfer()
    {
        return $this->belongsTo(IdmTransfer::class, 'idm_transfer_id');
    }

    public function idmDetail()
    {
        return $this->belongsTo(IdmDetail::class, 'idm_detail_id');
    }
}
