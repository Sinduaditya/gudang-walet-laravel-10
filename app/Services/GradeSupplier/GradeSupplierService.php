<?php

namespace App\Services\GradeSupplier;

use App\Models\GradeSupplier;
use Illuminate\Support\Facades\Storage;

class GradeSupplierService
{
    public function getAll($search = null)
    {
        $query = GradeSupplier::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate(10)->withQueryString();
    }

    public function getById($id)
    {
        return GradeSupplier::findOrFail($id);
    }

    public function create(array $data)
    {
        if (isset($data['image_url'])) {
            $data['image_url'] = $data['image_url']->store('grade-suppliers', 'public');
        }

        return GradeSupplier::create($data);
    }

    public function update($id, array $data)
    {
        $grade = GradeSupplier::findOrFail($id);

        if (isset($data['image_url'])) {
            // Hapus gambar lama
            if ($grade->image_url && Storage::disk('public')->exists($grade->image_url)) {
                Storage::disk('public')->delete($grade->image_url);
            }

            // Simpan gambar baru
            $data['image_url'] = $data['image_url']->store('grade-suppliers', 'public');
        }

        $grade->update($data);

        return $grade;
    }

    public function delete($id)
    {
        $grade = GradeSupplier::findOrFail($id);

        if ($grade->image_url && Storage::disk('public')->exists($grade->image_url)) {
            Storage::disk('public')->delete($grade->image_url);
        }

        return $grade->delete();
    }
}
