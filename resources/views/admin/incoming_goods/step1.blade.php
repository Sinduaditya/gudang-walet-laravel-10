@extends('layouts.app')

@section('title', 'Input Barang Masuk - Step 1')

@section('content')
<div class="min-h-screen bg-gray-50 py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
            <h1 class="text-2xl font-semibold text-gray-900">Input Barang Masuk</h1>
            <p class="mt-1 text-sm text-gray-600">Lengkapi informasi penerimaan barang dari supplier</p>
            </div>

            <a href="{{ route('incoming-goods.index') }}" 
               class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Daftar Barang Masuk
            </a>
        </div>

        <!-- Progress Steps -->
        <div class="mb-8 bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center justify-between max-w-3xl mx-auto">
                <div class="flex flex-col items-center flex-1">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-500  text-white font-semibold text-sm shadow-sm">1</div>
                    <span class="mt-2 text-xs sm:text-sm font-medium text-blue-600">Pilih Supplier</span>
                </div>
                <div class="flex-1 h-0.5 bg-gray-200 mx-2 sm:mx-4 -mt-6"></div>
                <div class="flex flex-col items-center flex-1">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-400 font-semibold text-sm">2</div>
                    <span class="mt-2 text-xs sm:text-sm font-medium text-gray-400">Berat Nota</span>
                </div>
                <div class="flex-1 h-0.5 bg-gray-200 mx-2 sm:mx-4 -mt-6"></div>
                <div class="flex flex-col items-center flex-1">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-400 font-semibold text-sm">3</div>
                    <span class="mt-2 text-xs sm:text-sm font-medium text-gray-400">Berat Gudang</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <!-- Form Section -->
            <div class="lg:col-span-2 space-y-6">
                <form action="{{ route('incoming-goods.store-step1') }}" method="POST" id="mainForm">
                    @csrf
                    
                    <!-- Data Penerimaan Card -->
                    <div class="bg-white rounded-lg shadow-sm border">
                        <div class="px-6 py-4 border-b">
                            <h2 class="text-base font-semibold text-gray-900">Data Penerimaan</h2>
                        </div>
                        <div class="p-6 space-y-5">
                            <!-- Tanggal Kedatangan -->
                            <div>
                                <label for="receipt_date" class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Tanggal Kedatangan <span class="text-red-500">*</span>
                                </label>
                                <input type="date" 
                                       name="receipt_date" 
                                       id="receipt_date"
                                       value="{{ old('receipt_date', date('Y-m-d')) }}"
                                       required
                                       class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all">
                                @error('receipt_date')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Nama Supplier -->
                            <div>
                                <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Nama Supplier <span class="text-red-500">*</span>
                                </label>
                                <select name="supplier_id" 
                                        id="supplier_id"
                                        required
                                        class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all">
                                    <option value="">Pilih supplier...</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" 
                                                {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_id')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Tanggal Bongkar -->
                            <div>
                                <label for="unloading_date" class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Tanggal Bongkar <span class="text-red-500">*</span>
                                </label>
                                <input type="date" 
                                       name="unloading_date" 
                                       id="unloading_date"
                                       value="{{ old('unloading_date', date('Y-m-d')) }}"
                                       required
                                       class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all">
                                @error('unloading_date')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Notes -->
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Catatan
                                    <span class="text-gray-400 font-normal">(Opsional)</span>
                                </label>
                                <textarea name="notes" 
                                          id="notes"
                                          rows="3"
                                          class="w-full px-3 py-2.5 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all resize-none"
                                          placeholder="Tambahkan catatan jika diperlukan...">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Form Actions -->
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" 
                       class="flex-1 inline-flex items-center justify-center px-4 py-2.5 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Batal
                    </a>
                    <button type="submit"
                            form="mainForm"
                            class="flex-1 inline-flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-500 hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm transition-all">
                        Lanjut ke Tahap 2
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Grade Selection Section -->
            <div class="lg:col-span-3 bg-white rounded-lg shadow-sm border h-fit">
                <div class="px-6 py-4 border-b sticky top-0 bg-white z-10 rounded-t-lg">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">
                                Pilih Grade Supplier <span class="text-red-500">*</span>
                            </h2>
                            <p class="text-sm text-gray-500 mt-0.5">Pilih satu atau lebih grade yang akan diinput</p>
                        </div>
                        <!-- Selected Counter -->
                        <div class="hidden ml-4" id="selectedCounter">
                            <div class="inline-flex items-center px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-lg">
                                <span class="text-sm font-semibold text-blue-700" id="selectedCount">0</span>
                                <span class="text-sm text-blue-600 ml-1">dipilih</span>
                            </div>
                        </div>
                    </div>

                    @error('grade_ids')
                        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        </div>
                    @enderror

                    <!-- Search Box -->
                    <div class="mt-4">
                        <div class="relative">
                            <input type="text" 
                                   id="gradeSearch"
                                   placeholder="Cari grade supplier..."
                                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Grade Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 max-h-[600px] overflow-y-auto pr-2 custom-scrollbar" id="gradeGrid">
                        @foreach($gradeSuppliers as $grade)
                        <label class="grade-item relative cursor-pointer group" data-name="{{ strtolower($grade->name) }}">
                            <input type="checkbox" 
                                   name="grade_ids[]" 
                                   value="{{ $grade->id }}"
                                   form="mainForm"
                                   {{ is_array(old('grade_ids')) && in_array($grade->id, old('grade_ids')) ? 'checked' : '' }}
                                   class="grade-checkbox peer sr-only">
                            
                            <div class="border-2 border-gray-200 rounded-xl p-3 transition-all duration-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300 hover:shadow-md peer-checked:shadow-md">
                                <!-- Image -->
                                @if($grade->image_url)
                                    <div class="w-full h-20 rounded-lg overflow-hidden mb-3 bg-gray-100">
                                        <img src="{{ $grade->image_url }}" 
                                             alt="{{ $grade->name }}" 
                                             class="w-full h-full object-cover">
                                    </div>
                                @else
                                    <div class="w-full h-20 bg-gray-100 rounded-lg flex items-center justify-center mb-3">
                                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                
                                <!-- Name -->
                                <p class="text-sm font-medium text-gray-900 text-center leading-snug">{{ $grade->name }}</p>
                                
                                <!-- Checkmark -->
                                <div class="absolute top-2 right-2 w-6 h-6 bg-white border-2 border-gray-300 rounded-full flex items-center justify-center transition-all duration-200 peer-checked:bg-blue-600 peer-checked:border-blue-600 shadow-sm">
                                    <svg class="w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 transition-opacity duration-200" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </label>
                        @endforeach
                    </div>

                    <!-- No results message -->
                    <div id="noResults" class="hidden text-center py-12">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500 font-medium">Tidak ada grade yang ditemukan</p>
                        <p class="text-xs text-gray-400 mt-1">Coba kata kunci lain</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('gradeSearch');
    const gradeItems = document.querySelectorAll('.grade-item');
    const noResults = document.getElementById('noResults');
    const gradeGrid = document.getElementById('gradeGrid');
    const selectedCounter = document.getElementById('selectedCounter');
    const selectedCount = document.getElementById('selectedCount');
    const checkboxes = document.querySelectorAll('.grade-checkbox');

    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        let hasResults = false;

        gradeItems.forEach(item => {
            const gradeName = item.dataset.name;
            if (gradeName.includes(searchTerm)) {
                item.style.display = '';
                hasResults = true;
            } else {
                item.style.display = 'none';
            }
        });

        if (hasResults) {
            gradeGrid.style.display = '';
            noResults.style.display = 'none';
        } else {
            gradeGrid.style.display = 'none';
            noResults.style.display = 'block';
        }
    });

    // Update selected counter
    function updateCounter() {
        const checkedBoxes = document.querySelectorAll('.grade-checkbox:checked');
        const count = checkedBoxes.length;
        
        selectedCount.textContent = count;
        if (count > 0) {
            selectedCounter.classList.remove('hidden');
        } else {
            selectedCounter.classList.add('hidden');
        }
    }

    // Add event listeners to checkboxes
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateCounter);
    });

    // Initial counter update
    updateCounter();
});
</script>
@endsection