<?php

namespace App\Http\Requests\IncomingGoods;

use Illuminate\Foundation\Http\FormRequest;

class Step1Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receipt_date' => 'required|date',
            'unloading_date' => 'required|date|after_or_equal:receipt_date',
            'supplier_id' => 'required|exists:suppliers,id',
            'grade_ids' => 'required|array|min:1',
            'grade_ids.*' => 'exists:grades_supplier,id',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'receipt_date.required' => 'Tanggal kedatangan harus diisi',
            'unloading_date.required' => 'Tanggal bongkar harus diisi',
            'unloading_date.after_or_equal' => 'Tanggal bongkar tidak boleh lebih awal dari tanggal kedatangan',
            'supplier_id.required' => 'Supplier harus dipilih',
            'supplier_id.exists' => 'Supplier tidak valid',
            'grade_ids.required' => 'Pilih minimal satu jenis grade',
            'grade_ids.min' => 'Pilih minimal satu jenis grade',
            'grade_ids.*.exists' => 'Grade yang dipilih tidak valid',
        ];
    }
}