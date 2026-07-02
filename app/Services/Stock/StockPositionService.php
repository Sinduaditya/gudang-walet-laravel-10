<?php

namespace App\Services\Stock;

use App\Models\StockPosition;
use App\Models\InventoryTransaction;

class StockPositionService
{
    public function updatePosition(InventoryTransaction $tx): void
    {
        if (!$tx->grade_company_id || !$tx->location_id) {
            return;
        }

        $quantity = (float) InventoryTransaction::where('grade_company_id', $tx->grade_company_id)
            ->where('location_id', $tx->location_id)
            ->whereNull('deleted_at')
            ->sum('quantity_change_grams');

        if ($quantity > 0.01) {
            StockPosition::updateOrCreate(
                [
                    'grade_company_id' => $tx->grade_company_id,
                    'location_id' => $tx->location_id,
                ],
                ['quantity_grams' => $quantity]
            );
        } else {
            StockPosition::where('grade_company_id', $tx->grade_company_id)
                ->where('location_id', $tx->location_id)
                ->delete();
        }
    }

    public function getPosition(int $gradeId, int $locationId): float
    {
        return (float) (StockPosition::where('grade_company_id', $gradeId)
            ->where('location_id', $locationId)
            ->value('quantity_grams') ?? 0);
    }

    public function rebuildCache(): void
    {
        StockPosition::truncate();

        $positions = InventoryTransaction::selectRaw('grade_company_id, location_id, SUM(quantity_change_grams) as total')
            ->whereNull('deleted_at')
            ->whereNotNull('grade_company_id')
            ->whereNotNull('location_id')
            ->groupBy('grade_company_id', 'location_id')
            ->havingRaw('total > 0.01')
            ->get();

        foreach ($positions as $pos) {
            StockPosition::create([
                'grade_company_id' => $pos->grade_company_id,
                'location_id' => $pos->location_id,
                'quantity_grams' => $pos->total,
            ]);
        }
    }
}
