<?php

namespace App\Http\Requests\BarangKeluar;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'grade_company_id' => 'required|exists:sorting_results,id',
            'from_location_id' => 'required|exists:locations,id',
            'to_location_id' => 'required|exists:locations,id|different:from_location_id',
            'weight_grams' => 'required|numeric|min:0.01',
             'susut_grams' => 'nullable|numeric|min:0',
            'transfer_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'grade_company_id.required' => 'Grade harus dipilih',
            'grade_company_id.exists' => 'Grade tidak valid',
            'from_location_id.required' => 'Lokasi asal harus dipilih',
            'from_location_id.exists' => 'Lokasi asal tidak valid',
            'to_location_id.required' => 'Lokasi tujuan harus dipilih',
            'to_location_id.exists' => 'Lokasi tujuan tidak valid',
            'to_location_id.different' => 'Lokasi tujuan harus berbeda dari lokasi asal',
            'weight_grams.required' => 'Berat wajib diisi',
            'weight_grams.numeric' => 'Berat harus berupa angka',
            'weight_grams.min' => 'Berat minimal 0.01 gram',
            'transfer_date.date' => 'Format tanggal tidak valid',
            'notes.max' => 'Catatan maksimal 500 karakter',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'grade_company_id' => 'grade',
            'from_location_id' => 'lokasi asal',
            'to_location_id' => 'lokasi tujuan',
            'weight_grams' => 'berat',
            'transfer_date' => 'tanggal transfer',
            'notes' => 'catatan',
        ];
    }
}
