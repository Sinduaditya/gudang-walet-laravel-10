<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use App\Services\Stock\TrackingStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrackingStockController extends Controller
{
    protected TrackingStockService $trackingStockService;

    public function __construct(TrackingStockService $trackingStockService)
    {
        $this->trackingStockService = $trackingStockService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        $trackingStocks = $this->trackingStockService->getGradeCompany($search);

        return view('admin.stock.index', compact(
            'trackingStocks',
            'search'
        ));
    }

    public function detail(Request $request, $id)
    {
        $grade = $this->trackingStockService->getGradeById($id);
        $search = $request->input('search');
        $locationStocks = $this->trackingStockService->getStockPerLocation($id, $search);
        
        // Calculate global stock from the retrieved location stocks to ensure consistency
        $globalStock = $locationStocks->sum('total_stock');

        Log::info('Location Stocks:', $locationStocks->toArray());
        return view('admin.stock.detail', compact('grade', 'globalStock', 'locationStocks', 'search'));
    }

    public function susut(Request $request, $id)
    {
        $grade = $this->trackingStockService->getGradeById($id);

        $locations = $this->trackingStockService->getAllLocations();

        $filters = [
            'date' => $request->input('date'),
            'from_location_id' => $request->input('from_location_id'),
            'to_location_id' => $request->input('to_location_id'),
        ];

        $stockTransfers = $this->trackingStockService->getSusutHistory($id, $filters);

        return view('admin.stock.susut', compact('grade', 'locations', 'stockTransfers'));
    }
}
