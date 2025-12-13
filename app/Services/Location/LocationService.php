<?php

namespace App\Services\Location;

use App\Exports\LocationExport;
use App\Models\Location;
use Maatwebsite\Excel\Facades\Excel;

class LocationService
{
    /**
     * Get all locations.
     */
    public function getAll(?string $search = null)
    {
        $query = Location::query();

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        return $query->latest()->paginate(10)->withQueryString(); 
    }

    /**
     * Export Location to Excel
     * 
     */
    public function exportToExcel(){
        return Excel::download(new LocationExport, 'locations-' . date('Y-m-d') . '.xlsx');
    }



    /**
     * Get a single location by ID.
     */
    public function getById(int $id)
    {
        return Location::findOrFail($id);
    }

    /**
     * Create a new location.
     */
    public function create(array $data)
    {
        return Location::create($data);
    }

    /**
     * Update an existing location.
     */
    public function update(int $id, array $data)
    {
        $location = $this->getById($id);
        $location->update($data);

        return $location;
    }

    /**
     * Delete a location.
     */
    public function delete(int $id)
    {
        $location = $this->getById($id);
        $location->delete();

        return true;
    }
}