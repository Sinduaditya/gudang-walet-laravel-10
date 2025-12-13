<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Dashboard\DashboardService;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // Ambil semua data dashboard
        $statisticsCards = $this->dashboardService->getStatisticsCards();
        $flowBarang = $this->dashboardService->getFlowBarangMasukKeluar();
        $flowDMK = $this->dashboardService->getFlowBarangKeDMK();
        $jasaCuci = $this->dashboardService->getFlowBarangKeJasaCuci();
        $supplierData = $this->dashboardService->getBarangMasukPerSupplier();
        $gradingData = $this->dashboardService->getTotalBarangGradingPerHari();
        $recentActivities = $this->dashboardService->getRecentActivities();
        $stockSummary = $this->dashboardService->getCurrentStockSummary();

        return view('admin.dashboard', compact(
            'statisticsCards',
            'flowBarang',
            'flowDMK',
            'jasaCuci',
            'supplierData',
            'gradingData',
            'recentActivities',
            'stockSummary'
        ));
    }
}