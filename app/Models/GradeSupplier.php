<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeSupplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'grades_supplier';

    protected $fillable = [
        'name',
        'image_url',
        'description'
    ];

    public function receiptItems()
    {
        return $this->hasMany(ReceiptItem::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
