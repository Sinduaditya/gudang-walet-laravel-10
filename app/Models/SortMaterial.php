<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SortMaterial extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_MASUK  = 'masuk';
    const TYPE_KELUAR = 'keluar';

    protected $fillable = [
        'weight',
        'type',
        'sort_date',
        'sale_date',
        'parent_grade_company_id',
        'grade_company_id',
        'description',
        'notes',
        'destination',
        'sorting_result_id',
    ];

    protected $casts = [
        'sort_date' => 'date',
        'sale_date' => 'date',
        'weight'    => 'decimal:2',
    ];

    public function parentGradeCompany()
    {
        return $this->belongsTo(ParentGradeCompany::class);
    }

    public function gradeCompany()
    {
        return $this->belongsTo(GradeCompany::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function sortingResult()
    {
        return $this->belongsTo(SortingResult::class);
    }
}
