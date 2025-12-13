<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
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
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama supplier wajib diisi.',
            'name.string' => 'Nama supplier harus berupa teks.',
            'name.max' => 'Nama supplier maksimal 255 karakter.',
            'address.string' => 'Alamat harus berupa teks.',
            'contact_person.string' => 'Nama kontak harus berupa teks.',
            'contact_person.max' => 'Nama kontak maksimal 255 karakter.',
        ];
    }
}
