@extends('layouts.app')

@section('title', 'Edit Supplier')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center mb-2">
                <a href="{{ route('suppliers.index') }}" class="text-gray-600 hover:text-gray-900 mr-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-semibold text-gray-800">Edit Supplier</h1>
            </div>
            <p class="text-sm text-gray-600 ml-7">Edit data supplier {{ $supplier->name }}</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <form action="{{ route('suppliers.update', $supplier) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')

                <!-- Nama Supplier -->
                <div class="mb-6">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Supplier <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name', $supplier->name) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                           placeholder="Masukkan nama supplier"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nomor Telepon -->
                <div class="mb-6">
                    <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-2">
                        Nomor Telepon
                    </label>
                    <input type="tel" 
                           name="contact_person" 
                           id="contact_person" 
                           value="{{ old('contact_person', $supplier->contact_person) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('contact_person') border-red-500 @enderror"
                           placeholder="Masukkan nomor telepon">
                    @error('contact_person')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Alamat -->
                <div class="mb-6">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                        Alamat
                    </label>
                    <textarea name="address" 
                              id="address" 
                              rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('address') border-red-500 @enderror"
                              placeholder="Masukkan alamat lengkap supplier">{{ old('address', $supplier->address) }}</textarea>
                    @error('address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('suppliers.index') }}" 
                       class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-medium transition duration-200">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-sm font-medium transition duration-200 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update Supplier
                    </button>
                </div>
            </form>
        </div>

        <!-- Info Card -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-blue-900 mb-1">Informasi</h3>
                    <p class="text-sm text-blue-700">
                        Pastikan data supplier yang Anda edit akurat dan lengkap. 
                        Field yang bertanda <span class="text-red-600">*</span> wajib diisi.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-focus pada input pertama
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('name')?.focus();
    });

    // Form validation sebelum submit
    document.querySelector('form')?.addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        
        if (!name) {
            e.preventDefault();
            alert('Nama supplier wajib diisi!');
            document.getElementById('name').focus();
            return false;
        }

        // Konfirmasi sebelum submit
        if (!confirm('Apakah Anda yakin ingin mengupdate supplier ini?')) {
            e.preventDefault();
            return false;
        }
    });
</script>
@endsection