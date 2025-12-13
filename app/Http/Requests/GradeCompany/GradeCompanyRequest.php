<?php

namespace App\Http\Requests\GradeCompany;

use Illuminate\Foundation\Http\FormRequest;

class GradeCompanyRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'image_url' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'description' => 'nullable|string',
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lokasi wajib diisi.',
            'name.string' => 'Nama lokasi harus berupa teks.',
            'name.max' => 'Nama lokasi maksimal 255 karakter.',
            'image_url.image' => 'URL gambar harus berupa file gambar.',
            'image_url.mimes' => 'URL gambar harus berformat jpg, jpeg, atau png.',
            'image_url.max' => 'Ukuran gambar maksimal 2MB.',
            'description.string' => 'Deskripsi harus berupa teks.',
        ];
    }
}
