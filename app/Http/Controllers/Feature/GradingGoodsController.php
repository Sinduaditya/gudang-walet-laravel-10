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
            'supplier_name' => $request->get('supplier_name'),
            'grading_date' => $request->get('grading_date'),
        ];

        $gradings = $this->gradingGoodsService->getAllGrading($filters);

        $suppliers = $this->gradingGoodsService->getSuppliers();

        return view('admin.grading-goods.index', compact('gradings', 'suppliers'));
    }

    public function show(Request $request, $receiptItemId)
    {
        $allGradingResults = $this->gradingGoodsService->getSortingResultsByReceiptItem($receiptItemId);

        if ($allGradingResults->isEmpty()) {
            return redirect()->route('grading-goods.index')
                ->with('error', 'Data grading tidak ditemukan.');
        }

        $grading = $allGradingResults->first();

        $notaWeight = $grading->receiptItem->supplier_weight_grams ?? 0;

        $page = $request->get('page');
        $month = $request->get('month');
        $year = $request->get('year');

        // ✅ G-01: Cek apakah grading bisa di-edit (belum ada transaksi keluar)
        $canEdit = !$this->isGradingUsedInOutgoingTransaction($receiptItemId);

        return view('admin.grading-goods.show', compact('grading', 'allGradingResults', 'notaWeight', 'page', 'month', 'year', 'canEdit'));
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
            \Illuminate\Support\Facades\Log::error('GradingGoods storeStep2 error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'sorting_result_id' => $id,
            ]);
            return back()->withInput()->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi atau hubungi administrator.');
        }
    }

    public function export(Request $request)
    {
        // ✅ G-12: Wajib pilih minimal satu filter
        $hasFilter = !empty($request->get('month'))
            || !empty($request->get('year'))
            || !empty($request->get('supplier_name'))
            || !empty($request->get('grading_date'));

        if (!$hasFilter) {
            return back()->with('error', 'Pilih minimal satu filter (Bulan, Tahun, Supplier, atau Tanggal Grading) sebelum export.');
        }

        $filters = [
            'month' => $request->get('month'),
            'year' => $request->get('year'),
            'supplier_name' => $request->get('supplier_name'),
            'grading_date' => $request->get('grading_date'),
        ];

        $fileName = 'laporan_grading_barang';

        if (!empty($filters['month']) || !empty($filters['year']) || !empty($filters['supplier_name']) || !empty($filters['grading_date'])) {
            $fileName .= '_';
            if (!empty($filters['supplier_name'])) {
                $fileName .= 'supplier_' . \Illuminate\Support\Str::slug($filters['supplier_name']) . '_';
            }
            if (!empty($filters['grading_date'])) {
                $fileName .= 'tgl_' . $filters['grading_date'] . '_';
            }
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

    public function edit(Request $request, $receiptItemId)
    {
        $allGradingResults = $this->gradingGoodsService->getSortingResultsByReceiptItem($receiptItemId);

        if ($allGradingResults->isEmpty()) {
            return redirect()->route('grading-goods.index')->with('error', 'Data grading tidak ditemukan.');
        }

        // ✅ G-01: Cek apakah grading sudah digunakan di transaksi keluar
        if ($this->isGradingUsedInOutgoingTransaction($receiptItemId)) {
            return redirect()->route('grading-goods.show', $receiptItemId)
                ->with('error', 'Tidak dapat edit. Grading sudah digunakan dalam transaksi barang keluar.');
        }

        $receiptItem = $allGradingResults->first()->receiptItem;
        $allGradeCompanies = $this->gradingGoodsService->getAllGradeCompanies();

        $page = $request->get('page');
        $month = $request->get('month');
        $year = $request->get('year');

        return view('admin.grading-goods.edit', compact('allGradingResults', 'receiptItem', 'allGradeCompanies', 'page', 'month', 'year'));
    }

    /**
     * ✅ G-01: Helper cek apakah grading sudah dipakai di transaksi keluar
     */
    private function isGradingUsedInOutgoingTransaction($receiptItemId): bool
    {
        $sortingResults = $this->gradingGoodsService->getSortingResultsByReceiptItem($receiptItemId);

        foreach ($sortingResults as $sr) {
            $hasOutgoing = \App\Models\InventoryTransaction::where('sorting_result_id', $sr->id)
                ->where('transaction_type', '!=', 'GRADING_IN')
                ->exists();

            if ($hasOutgoing) return true;
        }

        return false;
    }

    public function update(UpdateGradingRequest $request, $receiptItemId)
    {
        // ✅ G-01: Cek apakah grading sudah digunakan di transaksi keluar
        if ($this->isGradingUsedInOutgoingTransaction($receiptItemId)) {
            return back()->with('error', 'Tidak dapat edit. Grading sudah digunakan dalam transaksi barang keluar.');
        }

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

            $redirectParams = [];
            if ($request->has('page')) $redirectParams['page'] = $request->page;
            if ($request->has('month')) $redirectParams['month'] = $request->month;
            if ($request->has('year')) $redirectParams['year'] = $request->year;

            return redirect()->route('grading-goods.show', array_merge(['receiptItemId' => $receiptItemId], $redirectParams))->with('success', 'Data grading berhasil diperbarui.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('GradingGoods update error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'receipt_item_id' => $receiptItemId,
            ]);
            return back()->withInput()->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi atau hubungi administrator.');
        }
    }

    public function destroy($receiptItemId)
    {
        try {
            $this->gradingGoodsService->deleteGrading($receiptItemId);
            return redirect()->route('grading-goods.index')->with('success', 'Data grading berhasil dihapus.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('GradingGoods destroy error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'receipt_item_id' => $receiptItemId,
            ]);
            return back()->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi atau hubungi administrator.');
        }
    }

    // ✅ G-21: Cancel Step 2 - cleanup orphan SortingResult
    public function cancelStep2($sortingResultId)
    {
        $sortingResult = \App\Models\SortingResult::find($sortingResultId);

        if ($sortingResult && is_null($sortingResult->weight_grams)) {
            $sortingResult->deleted_by = auth()->id();
            $sortingResult->save();
            $sortingResult->delete();

            \Illuminate\Support\Facades\Log::info('Orphan SortingResult dari Step 1 dihapus via cancel', [
                'sorting_result_id' => $sortingResultId,
                'user_id' => auth()->id(),
            ]);
        }

        return redirect()->route('grading-goods.step1')->with('info', 'Proses grading dibatalkan.');
    }
}
