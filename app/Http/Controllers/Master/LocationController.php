<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Location\LocationRequest;
use App\Services\Location\LocationService;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    protected $locationService;

    /**
     * Constructor.
     */
    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $locations = $this->locationService->getAll($search);
        return view('admin.locations.index', compact('locations', 'search'));
    }

    /**
     * Export locations to Excel.
     */
    public function export()
    {
        try {
            return $this->locationService->exportToExcel(); 
        } catch (\Throwable $th) {
            return back()->with('error', 'Gagal mengekspor data lokasi: ' . $th->getMessage());
        }       
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.locations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LocationRequest $request)
    {
        $this->locationService->create($request->validated());
        return redirect()->route('locations.index')->with('success', 'Lokasi berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        $location = $this->locationService->getById($id);
        return view('admin.locations.edit', compact('location'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LocationRequest $request, int $id)
    {
        $this->locationService->update($id, $request->validated());
        return redirect()->route('locations.index')->with('success', 'Lokasi berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $this->locationService->delete($id);
        return redirect()->route('locations.index')->with('success', 'Lokasi berhasil dihapus.');
    }
}