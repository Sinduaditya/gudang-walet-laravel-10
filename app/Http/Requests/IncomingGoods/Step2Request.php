<?php

namespace App\Http\Requests\IncomingGoods;

use Illuminate\Foundation\Http\FormRequest;

class Step2Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gradeIds = session('step1_data.grade_ids', []);
        $rules = [
            'berat_awal' => 'required|array',
            'kadar_air' => 'nullable|array',
        ];

        foreach ($gradeIds as $gradeId) {
            $rules["berat_awal.{$gradeId}"] = 'required|numeric|min:0';
            $rules["kadar_air.{$gradeId}"] = 'nullable|numeric|min:0|max:100';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'berat_awal.*.required' => 'Berat nota harus diisi',
            'berat_awal.*.numeric' => 'Berat nota harus berupa angka',
            'berat_awal.*.min' => 'Berat nota tidak boleh negatif',
            'kadar_air.*.numeric' => 'Kadar air harus berupa angka',
            'kadar_air.*.min' => 'Kadar air tidak boleh negatif',
            'kadar_air.*.max' => 'Kadar air maksimal 100%',
        ];
    }
}