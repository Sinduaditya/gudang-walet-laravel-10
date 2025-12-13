<?php

namespace App\Http\Requests\GradingGoods;

use Illuminate\Foundation\Http\FormRequest;

class Step1Request extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'grading_date' => 'required|date',
            'receipt_item_id' => 'required|exists:receipt_items,id',
        ];
    }

    public function messages()
    {
        return [
            'grading_date.required' => 'Tanggal grading wajib diisi.',
            'grading_date.date' => 'Tanggal grading harus berupa tanggal yang valid.',
            'receipt_item_id.required' => 'Item penerimaan wajib dipilih.',
            'receipt_item_id.exists' => 'Item penerimaan yang dipilih tidak valid.',
        ];
    }
}
