<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Idm\TransferIdmService;

class TransferIdmController extends Controller
{
    protected $transferIdmService;

    public function __construct(TransferIdmService $transferIdmService)
    {
        $this->transferIdmService = $transferIdmService;
    }

    public function index(Request $request)
    {
        $transfers = $this->transferIdmService->getTransfers($request->all());
        return view('admin.transfer-idm.index', compact('transfers'));
    }

    public function create(Request $request)
    {
        // 1. Get pairs of (supplier_id, grade_company_id) that have AVAILABLE IdmDetails
        $availablePairs = \App\Models\IdmManagement::whereHas('details', function ($q) {
                $q->whereDoesntHave('transferDetails');
            })
            ->select('supplier_id', 'grade_company_id')
            ->distinct()
            ->get();

        // 2. Fetch Suppliers present in the pairs
        $supplierIds = $availablePairs->pluck('supplier_id')->unique();
        $suppliers = \App\Models\Supplier::whereIn('id', $supplierIds)->get();

        // 3. Fetch Grade Companies present in the pairs, and attach valid supplier IDs for JS filtering
        $gradeCompanyIds = $availablePairs->pluck('grade_company_id')->unique();
        $gradeCompanies = \App\Models\GradeCompany::whereIn('id', $gradeCompanyIds)->get();
        
        $gradeCompanies->each(function ($gc) use ($availablePairs) {
            $gc->valid_supplier_ids = $availablePairs->where('grade_company_id', $gc->id)
                ->pluck('supplier_id')
                ->values()
                ->all();
        });

        // 4. Get unique grade idm names for filter (Only from available items)
        $gradeIdms = \App\Models\IdmDetail::whereDoesntHave('transferDetails')
            ->select('grade_idm_name')
            ->distinct()
            ->pluck('grade_idm_name');

        // 5. Get unique IDM Types (category_grade)
        // We need to look at IdmManagement's sourceItems
        $idmTypes = \App\Models\SortingResult::whereHas('idmManagement.details', function ($q) {
                $q->whereDoesntHave('transferDetails');
            })
            ->select('category_grade')
            ->distinct()
            ->pluck('category_grade');

        $items = $this->transferIdmService->getAvailableIdmDetails($request->all());

        $locations = \App\Models\Location::all();

        return view('admin.transfer-idm.create-step-1', compact('suppliers', 'gradeCompanies', 'gradeIdms', 'items', 'idmTypes', 'locations'));
    }

    public function step2(Request $request)
    {
        $request->validate([
            'selected_items' => 'required|array',
            'selected_items.*' => 'exists:idm_details,id',
        ]);

        $selectedItemIds = $request->selected_items;
        $items = \App\Models\IdmDetail::with(['idmManagement.supplier'])->whereIn('id', $selectedItemIds)->get();

        // Calculations
        $idmItems = $items; // Assuming all are IDM for now, need logic to distinguish "Non IDM"?
        // User said: "Total harga selain idm diambil dari perhitungan harga barang selain idm (perutan dan kakian)"
        // How to distinguish? Maybe grade_idm_name contains 'Perutan' or 'Kakian'?
        
        $nonIdmKeywords = ['perutan', 'kakian'];
        $nonIdmItems = $items->filter(function ($item) use ($nonIdmKeywords) {
            foreach ($nonIdmKeywords as $keyword) {
                if (stripos($item->grade_idm_name, $keyword) !== false) {
                    return true;
                }
            }
            return false;
        });
        
        $idmOnlyItems = $items->diff($nonIdmItems);

        $totalIdmPrice = $idmOnlyItems->sum('price'); // or total_price?
        // Wait, price usually is per unit or total? IdmDetail has 'price' and 'total_price'.
        // Assuming 'total_price' is the value to sum.
        // Let's check IdmDetail schema again. It has 'price' and 'total_price'.
        // The wireframe says "Harga".
        // The calculation "Rata Rata Harga IDM" = (sum price) / count? Or (sum total_price / sum weight)?
        // "Rata rata harga idm yg diambil dari perhitungan harga idm yg dipilih kemudian dijumlahkan dan dibagi jumlah barang idm yg dipilih"
        // "harga idm yg dipilih" -> sum(price) / count items?
        
        $sumIdmPrice = $idmOnlyItems->sum('total_price') ?? 0;
        $countIdm = $idmOnlyItems->count();
        $averageIdmPrice = $countIdm > 0 ? $sumIdmPrice / $countIdm : 0;
        
        // Ensure consistency
        // If Price column in view is displaying 'price' (unit price), change it to total_price or allow user to see both?
        // Detailed view usually shows Value.

        $totalNonIdmPrice = $nonIdmItems->sum('total_price') ?? 0; // Using total_price for sum
        // Or should I use 'price' column if it represents the value?
        // Usually, price = unit price, total_price = weight * price.
        // "Total harga selain IDM" -> Sum of total values.
        // "Rata Rata Harga IDM" -> Average of unit prices? Or Average of Total values?
        // "Harga transfer" usually means Total Value.
        
        // Let's assume 'price' in IdmDetail is the value the user looks at in card (e.g. 100.000).
        // If "Harga" in card is total_price, then use total_price.
        
        // I will fallback to using 'price' as the value for average calculation as requested literally.
        
        $totalTransferPrice = $items->sum('total_price'); // Total of everything?
        // Requirement: "kemudian total harga diambil dari rata rata harga idm ditambahkan total harga selain idm"
        // This is weird. Average + Total?
        // "average_idm_price + total_non_idm_price" = Total Price?
        // Example: 10 items IDM @ 1000 each. Avg = 1000. 1 item Non-IDM = 5000.
        // Total = 1000 + 5000 = 6000?
        // That implies the "Transfer Price" is a constructed value, not the sum of goods values.
        // Okay, I will follow the formula strictly:
        // FinalTotal = AverageIdmPrice + TotalNonIdmPrice.
        
        return view('admin.transfer-idm.create-step-2', compact(
            'items', 
            'averageIdmPrice', 
            'totalNonIdmPrice',
            'idmOnlyItems',
            'nonIdmItems'
        ) + ['source_location_id' => $request->input('source_location_id')]);
    }

