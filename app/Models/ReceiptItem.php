<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReceiptItem extends Model
{
    use HasFactory;

    protected $table = 'receipt_items';

    // Status constants untuk alur kerja
    const STATUS_MENTAH = 'mentah';
    const STATUS_SELESAI_DISORTIR = 'selesai_disortir';

    protected $fillable = ['purchase_receipt_id', 'grade_supplier_id', 'supplier_weight_grams', 'warehouse_weight_grams', 'difference_grams', 'percentage_difference', 'moisture_percentage', 'is_flagged_red', 'status', 'created_by', 'updated_by'];

    protected $casts = [
        'supplier_weight_grams' => 'integer',
        'warehouse_weight_grams' => 'integer',
        'difference_grams' => 'integer',
        'percentage_difference' => 'float',
        'moisture_percentage' => 'float',
        'is_flagged_red' => 'boolean',
    ];

    public function isPercentageAboveThreshold()
    {
        return abs($this->percentage_difference ?? 0) > 5;
    }

     public function getFormattedPercentageAttribute()
    {
        if ($this->percentage_difference === null) {
            return '-';
        }

        $percentage = abs($this->percentage_difference); 
        
        if ($percentage == floor($percentage)) {
            return number_format($percentage, 0, ',', '.') . '%';
        } else {
            return number_format($percentage, 1, ',', '.') . '%';
        }
    }

    public function getFormattedDecimalRatioAttribute()
    {
        if ($this->supplier_weight_grams <= 0) {
            return '0,000';
        }
        
        $decimal = $this->difference_grams / $this->supplier_weight_grams;
        return number_format($decimal, 3, ',', '.');
    }

    public function getFormattedDifferenceAttribute()
    {
        if ($this->difference_grams < 0) {
            return number_format($this->difference_grams, 0, ',', '.') . ' gr (susut)';
        } elseif ($this->difference_grams > 0) {
            return '+' . number_format($this->difference_grams, 0, ',', '.') . ' gr (kelebihan)';
        } else {
            return '0 gr (sama)';
        }
    }

    // ✅ Helper method untuk mendapatkan class CSS berdasarkan persentase
     public function getPercentageColorClassAttribute()
    {
        if ($this->percentage_difference === null || $this->percentage_difference == 0) {
            return 'text-gray-600';
        }

        $absPercentage = abs($this->percentage_difference);

        // ✅ Merah jika di atas 5%
        if ($absPercentage > 5) {
            return 'text-red-600 font-bold bg-red-50 px-1 py-0.5 rounded';
        }

        // ✅ Orange jika 1% - 5%
        if ($absPercentage > 1) {
            return 'text-orange-600';
        }

        // ✅ Hijau jika < 1%
        return 'text-green-600';
    }

    public function purchaseReceipt()
    {
        return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipt_id');
    }

    public function gradeSupplier()
    {
        return $this->belongsTo(GradeSupplier::class, 'grade_supplier_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helper methods untuk status
    public function isMentah()
    {
        return $this->status === self::STATUS_MENTAH;
    }

    public function isSelesaiDisortir()
    {
        return $this->status === self::STATUS_SELESAI_DISORTIR;
    }

    // Scope untuk query berdasarkan status
    public function scopeMentah($query)
    {
        return $query->where('status', self::STATUS_MENTAH);
    }

    public function scopeSelesaiDisortir($query)
    {
        return $query->where('status', self::STATUS_SELESAI_DISORTIR);
    }

    public function sortingResults()
    {
        return $this->hasMany(SortingResult::class, 'receipt_item_id');
    }

    public function hasSortingResults()
    {
        return $this->sortingResults()->exists();
    }

    public function canBeGraded()
    {
        return $this->isMentah() && !$this->hasSortingResults();
    }

    // Method untuk mendapatkan total weight dari hasil grading
    public function getTotalGradedWeight()
    {
        return $this->sortingResults()->sum('weight_grams');
    }
}
