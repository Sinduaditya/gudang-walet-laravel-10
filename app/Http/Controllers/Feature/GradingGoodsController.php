<?php

namespace App\Http\Controllers\Feature;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\GradingGoods\Step1Request;
use App\Http\Requests\GradingGoods\Step2Request;
use App\Services\GradingGoods\GradingGoodsService;
use App\Http\Requests\GradingGoods\UpdateGradingRequest;
use App\Exports\GradingGoodsExport;
use Maatwebsite\Excel\Facades\Excel;

class GradingGoodsController extends Controller
{
    protected $gradingGoodsService;

    public function __construct(GradingGoodsService $gradingGoodsService)
    {
        $this->gradingGoodsService = $gradingGoodsService;
    }

    public function index(Request $request)
    {
        $filters = [
            'month' => $request->get('month'),
            'year' => $request->get('year'),
        ];

        $gradings = $this->gradingGoodsService->getAllGrading($filters);

        return view('admin.grading-goods.index', compact('gradings'));
    }

    public function show($receiptItemId)
    {
        $allGradingResults = $this->gradingGoodsService->getSortingResultsByReceiptItem($receiptItemId);

        if ($allGradingResults->isEmpty()) {
            return abort(404, 'Grading not found');
        }

        $grading = $allGradingResults->first();

        $notaWeight = $grading->receiptItem->supplier_weight_grams ?? 0;

        return view('admin.grading-goods.show', compact('grading', 'allGradingResults', 'notaWeight'));
    }

    public function createStep1(Request $request)
    {
        $q = $request->query('q');
        $receiptItems = $this->gradingGoodsService->getReceiptItemsByGradeSupplierName($q);
        return view('admin.grading-goods.step1', compact('receiptItems', 'q'));
    }

    public function storeStep1(Step1Request $request)
    {
        $sortingResult = $this->gradingGoodsService->createSortingResultStep1($request->input('grading_date'), $request->input('receipt_item_id'));

        return redirect()
            ->route('grading-goods.step2', ['id' => $sortingResult->id])
            ->with('success', 'Step 1 tersimpan. Lanjutkan ke Step 2.');
    }

    public function createStep2($id)
    {
        $sortingResult = $this->gradingGoodsService->getSortingResultWithRelations($id);
        if (!$sortingResult) {
            return redirect()->route('grading-goods.index')->with('error', 'Data grading tidak ditemukan.');
        }

        $allGradeCompanies = $this->gradingGoodsService->getAllGradeCompanies();

        return view('admin.grading-goods.step2', compact('sortingResult', 'allGradeCompanies'));
    }

    public function storeStep2(Step2Request $request, $id)
    {
        try {
            $grades = $request->input('grades');
            $globalNotes = $request->input('global_notes');

            $results = $this->gradingGoodsService->updateSortingResultStep2Multiple($id, $grades, $globalNotes);

            $gradesCount = count($results);
            $totalWeight = collect($grades)->sum('weight_grams');

            return redirect()
                ->route('grading-goods.index')
                ->with('success', "Grading berhasil disimpan! Menghasilkan {$gradesCount} grade dengan total berat {$totalWeight} gram.");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $filters = [
            'month' => $request->get('month'),
            'year' => $request->get('year'),
        ];

        $fileName = 'laporan_grading_barang';

        if (!empty($filters['month']) || !empty($filters['year'])) {
            $fileName .= '_';
            if (!empty($filters['month'])) {
                $fileName .= 'bulan_' . $filters['month'];
            }
            if (!empty($filters['year'])) {
                $fileName .= '_tahun_' . $filters['year'];
            }
        }

        $fileName .= '_' . date('Y-m-d') . '.xlsx';

        $export = new GradingGoodsExport($this->gradingGoodsService, $filters);
        return Excel::download($export, $fileName);
    }

    public function edit($receiptItemId)
    {
        $allGradingResults = $this->gradingGoodsService->getSortingResultsByReceiptItem($receiptItemId);

        if ($allGradingResults->isEmpty()) {
            return redirect()->route('grading-goods.index')->with('error', 'Data grading tidak ditemukan.');
        }

        $receiptItem = $allGradingResults->first()->receiptItem;
        $allGradeCompanies = $this->gradingGoodsService->getAllGradeCompanies();

        return view('admin.grading-goods.edit', compact('allGradingResults', 'receiptItem', 'allGradeCompanies'));
    }

    public function update(Request $request, $receiptItemId)
    {
        $request->validate([
            'grades.*.grading_date' => 'required|date',
            'grades.*.grade_company_name' => 'required|string|max:255',
            'grades.*.quantity' => 'required|numeric|min:0', 
            'grades.*.weight_grams' => 'required|numeric|min:0', 
            'grades.*.notes' => 'nullable|string',
            'grades.*.outgoing_type' => 'nullable|in:penjualan_langsung,internal,external',
            'grades.*.category_grade' => 'nullable|in:IDM A,IDM B',
            'global_notes' => 'nullable|string',
        ]);

        try {
            $grades = $request->input('grades');
            $globalNotes = $request->input('global_notes');

            $processedGrades = [];
            foreach ($grades as $grade) {
                $processedGrades[] = [
                    'grading_date' => $grade['grading_date'],
                    'grade_company_name' => $grade['grade_company_name'],
                    'quantity' => (int) $grade['quantity'], 
                    'weight_grams' => (int) $grade['weight_grams'], 
                    'outgoing_type' => $grade['outgoing_type'] ?? null,
                    'category_grade' => $grade['category_grade'] ?? null,
                    'notes' => $grade['notes'] ?? null,

                ];
            }

            $this->gradingGoodsService->updateMultipleSortingResults($receiptItemId, $processedGrades, $globalNotes);

            return redirect()->route('grading-goods.index')->with('success', 'Data grading berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy($receiptItemId)
    {
        try {
            $this->gradingGoodsService->deleteGrading($receiptItemId);
            return redirect()->route('grading-goods.index')->with('success', 'Data grading berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
