<?php

namespace App\Http\Requests\BarangKeluar;

use Illuminate\Foundation\Http\FormRequest;

class SellRequest extends FormRequest
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
            'location_id' => 'required|exists:locations,id',
            'weight_grams' => 'required|numeric|min:0.01',
            // 'price' => 'required|numeric|min:0',
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
            'location_id.required' => 'Lokasi harus dipilih',
            'location_id.exists' => 'Lokasi tidak valid',
            'weight_grams.required' => 'Berat wajib diisi',
            'weight_grams.numeric' => 'Berat harus berupa angka',
            'weight_grams.min' => 'Berat minimal 0.01 gram',
            // 'price.required' => 'Harga wajib diisi',
            // 'price.numeric' => 'Harga harus berupa angka',
            // 'price.min' => 'Harga tidak boleh negatif',
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
            'location_id' => 'lokasi',
            'weight_grams' => 'berat',
            // 'price' => 'harga',
        ];
    }
}
