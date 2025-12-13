<?php

namespace App\Http\Requests\GradingGoods;

use Illuminate\Foundation\Http\FormRequest;

class Step2Request extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'grades' => 'required|array|min:1',
            'grades.*.grade_company_name' => 'required|string|max:255',
            'grades.*.quantity' => 'required|numeric|min:0', // ✅ numeric instead of integer
            'grades.*.weight_grams' => 'required|numeric|min:0', // ✅ numeric instead of integer
            'grades.*.notes' => 'nullable|string|max:1000',
            'grades.*.outgoing_type' => 'nullable|in:penjualan_langsung,internal,external', // ✅ Ditambahkan
            'grades.*.category_grade' => 'nullable|in:IDM A,IDM B', // ✅ Ditambahkan
            'global_notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'grades.required' => 'Setidaknya harus ada 1 grade hasil.',
            'grades.*.grade_company_name.required' => 'Nama grade company wajib diisi.',
            'grades.*.quantity.required' => 'Jumlah item wajib diisi.',
            'grades.*.quantity.numeric' => 'Jumlah item harus berupa angka.',
            'grades.*.quantity.min' => 'Jumlah item tidak boleh negatif.',
            'grades.*.weight_grams.required' => 'Berat hasil wajib diisi.',
            'grades.*.weight_grams.numeric' => 'Berat hasil harus berupa angka.',
            'grades.*.weight_grams.min' => 'Berat hasil tidak boleh negatif.',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('grades')) {
            $grades = $this->input('grades');
            foreach ($grades as $index => $grade) {
                // ✅ Pastikan quantity dan weight_grams adalah string yang bisa di-cast ke integer
                if (isset($grade['quantity'])) {
                    $grades[$index]['quantity'] = trim($grade['quantity']);
                }
                if (isset($grade['weight_grams'])) {
                    $grades[$index]['weight_grams'] = trim($grade['weight_grams']);
                }
            }
            $this->merge(['grades' => $grades]);
        }
    }
}