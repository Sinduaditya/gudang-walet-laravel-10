<?php

namespace App\Http\Requests\GradingGoods;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradingRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Asumsi user yang login boleh mengedit
    }

    public function rules()
    {
        return [
            'grading_date' => 'required|date',
            'receipt_item_id' => 'required|exists:receipt_items,id',
            'quantity' => 'required|integer|min:1',
            'grade_company_name' => 'required|string|max:255',
            'weight_grams' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'grading_date.required' => 'Tanggal grading wajib diisi.',
            'receipt_item_id.required' => 'Nama grade supplier (item) wajib dipilih.',
            'receipt_item_id.exists' => 'Item yang dipilih tidak valid.',
            'quantity.required' => 'Kuantitas wajib diisi.',
            'grade_company_name.required' => 'Nama grade company wajib diisi.',
            'weight_grams.required' => 'Berat setelah grading wajib diisi.',
        ];
    }
}
