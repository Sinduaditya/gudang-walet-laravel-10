<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ParentGradeCompany extends Model
{
    use HasFactory;

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
}
