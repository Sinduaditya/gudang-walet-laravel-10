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

        // Now fetching Parent Grades instead of all Grade Companies
        $parentGrades = $this->trackingStockService->getParentGradeCompany($search);

        return view('admin.stock.index', compact(
            'parentGrades',
            'search'
        ));
    }

    public function parentGrades(Request $request, $id)
    {
        $parentGrade = $this->trackingStockService->getParentGradeById($id);
        $search = $request->input('search');
        $gradeCompanies = $this->trackingStockService->getChildGrades($id, $search);
        $globalStock = $this->trackingStockService->calculateParentGlobalStock($id);

        return view('admin.stock.parent-grades', compact('parentGrade', 'gradeCompanies', 'search', 'globalStock'));
    }

    public function parentSorts(Request $request, $id)
    {
        $parentGrade = $this->trackingStockService->getParentGradeById($id);
        $search = $request->input('search');
        $sortMaterials = $this->trackingStockService->getSortMaterials($id, $search);
        $globalStock = $this->trackingStockService->calculateParentGlobalStock($id);
        $sortStock = $this->trackingStockService->calculateParentSortStock($id);

        return view('admin.stock.parent-sorts', compact('parentGrade', 'sortMaterials', 'search', 'globalStock', 'sortStock'));
    }

    public function detail(Request $request, $id)
    {
        $grade = $this->trackingStockService->getGradeById($id);
        $search = $request->input('search');
        $locationStocks = $this->trackingStockService->getStockPerLocation($id, $search);

        // Calculate global stock from the retrieved location stocks to ensure consistency
        $globalStock = $locationStocks->sum('total_stock');

        // Log::info('Location Stocks:', $locationStocks->toArray());
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
