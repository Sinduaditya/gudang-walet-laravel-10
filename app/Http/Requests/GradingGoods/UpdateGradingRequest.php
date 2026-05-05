<?php

namespace App\Http\Requests\GradingGoods;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'grades'                      => 'required|array|min:1',
            'grades.*.grading_date'       => 'required|date',
            'grades.*.grade_company_name' => 'required|string|max:255',
            'grades.*.quantity'           => 'required|numeric|min:0',
            'grades.*.weight_grams'       => 'required|numeric|min:0',
            'grades.*.notes'             => 'nullable|string|max:1000',
            'grades.*.outgoing_type'     => 'nullable|in:penjualan_langsung,internal,external',
            'grades.*.category_grade'     => 'nullable|in:IDM A,IDM B',
            'global_notes'                 => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'grades.required'                       => 'Minimal satu grade harus diisi.',
            'grades.*.grading_date.required'        => 'Tanggal grading harus diisi.',
            'grades.*.grade_company_name.required' => 'Nama grade company harus diisi.',
            'grades.*.quantity.required'            => 'Jumlah item harus diisi.',
            'grades.*.weight_grams.required'        => 'Berat hasil harus diisi.',
        ];
    }
}
