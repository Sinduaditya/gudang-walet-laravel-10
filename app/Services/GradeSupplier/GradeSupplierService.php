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
        // Perbaikan: gunakan key yang sesuai dari request (bisa 'image_url' atau 'image')
        // controller menggunakan $request->validated() yang mengembalikan array

        $image = $data['image_url'] ?? null;
        if ($image instanceof \Illuminate\Http\UploadedFile) {
            $data['image_url'] = $image->store('grade-suppliers', 'public');
        } elseif (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $data['image_url'] = $data['image']->store('grade-suppliers', 'public');
        }

        return GradeSupplier::create($data);
    }

    public function update($id, array $data)
    {
        $grade = GradeSupplier::findOrFail($id);

        $image = $data['image_url'] ?? ($data['image'] ?? null);

        if ($image instanceof \Illuminate\Http\UploadedFile) {
            // Hapus gambar lama
            if ($grade->image_url && Storage::disk('public')->exists($grade->image_url)) {
                Storage::disk('public')->delete($grade->image_url);
            }

            // Simpan gambar baru
            $data['image_url'] = $image->store('grade-suppliers', 'public');
        } else {
            // Jika tidak ada gambar baru, jangan overwrite field image_url dengan null/string path
            // Hapus dari array update agar tidak menimpa data lama dengan null jika tidak dikirim
            if (!array_key_exists('image_url', $data) && !array_key_exists('image', $data)) {
                // do nothing
            } else {
                // jika dikirim tapi null/bukan file (kecuali kita mau fitur hapus gambar), sebaiknya unset
                unset($data['image_url']);
            }
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
