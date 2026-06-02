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
        try {
            $search = $request->input('search');
            $parentGrades = $this->trackingStockService->getParentGradeCompany($search);

            Log::channel('audit')->info('TrackingStock index accessed', [
                'user_id' => auth()->id(),
                'action' => 'index',
                'search' => $search,
                'ip' => $request->ip(),
            ]);

            return view('admin.stock.index', compact(
                'parentGrades',
                'search'
            ));
        } catch (\Exception $e) {
            Log::error('TrackingStock index error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Terjadi kesalahan saat memuat data. Silakan coba lagi.');
        }
    }

    public function parentGrades(Request $request, $id)
    {
        try {
            $parentGrade = $this->trackingStockService->getParentGradeById($id);
            $search = $request->input('search');
            $gradeCompanies = $this->trackingStockService->getChildGrades($id, $search);

            // TS-02 fix: Use batch calculation instead of N+1 loop
            $gradeIds = $gradeCompanies->pluck('id')->toArray();
            $stockMap = $this->trackingStockService->calculateGlobalStockBulk($gradeIds);

            // Dynamic sortir stock from SortMaterialService
            $sortService = app(\App\Services\SortMaterial\SortMaterialService::class);

            foreach ($gradeCompanies as $item) {
                $item->total_stock = $stockMap[$item->id] ?? 0;
                $item->sort_stock  = $sortService->getSortStockByGrade($item->id); // Dynamic sortir stock
            }

            $globalStock = $this->trackingStockService->calculateParentGlobalStock($id);
            $positiveStock = $this->trackingStockService->calculateParentPositiveStock($id);
            $negativeStock = $this->trackingStockService->calculateParentNegativeStock($id);

            Log::channel('audit')->info('TrackingStock parentGrades accessed', [
                'user_id' => auth()->id(),
                'action' => 'parentGrades',
                'parent_grade_id' => $id,
                'search' => $search,
                'ip' => $request->ip(),
            ]);

            return view('admin.stock.parent-grades', compact(
                'parentGrade',
                'gradeCompanies',
                'search',
                'globalStock',
                'positiveStock',
                'negativeStock'
            ));
        } catch (\Exception $e) {
            Log::error('TrackingStock parentGrades error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'parent_grade_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Terjadi kesalahan saat memuat data. Silakan coba lagi.');
        }
    }

    public function parentSorts(Request $request, $id)
    {
        try {
            $parentGrade = $this->trackingStockService->getParentGradeById($id);
            $search = $request->input('search');
            $sortMaterials = $this->trackingStockService->getSortMaterials($id, $search);
            $globalStock = $this->trackingStockService->calculateParentGlobalStock($id);
            
            // Ambil layanan sortir bahan
            $sortService = app(\App\Services\SortMaterial\SortMaterialService::class);
            
            // 1. Total Stok Sortir (Gabungan Parent + Child)
            $sortStock = $sortService->getStockByParent($id);
            
            // 2. Breakdown untuk catatan Parent (Mentah) vs Child (Pecahan)
            $sortParentStock = $sortService->getNetSortStock($id);
            $sortChildStock = max(0.00, $sortStock - $sortParentStock);

            Log::channel('audit')->info('TrackingStock parentSorts accessed', [
                'user_id' => auth()->id(),
                'action' => 'parentSorts',
                'parent_grade_id' => $id,
                'search' => $search,
                'ip' => $request->ip(),
            ]);

            return view('admin.stock.parent-sorts', compact(
                'parentGrade', 
                'sortMaterials', 
                'search', 
                'globalStock', 
                'sortStock',
                'sortParentStock',
                'sortChildStock'
            ));
        } catch (\Exception $e) {
            Log::error('TrackingStock parentSorts error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'parent_grade_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Terjadi kesalahan saat memuat data. Silakan coba lagi.');
        }
    }

    public function detail(Request $request, $id)
    {
        try {
            $grade = $this->trackingStockService->getGradeById($id);
            $search = $request->input('search');
            $locationStocks = $this->trackingStockService->getStockPerLocation($id, $search);

            $globalStock = $locationStocks->sum('total_stock');

            Log::channel('audit')->info('TrackingStock detail accessed', [
                'user_id' => auth()->id(),
                'action' => 'detail',
                'grade_id' => $id,
                'search' => $search,
                'ip' => $request->ip(),
            ]);

            // TS-08 fix: Store referrer for back button
            session(['tracking_stock_referrer' => url()->previous()]);

            return view('admin.stock.detail', compact('grade', 'globalStock', 'locationStocks', 'search'));
        } catch (\Exception $e) {
            Log::error('TrackingStock detail error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'grade_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Terjadi kesalahan saat memuat data. Silakan coba lagi.');
        }
    }

    public function susut(Request $request, $id)
    {
        try {
            $grade = $this->trackingStockService->getGradeById($id);
            $locations = $this->trackingStockService->getAllLocations();

            $filters = [
                'date' => $request->input('date'),
                'from_location_id' => $request->input('from_location_id'),
                'to_location_id' => $request->input('to_location_id'),
            ];

            $stockTransfers = $this->trackingStockService->getSusutHistory($id, $filters);

            Log::channel('audit')->info('TrackingStock susut accessed', [
                'user_id' => auth()->id(),
                'action' => 'susut',
                'grade_id' => $id,
                'filters' => $filters,
                'ip' => $request->ip(),
            ]);

            return view('admin.stock.susut', compact('grade', 'locations', 'stockTransfers'));
        } catch (\Exception $e) {
            Log::error('TrackingStock susut error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'grade_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Terjadi kesalahan saat memuat data. Silakan coba lagi.');
        }
    }
}
