<?php

namespace App\Observers;

use App\Models\InventoryTransaction;
use App\Services\Stock\StockPositionService;

class InventoryTransactionObserver
{
    protected StockPositionService $positionService;

    public function __construct(StockPositionService $positionService)
    {
        $this->positionService = $positionService;
    }

    public function created(InventoryTransaction $transaction): void
    {
        $this->positionService->updatePosition($transaction);
    }

    public function restored(InventoryTransaction $transaction): void
    {
        $this->positionService->updatePosition($transaction);
    }

    public function deleted(InventoryTransaction $transaction): void
    {
        $this->positionService->updatePosition($transaction);
    }

    public function forceDeleted(InventoryTransaction $transaction): void
    {
        $this->positionService->updatePosition($transaction);
    }
}
