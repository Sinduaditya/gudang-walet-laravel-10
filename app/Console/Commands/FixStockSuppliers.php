<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventoryTransaction;
use App\Models\SortingResult;
use Illuminate\Support\Facades\DB;

class FixStockSuppliers extends Command
{
    protected $signature = 'stock:fix-suppliers';
    protected $description = 'Fix missing supplier_id and sorting_result_id in inventory_transactions';

    public function handle()
    {
        $this->info('Starting Stock Supplier Fix...');

        $transactions = InventoryTransaction::where('transaction_type', 'GRADING_IN')
            ->whereNull('supplier_id')
            ->get();

        $this->info("Found {$transactions->count()} GRADING_IN transactions with missing supplier.");

        $bar = $this->output->createProgressBar($transactions->count());

        foreach ($transactions as $tx) {
            // GRADING_IN stores SortingResult ID in reference_id
            $sortingResultId = $tx->reference_id;
            
            if (!$sortingResultId) {
                $bar->advance();
                continue;
            }

            $sortingResult = SortingResult::with(['receiptItem.purchaseReceipt'])->find($sortingResultId);

            if ($sortingResult) {
                $supplierId = null;
                if ($sortingResult->receiptItem && $sortingResult->receiptItem->purchaseReceipt) {
                    $supplierId = $sortingResult->receiptItem->purchaseReceipt->supplier_id;
                }

                $tx->update([
                    'supplier_id' => $supplierId,
                    'sorting_result_id' => $sortingResult->id
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Fix completed.');
        return 0;
    }
}
