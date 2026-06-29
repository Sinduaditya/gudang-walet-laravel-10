<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IdmDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'idm_details';

    protected $fillable = [
        'idm_management_id',
        'grade_idm_name',
        'grade_company_id',
        'weight',
    ];

    public function idmManagement()
    {
        return $this->belongsTo(IdmManagement::class);
    }

    public function gradeCompany()
    {
        return $this->belongsTo(GradeCompany::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