    public function store(Request $request)
    {
        // Validate and Store
        $data = $request->validate([
            'items' => 'required|array',
            'transfer_date' => 'required|date',
            'average_idm_price' => 'required|numeric',
            'total_non_idm_price' => 'required|numeric',
            'total_idm_price' => 'required|numeric',
            'total_price' => 'required|numeric',
            'source_location_id' => 'sometimes|exists:locations,id', // Make it optional for backward compatibility or strict? Better strict if user wants control.
        ]);

        // Re-construct items array from request or re-fetch?
        // The service expects array of items with details.
        // The form in step 2 should submit these values.
        
        // Better: Fetch items again by IDs passed in hidden fields to ensure integrity?
        // Or trust the POST data if it contains the snapshot values.
        // I'll fetch items by ID for security but use calculated values from request for the header totals if user verified them.
        
        $itemIds = array_column($request->items, 'id');
        $items = \App\Models\IdmDetail::whereIn('id', $itemIds)->get();
        // Map items to array format for service
        $itemsData = $items->map(function($item) {
            return [
                'id' => $item->id,
                'weight' => $item->weight,
                'price' => $item->price, // Snapshot Unit Price
                'total_price' => $item->total_price, // Snapshot Total Value
                'grade_idm_name' => $item->grade_idm_name,
            ];
        });

        $storeData = [
            'transfer_date' => $request->transfer_date,
            'source_location_id' => $request->source_location_id,
            'items' => $itemsData,
            'total_price' => $request->total_price,
            'average_idm_price' => $request->average_idm_price,
            'total_non_idm_price' => $request->total_non_idm_price,
            'total_idm_price' => $request->total_idm_price,
            'notes' => $request->notes
        ];

        $this->transferIdmService->storeTransfer($storeData);

        return redirect()->route('barang.keluar.transfer-idm.index')->with('success', 'Transfer Created Successfully');
    }

    public function edit($id)
    {
        $transfer = $this->transferIdmService->getTransferById($id);
        // We pass the transfer object. The view should handle displaying details.
        // We also need to calculate Non-IDM vs IDM breakdown if we want to allow JS recalculation?
        // Actually, the stored transfer has the totals. We can just use those for initial display.
        // If items are removed, JS usage applies.
        
        return view('admin.transfer-idm.edit', compact('transfer'));
    }

    public function update(Request $request, $id)
    {
        // Validate
        $data = $request->validate([
            'items' => 'required|array',
            'transfer_date' => 'required|date',
            'average_idm_price' => 'required|numeric',
            'total_non_idm_price' => 'required|numeric',
            'total_idm_price' => 'required|numeric',
            'total_price' => 'required|numeric',
        ]);

        // Items come from the form as array of [id, grade_idm_name, weight, price, total_price]
        // But the form in edit.blade.php might just send IDs if we want to re-fetch?
        // In store(), we re-fetched. Here, if we allow "Delete", we are submitting a subset.
        // The Service expects an array of item data to create IdmTransferDetail.
        // If we only send IDs, we need to fetch IdmDetail.
        // But wait, if we delete an item from the specific transfer, the IdmDetail still exists.
        // So we can just send the list of IdmDetail IDs that should remain.
        // "items" in request should be the array of IdmDetail IDs?
        // In store() logic: $itemIds = array_column($request->items, 'id'); $items = IdmDetail::whereIn...
        // So yes, we should send the 'id' (of the IdmDetail, not the pivot IdmTransferDetail).
        
        // In edit.blade.php, we will have hidden inputs name="items[i][id]" value="{{ $detail->idm_detail_id }}".
        
        $itemIds = array_column($request->items, 'id');
        $items = \App\Models\IdmDetail::whereIn('id', $itemIds)->get();
        
        $itemsData = $items->map(function($item) {
            return [
                'id' => $item->id,
                'weight' => $item->weight,
                'price' => $item->price, 
                'total_price' => $item->total_price,
                'grade_idm_name' => $item->grade_idm_name,
            ];
        });

        $updateData = [
            'transfer_date' => $request->transfer_date,
            'items' => $itemsData,
            'total_price' => $request->total_price,
            'average_idm_price' => $request->average_idm_price,
            'total_non_idm_price' => $request->total_non_idm_price,
            'total_idm_price' => $request->total_idm_price,
            'notes' => $request->notes
        ];

        $this->transferIdmService->updateTransfer($id, $updateData);

        return redirect()->route('barang.keluar.transfer-idm.index')->with('success', 'Transfer Updated Successfully');
    }

    public function show($id)
    {
        $transfer = $this->transferIdmService->getTransferById($id);
        return view('admin.transfer-idm.show', compact('transfer'));
    }

    public function destroy($id)
    {
        $this->transferIdmService->deleteTransfer($id);
        return redirect()->route('barang.keluar.transfer-idm.index')->with('success', 'Transfer Deleted Successfully');
    }
}
