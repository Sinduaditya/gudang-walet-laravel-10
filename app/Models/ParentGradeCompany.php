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
        'description'
    ];
}
