<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleItem extends Model
{
    use HasFactory;

    protected $table = 'sale_items';

    protected $fillable = [
        'sale_id',
        'grade_company_id',
        'from_location_id',
        'weight_grams',
        'price_per_gram',
        'total_price'
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function gradeCompany()
    {
        return $this->belongsTo(GradeCompany::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }
}
