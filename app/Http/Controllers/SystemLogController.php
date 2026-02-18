<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\GradeSupplier;
use App\Models\Location;
use App\Models\GradeCompany;
use App\Models\ParentGradeCompany;
use App\Models\User;
use App\Models\SortMaterial;
use App\Models\StockTransfer;
use App\Models\IdmTransfer;
use App\Models\IdmTransferDetail;
use App\Models\InventoryTransaction;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type', 'suppliers');
        $search = $request->get('search');

        $data = [];

        switch ($type) {
            case 'suppliers':
                $query = Supplier::onlyTrashed()->with('deletedBy');
                if ($search) {
                    $query->where('name', 'like', "%{$search}%");
                }
                $data = $query->latest('deleted_at')->paginate(10);
                break;

            case 'grade_suppliers':
                $query = GradeSupplier::onlyTrashed()->with('deletedBy');
                if ($search) {
                    $query->where('name', 'like', "%{$search}%");
                }
                $data = $query->latest('deleted_at')->paginate(10);
                break;

            case 'locations':
                $query = Location::onlyTrashed()->with('deletedBy');
                if ($search) {
                    $query->where('name', 'like', "%{$search}%");
                }
                $data = $query->latest('deleted_at')->paginate(10);
                break;

            case 'grade_companies':
                $query = GradeCompany::onlyTrashed()->with('deletedBy');
                if ($search) {
                    $query->where('name', 'like', "%{$search}%");
                }
                $data = $query->latest('deleted_at')->paginate(10);
                break;

            case 'parent_grade_companies':
                $query = ParentGradeCompany::onlyTrashed()->with('deletedBy');
                if ($search) {
                    $query->where('name', 'like', "%{$search}%");
                }
                $data = $query->latest('deleted_at')->paginate(10);
                break;

            case 'sort_materials':
                $query = SortMaterial::onlyTrashed()->with('deletedBy');
                if ($search) {
                    $query->where('description', 'like', "%{$search}%");
                }
                $data = $query->latest('deleted_at')->paginate(10);
                break;

            case 'stock_transfers':
                $query = StockTransfer::onlyTrashed()->with('deletedBy');
                if ($search) {
                    $query->where('notes', 'like', "%{$search}%");
                }
                $data = $query->latest('deleted_at')->paginate(10);
                break;

            case 'idm_transfers':
                $query = IdmTransfer::onlyTrashed()->with('deletedBy');
                if ($search) {
                    $query->where('transfer_code', 'like', "%{$search}%");
                }
                $data = $query->latest('deleted_at')->paginate(10);
                break;

            case 'idm_transfer_details':
                $query = IdmTransferDetail::onlyTrashed()->with('deletedBy');
                if ($search) {
                    $query->where('item_name', 'like', "%{$search}%");
                }
                $data = $query->latest('deleted_at')->paginate(10);
                break;

            case 'inventory_transactions':
                $query = InventoryTransaction::onlyTrashed()->with('deletedBy');
                if ($search) {
                    $query->where('transaction_type', 'like', "%{$search}%");
                }
                $data = $query->latest('deleted_at')->paginate(10);
                break;

            default:
                abort(404);
        }

        return view('admin.system-log.index', compact('data', 'type', 'search'));
    }
}
