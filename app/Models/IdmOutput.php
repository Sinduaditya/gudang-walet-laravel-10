<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IdmOutput extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'idm_outputs';

    protected $fillable = [
        'idm_management_id',
        'grade_company_id',
        'weight_grams',
        'notes',
        'created_by',
        'deleted_by',
    ];

    public function idmManagement()
    {
        return $this->belongsTo(IdmManagement::class);
    }

    public function gradeCompany()
    {
        return $this->belongsTo(GradeCompany::class);
    }

    public function sortingResultProxy()
    {
        return $this->hasOne(SortingResult::class, 'idm_output_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
