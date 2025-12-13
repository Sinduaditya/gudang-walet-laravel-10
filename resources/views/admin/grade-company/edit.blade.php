@extends('layouts.app')

@section('title', 'Edit Grade Company')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center mb-2">
                <a href="{{ route('grade-company.index') }}" class="text-gray-600 hover:text-gray-900 mr-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-semibold text-gray-800">Edit Grade Company</h1>
            </div>
            <p class="text-sm text-gray-600 ml-7">Perbarui data grade company di bawah ini</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <form action="{{ route('grade-company.update', $gradeCompany->id) }}" method="POST" enctype="multipart/form-data" class="p-6">
                @csrf
                @method('PUT')

                <!-- Nama -->
                <div class="mb-6">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Grade Perusahaan <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           id="name"
                           value="{{ old('name', $gradeCompany->name) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                           placeholder="Masukkan nama perusahaan"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Gambar -->
                <div class="mb-6">
                    <label for="image_url" class="block text-sm font-medium text-gray-700 mb-2">
                        Gambar Grade Perusahaan
                    </label>

                    @if($gradeCompany->image_url)
                        <div class="mb-3">
                            <img src="{{ asset('storage/' . $gradeCompany->image_url) }}" alt="Gambar Perusahaan" class="h-24 rounded shadow">
                            <p class="text-xs text-gray-500 mt-1">Gambar saat ini</p>
                        </div>
                    @endif

                    <input type="file"
                           name="image_url"
                           id="image_url"
                           accept=".jpg,.jpeg,.png"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('image_url') border-red-500 @enderror">
                    @error('image_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Kosongkan jika tidak ingin mengganti gambar. Format: JPG, JPEG, PNG (maks. 2MB)</p>
                </div>

                <!-- Deskripsi -->
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Deskripsi
                    </label>
                    <textarea name="description"
                              id="description"
                              rows="5"
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                              placeholder="Masukkan deskripsi perusahaan (opsional)">{{ old('description', $gradeCompany->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tombol -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('grade-company.index') }}"
                       class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-medium transition duration-200">
                        Batal
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-sm font-medium transition duration-200 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update
                    </button>
                </div>
            </form>
        </div>

        <!-- Info -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-blue-900 mb-1">Informasi</h3>
                    <p class="text-sm text-blue-700">
                        Pastikan perubahan sudah sesuai sebelum disimpan.
                        Gambar lama akan tetap digunakan jika Anda tidak mengunggah yang baru.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Fokus otomatis
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('name')?.focus();
    });

    // Konfirmasi update
    document.querySelector('form')?.addEventListener('submit', function(e) {
        if (!confirm('Apakah Anda yakin ingin memperbarui data ini?')) {
            e.preventDefault();
            return false;
        }
    });
</script>
@endsection
