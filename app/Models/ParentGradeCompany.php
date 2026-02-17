<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParentGradeCompany extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'parent_grade_companies';

    protected $fillable = [
        'name',
        'image_url',
        'description',
        'stock',
    ];

    public function sortMaterials()
    {
        return $this->hasMany(SortMaterial::class);
    }

    public function gradeCompanies()
    {
        return $this->hasMany(GradeCompany::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
