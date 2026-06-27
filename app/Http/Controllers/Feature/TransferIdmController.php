<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Idm\TransferIdmService;
use App\Exports\TransferIdmExport;
use Maatwebsite\Excel\Facades\Excel;

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
        $sub = '(SELECT COALESCE(SUM(itd.weight), 0) FROM idm_transfer_details itd
                 INNER JOIN idm_transfers it ON it.id = itd.idm_transfer_id AND it.deleted_at IS NULL
                 WHERE itd.idm_detail_id = idm_details.id AND itd.deleted_at IS NULL)';

        // 1. Get pairs of (supplier_id, grade_company_id) that have AVAILABLE IdmDetails
        $availablePairs = \App\Models\IdmManagement::whereHas('details', function ($q) use ($sub) {
                $q->whereRaw("idm_details.weight > {$sub}");
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
        $gradeIdms = \App\Models\IdmDetail::whereRaw("idm_details.weight > {$sub}")
            ->select('grade_idm_name')
            ->distinct()
            ->pluck('grade_idm_name');

        // 5. Get unique IDM Types (category_grade)
        $idmTypes = \App\Models\SortingResult::whereHas('idmManagement.details', function ($q) use ($sub) {
                $q->whereRaw("idm_details.weight > {$sub}");
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
        if ($request->isMethod('GET')) {
            return redirect()->route('barang.keluar.transfer-idm.create');
        }

        $request->validate([
            'selected_items'   => 'required|array',
            'selected_items.*' => 'exists:idm_details,id',
        ]);

        $sub   = '(SELECT COALESCE(SUM(itd.weight), 0) FROM idm_transfer_details itd
                   INNER JOIN idm_transfers it ON it.id = itd.idm_transfer_id AND it.deleted_at IS NULL
                   WHERE itd.idm_detail_id = idm_details.id AND itd.deleted_at IS NULL)';
        $items = \App\Models\IdmDetail::with(['idmManagement.supplier'])
            ->selectRaw("idm_details.*, (idm_details.weight - {$sub}) AS remaining_weight")
            ->whereIn('idm_details.id', $request->selected_items)
            ->get();
        $locations = \App\Models\Location::all();

        return view('admin.transfer-idm.create-step-2', [
            'items'         => $items,
            'transfer_date' => $request->input('transfer_date'),
            'locations'     => $locations,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'              => 'required|array',
            'items.*.id'         => 'required|exists:idm_details,id',
            'items.*.weight'     => 'required|numeric|min:0.01',
            'transfer_date'      => 'required|date',
            'source_location_id' => 'required|exists:locations,id',
        ]);

        $sub     = '(SELECT COALESCE(SUM(itd.weight), 0) FROM idm_transfer_details itd
                    INNER JOIN idm_transfers it ON it.id = itd.idm_transfer_id AND it.deleted_at IS NULL
                    WHERE itd.idm_detail_id = idm_details.id AND itd.deleted_at IS NULL)';
        $dbItems = \App\Models\IdmDetail::whereIn('idm_details.id', array_column($request->items, 'id'))
            ->selectRaw("idm_details.*, (idm_details.weight - {$sub}) AS remaining_weight")
            ->get()->keyBy('id');

        foreach ($request->items as $submitted) {
            $dbItem = $dbItems->get($submitted['id']);
            $remaining = $dbItem ? (float) $dbItem->remaining_weight : 0;
            if (!$dbItem || (float) $submitted['weight'] > $remaining + 0.001) {
                return redirect()->back()
                    ->with('error', 'Berat yang dimasukkan melebihi sisa berat tersedia untuk item ' . ($dbItem->grade_idm_name ?? '') . ' (sisa: ' . number_format($remaining, 2) . ' g).');
            }
        }

        $itemsData = collect($request->items)->map(fn ($submitted) => [
            'id'             => $submitted['id'],
            'weight'         => (float) $submitted['weight'],
            'grade_idm_name' => $dbItems->get($submitted['id'])->grade_idm_name,
        ]);

        try {
            $this->transferIdmService->storeTransfer([
                'transfer_date'      => $request->transfer_date,
                'source_location_id' => $request->source_location_id,
                'items'              => $itemsData,
                'notes'              => $request->notes,
            ]);

            return redirect()->route('barang.keluar.transfer-idm.index')->with('success', 'Transfer IDM berhasil dibuat.');
        } catch (\Exception $e) {
            return redirect()->route('barang.keluar.transfer-idm.create')->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $this->transferIdmService->deleteTransfer($id);
            return redirect()->route('barang.keluar.transfer-idm.index')
                ->with('success', 'Transfer IDM berhasil dihapus dan stok dikembalikan.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('TransferIdmController destroy error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus transfer.');
        }
    }

    public function show($id)
    {
        $transfer = $this->transferIdmService->getTransferById($id);
        return view('admin.transfer-idm.show', compact('transfer'));
    }


    public function export(Request $request)
    {
        try {
            $filters = [
                'start_date' => $request->get('start_date'),
                'end_date'   => $request->get('end_date'),
                'search'     => $request->get('search'),
            ];

            $fileName = 'transfer_idm_' . date('Y-m-d') . '.xlsx';
            return Excel::download(new TransferIdmExport($filters), $fileName);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('TransferIdmController export error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat export data.');
        }
    }
}
