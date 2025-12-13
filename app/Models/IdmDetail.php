<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdmDetail extends Model
{
    use HasFactory;

    protected $table = 'idm_details';

    protected $fillable = [
        'idm_management_id',
        'grade_idm_name',
        'weight',
        'price',
        'total_price',
    ];

    public function idmManagement()
    {
        return $this->belongsTo(IdmManagement::class);
    }

    public function transferDetails()
    {
        return $this->hasMany(IdmTransferDetail::class, 'idm_detail_id');
    }
}
