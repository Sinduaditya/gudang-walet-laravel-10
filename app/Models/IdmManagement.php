<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdmManagement extends Model
{
    use HasFactory;

    protected $table = 'idm_managements';

    protected $fillable = [
        'supplier_id',
        'grade_company_id',
        'initial_weight',
        'shrinkage',
        'initial_price',
        'estimated_selling_price',
        'grading_date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function gradeCompany()
    {
        return $this->belongsTo(GradeCompany::class, 'grade_company_id');
    }

    public function details()
    {
        return $this->hasMany(IdmDetail::class);
    }

    public function sourceItems()
    {
        return $this->hasMany(SortingResult::class, 'idm_management_id');
    }
}
