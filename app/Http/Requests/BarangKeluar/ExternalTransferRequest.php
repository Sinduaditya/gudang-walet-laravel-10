<?php

namespace App\Http\Requests\BarangKeluar;

use Illuminate\Foundation\Http\FormRequest;

class ExternalTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grade_company_id' => 'required|exists:sorting_results,id',
            'from_location_id' => 'required|exists:locations,id',
            'to_location_id' => 'required|exists:locations,id',
            'weight_grams' => 'required|numeric|min:0.01',
            'susut_grams' => 'nullable|numeric|min:0',
            // 'external_source' => 'required|string|max:255',
            'transfer_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'grade_company_id.required' => 'Grade harus dipilih',
            'grade_company_id.exists' => 'Grade tidak valid',
            'from_location_id.required' => 'Lokasi asal harus dipilih',
            'from_location_id.exists' => 'Lokasi asal tidak valid',
            'to_location_id.required' => 'Lokasi tujuan harus dipilih',
            'weight_grams.required' => 'Berat wajib diisi',
            'weight_grams.min' => 'Berat minimal 0.01 gram',
            // 'external_source.required' => 'Sumber eksternal harus diisi',
            // 'external_source.max' => 'Sumber eksternal maksimal 255 karakter',
        ];
    }

    public function attributes(): array
    {
        return [
            'grade_company_id' => 'grade',
            'from_location_id' => 'lokasi asal',
            'to_location_id' => 'lokasi tujuan',
            'weight_grams' => 'berat',
            // 'external_source' => 'sumber eksternal',
            'transfer_date' => 'tanggal transfer',
            'notes' => 'catatan',
        ];
    }
}
