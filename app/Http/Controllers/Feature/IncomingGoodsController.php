<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use App\Http\Requests\IncomingGoods\Step1Request;
use App\Http\Requests\IncomingGoods\Step2Request;
use App\Http\Requests\IncomingGoods\Step3Request;
use App\Services\IncomingGoods\IncomingGoodsService;
use Illuminate\Http\Request;

class IncomingGoodsController extends Controller
{
    protected $incomingGoodsService;

    public function __construct(IncomingGoodsService $incomingGoodsService)
    {
        $this->incomingGoodsService = $incomingGoodsService;
    }

    public function index(Request $request)
    {
        $filters = [
            'month' => $request->get('month'),
            'year' => $request->get('year'),
        ];

        $receipts = $this->incomingGoodsService->getAllReceipts($filters);
        return view('admin.incoming_goods.index', compact('receipts'));
    }

    /**
     * STEP 1: Show form to select supplier and grades
     */
    public function createStep1()
    {
        $suppliers = $this->incomingGoodsService->getSuppliers();
        $gradeSuppliers = $this->incomingGoodsService->getGradeSuppliers();

        return view('admin.incoming_goods.step1', compact('suppliers', 'gradeSuppliers'));
    }

    /**
     * STEP 1: Store data to session and redirect to step 2
     */
    public function storeStep1(Step1Request $request)
    {
        // Store step 1 data to session
        session(['step1_data' => $request->validated()]);

        return redirect()->route('incoming-goods.step2')->with('success', 'Data penerimaan berhasil disimpan. Lanjut ke input berat nota.');
    }

    /**
     * STEP 2: Show form to input initial weights
     */
    public function createStep2()
    {
        // Check if step 1 data exists
        if (!session()->has('step1_data')) {
            return redirect()->route('incoming-goods.step1')->with('error', 'Silakan lengkapi data penerimaan terlebih dahulu');
        }

        $step1Data = session('step1_data');
        $supplier = $this->incomingGoodsService->getSupplierById($step1Data['supplier_id']);
        $grades = $this->incomingGoodsService->getSelectedGrades($step1Data['grade_ids']);

        return view('admin.incoming_goods.step2', compact('supplier', 'grades', 'step1Data'));
    }

    /**
     * STEP 2: Store data to session and redirect to step 3
     */
    public function storeStep2(Step2Request $request)
    {
        // Check if step 1 data exists
        if (!session()->has('step1_data')) {
            return redirect()->route('incoming-goods.step1')->with('error', 'Silakan mulai dari awal');
        }

        // Store step 2 data to session
        session(['step2_data' => $request->validated()]);

        return redirect()->route('incoming-goods.step3')->with('success', 'Data berat nota berhasil disimpan. Lanjut ke input timbangan gudang.');
    }

    /**
     * STEP 3: Show form to input final weights
     */
    public function createStep3()
    {
        // Check if previous steps data exists
        if (!session()->has('step1_data') || !session()->has('step2_data')) {
            return redirect()->route('incoming-goods.step1')->with('error', 'Silakan mulai dari awal');
        }

        $step1Data = session('step1_data');
        $step2Data = session('step2_data');
        $supplier = $this->incomingGoodsService->getSupplierById($step1Data['supplier_id']);
        $grades = $this->incomingGoodsService->getSelectedGrades($step1Data['grade_ids']);

        return view('admin.incoming_goods.step3', compact('supplier', 'grades', 'step1Data', 'step2Data'));
    }

    /**
     * STEP 3: Final store - save to database
     */
    public function storeFinal(Step3Request $request)
    {
        // Check if previous steps data exists
        if (!session()->has('step1_data') || !session()->has('step2_data')) {
            return redirect()->route('incoming-goods.step1')->with('error', 'Silakan mulai dari awal');
        }

        try {
            $step1Data = session('step1_data');
            $step2Data = session('step2_data');
            $step3Data = $request->validated();

            // Save to database
            $receipt = $this->incomingGoodsService->createPurchaseReceipt($step1Data, $step2Data, $step3Data);

            // Clear session
            $this->incomingGoodsService->clearWizardSession();

            return redirect()->route('incoming-goods.show', $receipt->id)->with('success', 'Data barang masuk berhasil disimpan!');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Show receipt details
     */
    public function show($id)
    {
        $receipt = \App\Models\PurchaseReceipt::with(['supplier', 'receiptItems.gradeSupplier'])->findOrFail($id);

        return view('admin.incoming_goods.show', compact('receipt'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $receipt = $this->incomingGoodsService->getReceiptById($id);
        $suppliers = $this->incomingGoodsService->getSuppliers();
        $gradeSuppliers = $this->incomingGoodsService->getGradeSuppliers();

        return view('admin.incoming_goods.edit', compact('receipt', 'suppliers', 'gradeSuppliers'));
    }

    /**
     * Update receipt
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'receipt_date' => 'required|date',
                'unloading_date' => 'required|date',
                'notes' => 'nullable|string|max:500',
                'items' => 'required|array|min:1',
                'items.*.grade_supplier_id' => 'required|exists:grades_supplier,id', 
                'items.*.supplier_weight_grams' => 'required|numeric|min:0',
                'items.*.warehouse_weight_grams' => 'required|numeric|min:0',
                'items.*.moisture_percentage' => 'nullable|numeric|min:0|max:100',
            ]);

            $receipt = $this->incomingGoodsService->updateReceipt($id, $validated);

            return redirect()->route('incoming-goods.show', $receipt->id)->with('success', 'Data barang masuk berhasil diperbarui!');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel wizard and clear session
     */
    public function cancel()
    {
        $this->incomingGoodsService->clearWizardSession();
        return redirect()->route('dashboard')->with('info', 'Input barang masuk dibatalkan');
    }

    /**
     * Delete purchase receipt
     */
    public function destroy($id)
    {
        try {
            $this->incomingGoodsService->deleteReceipt($id);
            return redirect()->route('incoming-goods.index')->with('success', 'Data barang masuk berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        try {
            $filters = [
                'month' => $request->get('month'),
                'year' => $request->get('year'),
            ];

            return $this->incomingGoodsService->exportToExcel($filters);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengekspor data: ' . $e->getMessage());
        }
    }
}
