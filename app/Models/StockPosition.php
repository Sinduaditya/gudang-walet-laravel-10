<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockPosition extends Model
{
    protected $table = 'stock_positions';

    protected $fillable = [
        'grade_company_id',
        'location_id',
        'quantity_grams',
    ];

    protected $casts = [
        'quantity_grams' => 'float',
    ];

    public function gradeCompany()
    {
        return $this->belongsTo(GradeCompany::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function scopePositive($query)
    {
        return $query->where('quantity_grams', '>', 0);
    }

    public function scopeByGrade($query, int $gradeId)
    {
        return $query->where('grade_company_id', $gradeId);
    }

    public function scopeByLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }
}
