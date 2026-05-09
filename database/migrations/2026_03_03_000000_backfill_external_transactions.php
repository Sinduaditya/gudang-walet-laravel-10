<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\StockTransfer;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Backfill External Transfers (to Jasa Cuci)
        // Find transfers that have EXTERNAL_TRANSFER_OUT but missing EXTERNAL_TRANSFER_IN
        $externalTransfers = StockTransfer::whereHas('transactions', function($q) {
            $q->where('transaction_type', 'EXTERNAL_TRANSFER_OUT');
        })->with(['transactions', 'toLocation'])->get();

        foreach ($externalTransfers as $transfer) {
            // Check if IN transaction exists
            $hasIn = $transfer->transactions->where('transaction_type', 'EXTERNAL_TRANSFER_IN')->count() > 0;

            if (!$hasIn) {
                InventoryTransaction::create([
                    'transaction_date'       => $transfer->transfer_date,
                    'grade_company_id'       => $transfer->grade_company_id,
                    'location_id'            => $transfer->to_location_id, // Jasa Cuci
                    'quantity_change_grams'  => abs($transfer->weight_grams), // Net weight
                    'transaction_type'       => 'EXTERNAL_TRANSFER_IN',
                    'reference_id'           => $transfer->id,
                    'created_by'             => $transfer->created_by ?? 1,
                    'created_at'             => $transfer->created_at,
                    'updated_at'             => $transfer->updated_at,
                ]);
            }
        }

        // 2. Backfill Receive External (from Jasa Cuci)
        // Find transfers that have RECEIVE_EXTERNAL_IN but missing RECEIVE_EXTERNAL_OUT
        $receiveTransfers = StockTransfer::whereHas('transactions', function($q) {
            $q->where('transaction_type', 'RECEIVE_EXTERNAL_IN');
        })->with(['transactions', 'fromLocation'])->get();

        foreach ($receiveTransfers as $transfer) {
            // Check if OUT transaction exists
            $hasOut = $transfer->transactions->where('transaction_type', 'RECEIVE_EXTERNAL_OUT')->count() > 0;

            if (!$hasOut) {
                $totalDeduction = abs($transfer->weight_grams) + abs($transfer->susut_grams ?? 0);

                InventoryTransaction::create([
                    'transaction_date'       => $transfer->transfer_date,
                    'grade_company_id'       => $transfer->grade_company_id,
                    'location_id'            => $transfer->from_location_id, // Jasa Cuci
                    'quantity_change_grams'  => -$totalDeduction,
                    'transaction_type'       => 'RECEIVE_EXTERNAL_OUT',
                    'reference_id'           => $transfer->id,
                    'created_by'             => $transfer->created_by ?? 1,
                    'created_at'             => $transfer->created_at,
                    'updated_at'             => $transfer->updated_at,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete backfilled transactions
        InventoryTransaction::whereIn('transaction_type', ['EXTERNAL_TRANSFER_IN', 'RECEIVE_EXTERNAL_OUT'])->delete();
    }
};
