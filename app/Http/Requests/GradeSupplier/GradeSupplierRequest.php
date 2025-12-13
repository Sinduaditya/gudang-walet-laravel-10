<?php

namespace App\Http\Requests\GradeSupplier;

use Illuminate\Foundation\Http\FormRequest;

class GradeSupplierRequest extends FormRequest
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

    public function messages(): array
    {
        return [
            'name.required' => 'Nama grade supplier wajib diisi.',
            'name.string' => 'Nama grade supplier harus berupa teks.',
            'name.max' => 'Nama grade supplier maksimal 255 karakter.',
            'image_url.image' => 'File harus berupa gambar.',
            'image_url.mimes' => 'Gambar harus berformat jpg, jpeg, atau png.',
            'image_url.max' => 'Ukuran gambar maksimal 2MB.',
            'description.string' => 'Deskripsi harus berupa teks.',
        ];
    }
}
