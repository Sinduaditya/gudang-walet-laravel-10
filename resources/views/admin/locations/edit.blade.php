@extends('layouts.app')

@section('title', 'Edit Lokasi')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center mb-2">
                <a href="{{ route('locations.index') }}" class="text-gray-600 hover:text-gray-900 mr-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-semibold text-gray-800">Edit Lokasi</h1>
            </div>
            <p class="text-sm text-gray-600 ml-7">Perbarui informasi lokasi</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <form action="{{ route('locations.update', $location->id) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')

                <!-- Nama Lokasi -->
                <div class="mb-6">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Lokasi <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name', $location->name) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                           placeholder="Masukkan nama lokasi"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Deskripsi -->
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Deskripsi
                    </label>
                    <textarea name="description" 
                              id="description" 
                              rows="5"
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror"
                              placeholder="Masukkan deskripsi lokasi (opsional)">{{ old('description', $location->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Deskripsi dapat membantu mengidentifikasi lokasi dengan lebih detail</p>
                </div>

                <!-- Info Update -->
                @if($location->updated_at)
                <div class="mb-6 bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Terakhir diperbarui: {{ $location->updated_at->format('d M Y, H:i') }}</span>
                    </div>
                </div>
                @endif

                <!-- Buttons -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <button type="button" 
                            onclick="confirmDelete()"
                            class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-md text-sm font-medium transition duration-200 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Hapus Lokasi
                    </button>

                    <div class="flex items-center space-x-3">
                        <a href="{{ route('locations.index') }}" 
                           class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-medium transition duration-200">
                            Batal
                        </a>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-sm font-medium transition duration-200 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Info Card -->
        <div class="mt-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-amber-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-amber-900 mb-1">Perhatian</h3>
                    <p class="text-sm text-amber-700">
                        Perubahan yang Anda lakukan akan mempengaruhi semua data terkait dengan lokasi ini. 
                        Pastikan untuk memeriksa kembali sebelum menyimpan.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-4">Hapus Lokasi</h3>
            <p class="text-sm text-gray-500 mt-2">
                Apakah Anda yakin ingin menghapus lokasi <span class="font-semibold text-gray-900">{{ $location->name }}</span>?
            </p>
            <p class="text-sm text-red-600 mt-1">Tindakan ini tidak dapat dibatalkan!</p>
            <form id="deleteForm" action="{{ route('locations.destroy', $location->id) }}" method="POST" class="mt-6">
                @csrf
                @method('DELETE')
                <div class="flex gap-3 justify-center">
                    <button type="button" 
                            onclick="closeDeleteModal()" 
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-medium transition duration-200">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md text-sm font-medium transition duration-200">
                        Ya, Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Auto-focus pada input pertama
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('name')?.focus();
    });

    // Form validation sebelum submit
    document.querySelector('form[method="POST"]')?.addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        
        if (!name) {
            e.preventDefault();
            alert('Nama lokasi wajib diisi!');
            document.getElementById('name').focus();
            return false;
        }

        // Konfirmasi sebelum submit
        if (!confirm('Apakah Anda yakin ingin menyimpan perubahan ini?')) {
            e.preventDefault();
            return false;
        }
    });

    // Delete modal functions
    function confirmDelete() {
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('deleteModal')?.addEventListener('click', function(event) {
        if (event.target === this) {
            closeDeleteModal();
        }
    });

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    });
</script>
@endsection