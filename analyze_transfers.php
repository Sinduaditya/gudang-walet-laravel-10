<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\StockTransfer;
use App\Models\Location;

echo "==== TO_LOCATION ANALYSIS FOR STOCK_TRANSFERS ====\n\n";

// Query to_location distribution
$results = DB::table('stock_transfers')
    ->join('locations', 'stock_transfers.to_location_id', '=', 'locations.id')
    ->select('locations.name', 'locations.id', DB::raw('COUNT(*) as count'))
    ->groupBy('locations.name', 'locations.id')
    ->orderByDesc('count')
    ->get();

echo "To Locations Distribution:\n";
foreach ($results as $row) {
    echo "  {$row->name} (ID: {$row->id}): {$row->count} transfers\n";
}

echo "\n---\n\n";

// Check if DMK is one of the destinations
$dmkTransfers = StockTransfer::join('locations', 'stock_transfers.to_location_id', '=', 'locations.id')
    ->where('locations.name', 'like', '%DMK%')
    ->count();

echo "Transfers to DMK-related locations: $dmkTransfers\n";

echo "\n---\n\n";

// Check which TRANSFER_OUT records should have TRANSFER_IN but don't
echo "Checking TRANSFER_OUT records without TRANSFER_IN:\n";

$outWithoutIn = DB::table('inventory_transactions as t1')
    ->leftJoin('inventory_transactions as t2', function ($join) {
        $join->on('t1.reference_id', '=', 't2.reference_id')
            ->where('t2.transaction_type', '=', 'TRANSFER_IN');
    })
    ->where('t1.transaction_type', '=', 'TRANSFER_OUT')
    ->whereNull('t2.id')
    ->select(
        't1.id',
        't1.reference_id',
        't1.grade_company_id',
        't1.location_id',
        't1.quantity_change_grams'
    )
    ->limit(10)
    ->get();

echo "Sample TRANSFER_OUT records without TRANSFER_IN:\n";
echo "  ID | Reference | Grade | Location | Quantity\n";
foreach ($outWithoutIn as $tx) {
    echo "  {$tx->id} | {$tx->reference_id} | {$tx->grade_company_id} | {$tx->location_id} | " . number_format($tx->quantity_change_grams / 1000, 2) . " kg\n";
}

// Now check the to_location for those transfers
echo "\n\nCorresponding StockTransfer to_locations:\n";
$transferIds = collect($outWithoutIn)->pluck('reference_id')->toArray();
if (!empty($transferIds)) {
    $toLocations = StockTransfer::whereIn('stock_transfers.id', $transferIds)
        ->join('locations', 'stock_transfers.to_location_id', '=', 'locations.id')
        ->select('stock_transfers.id', 'locations.name')
        ->get();

    echo "  ST_ID | To Location\n";
    foreach ($toLocations as $st) {
        echo "  {$st->id} | {$st->name}\n";
    }
}
