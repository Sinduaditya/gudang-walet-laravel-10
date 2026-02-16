<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SortMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'weight',
        'sort_date',
        'parent_grade_company_id',
        'grade_company_id',
        'description',
    ];

    protected $casts = [
        'sort_date' => 'date',
        'weight' => 'decimal:2',
    ];

    public function parentGradeCompany()
    {
        return $this->belongsTo(ParentGradeCompany::class);
    }

    public function gradeCompany()
    {
        return $this->belongsTo(GradeCompany::class);
    }
}
