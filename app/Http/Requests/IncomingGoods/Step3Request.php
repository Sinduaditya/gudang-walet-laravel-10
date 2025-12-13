<?php

namespace App\Http\Requests\IncomingGoods;

use Illuminate\Foundation\Http\FormRequest;

class Step3Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gradeIds = session('step1_data.grade_ids', []);
        $rules = [
            'berat_akhir' => 'required|array',
        ];

        foreach ($gradeIds as $gradeId) {
            $rules["berat_akhir.{$gradeId}"] = 'required|numeric|min:0';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'berat_akhir.*.required' => 'Timbangan gudang harus diisi',
            'berat_akhir.*.numeric' => 'Timbangan gudang harus berupa angka',
            'berat_akhir.*.min' => 'Timbangan gudang tidak boleh negatif',
        ];
    }
}