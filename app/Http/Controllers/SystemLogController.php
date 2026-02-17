<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\GradeSupplier;
use App\Models\Location;
use App\Models\GradeCompany;
use App\Models\ParentGradeCompany;
use App\Models\User;

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


            default:
                abort(404);
        }

        return view('admin.system-log.index', compact('data', 'type', 'search'));
    }
}
