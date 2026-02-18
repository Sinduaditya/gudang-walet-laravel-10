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
use App\Models\PurchaseReceipt;
use App\Models\ReceiptItem;
use App\Models\SortingResult;
use App\Models\IdmManagement;
use App\Models\IdmDetail;

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
            case 'purchase_receipts':
                $query = PurchaseReceipt::onlyTrashed()->with([
                    'deletedBy',
                    'supplier' => function ($query) {
                        $query->withTrashed();
                    }
                ]);
                if ($search) {
                    $query->where('id', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });

                }
                $data = $query->latest('deleted_at')->paginate(10);
                break;

            case 'stock_transfers':
                $query = StockTransfer::onlyTrashed()->with(['deletedBy', 'gradeCompany', 'fromLocation', 'toLocation']);
                if ($search) {
                    $query->where('notes', 'like', "%{$search}%");
                }
                $data = $query->latest('deleted_at')->paginate(10);
                break;

            case 'receipt_items':
                $query = ReceiptItem::onlyTrashed()->with([
                    'deletedBy',
                    'gradeSupplier' => function ($query) {
                        $query->withTrashed();
                    }
                ]);
                if ($search) {
                    $query->where('id', 'like', "%{$search}%")
                        ->orWhereHas('gradeSupplier', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });

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

            case 'sorting_results':
                $query = SortingResult::onlyTrashed()->with([
                    'deletedBy',
                    'gradeCompany' => function ($query) {
                        $query->withTrashed();
                    }
                ]);
                if ($search) {
                    $query->where('id', 'like', "%{$search}%")
                        ->orWhereHas('gradeCompany', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
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

            case 'idm_managements':
                $query = IdmManagement::onlyTrashed()->with([
                    'deletedBy',
                    'gradeCompany' => function ($query) {
                        $query->withTrashed();
                    },
                    'supplier' => function ($query) {
                        $query->withTrashed();
                    }
                ]);
                if ($search) {
                    $query->where('id', 'like', "%{$search}%");
                }
                $data = $query->latest('deleted_at')->paginate(10);
                break;

            case 'inventory_transactions':
                $query = InventoryTransaction::onlyTrashed()->with(['deletedBy', 'gradeCompany', 'location', 'supplier']);
                if ($search) {
                    $query->where('transaction_type', 'like', "%{$search}%");
                }
                $data = $query->latest('deleted_at')->paginate(10);
                break;

            case 'idm_details':
                $query = IdmDetail::onlyTrashed()->with('deletedBy');
                if ($search) {
                    $query->where('grade_idm_name', 'like', "%{$search}%");
                }
                $data = $query->latest('deleted_at')->paginate(10);
                break;

            default:
                abort(404);
        }

        return view('admin.system-log.index', compact('data', 'type', 'search'));
    }
}
