@extends('layouts.app')

@section('title', 'Input Grading - Step 1')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Input Grading - Step 1</h1>
                    <p class="mt-1 text-sm text-gray-600">Pilih item yang akan digrading.</p>
                </div>
                <a href="{{ route('grading-goods.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali
                </a>
            </div>

            <div class="mb-8 bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center justify-between max-w-xl mx-auto">
                    <div class="flex flex-col items-center flex-1">
                        <div
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-500 text-white font-semibold text-sm shadow-sm">
                            1</div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-blue-600">Pilih Item</span>
                    </div>
                    <div class="flex-1 h-0.5 bg-gray-200 mx-2 sm:mx-4 -mt-6"></div>
                    <div class="flex flex-col items-center flex-1">
                        <div
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-400 font-semibold text-sm">
                            2</div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-gray-400">Lengkapi Data</span>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('grading-goods.store.step1') }}" id="mainForm">
                @csrf
                <div class="bg-white shadow-sm border rounded-lg">

                    <div class="p-6 border-b">
                        <div>
                            <label for="grading_date" class="block text-sm font-medium text-gray-700">
                                Tanggal Grading <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="date" name="grading_date" id="grading_date"
                                    value="{{ old('grading_date', date('Y-m-d')) }}"
                                    class="mt-1.5 shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md text-gray-900 font-semibold pl-4 pr-10 py-2.5 @error('grading_date') border-red-500 @enderror"
                                    required>

                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            @error('grading_date')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- filepath: resources/views/admin/grading-goods/step1.blade.php -->

                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">
                                    Pilih Item (Hasil Pencarian) <span class="text-red-500">*</span>
                                </h2>
                                <p class="text-sm text-gray-500 mt-0.5">Pilih satu item yang akan digrading.</p>
                            </div>
                        </div>

                        @error('receipt_item_id')
                            <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            </div>
                        @enderror

                        <!-- Filter dan Search Section -->
                        <div class="mt-4 space-y-4">
                            <!-- Filter Bulan -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div>
                                    <label for="monthFilter" class="block text-sm font-medium text-gray-700 mb-1">
                                        Filter Bulan Kedatangan
                                    </label>
                                    <select id="monthFilter"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all text-sm">
                                        <option value="">Semua Bulan</option>
                                        <option value="01">Januari</option>
                                        <option value="02">Februari</option>
                                        <option value="03">Maret</option>
                                        <option value="04">April</option>
                                        <option value="05">Mei</option>
                                        <option value="06">Juni</option>
                                        <option value="07">Juli</option>
                                        <option value="08">Agustus</option>
                                        <option value="09">September</option>
                                        <option value="10">Oktober</option>
                                        <option value="11">November</option>
                                        <option value="12">Desember</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="yearFilter" class="block text-sm font-medium text-gray-700 mb-1">
                                        Filter Tahun
                                    </label>
                                    <select id="yearFilter"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all text-sm">
                                        <option value="">Semua Tahun</option>
                                        @for ($year = date('Y'); $year >= date('Y') - 5; $year--)
                                            <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>
                                                {{ $year }}</option>
                                        @endfor
                                    </select>
                                </div>

                                <div>
                                    <label for="supplierFilter" class="block text-sm font-medium text-gray-700 mb-1">
                                        Filter Supplier
                                    </label>
                                    <select id="supplierFilter"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all text-sm">
                                        <option value="">Semua Supplier</option>
                                        <!-- Options akan diisi dinamis via JavaScript -->
                                    </select>
                                </div>

                                <div class="flex items-end">
                                    <button type="button" id="resetFilters"
                                        class="w-full px-3 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium border border-gray-300">
                                        Reset Filter
                                    </button>
                                </div>
                            </div>

                            <!-- Search Bar -->
                            <div class="relative">
                                <input type="text" id="itemSearch"
                                    placeholder="Cari berdasarkan nama grade atau supplier..."
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Filter Summary -->
                            <div id="filterSummary" class="hidden">
                                <div class="flex flex-wrap gap-2">
                                    <span class="text-sm text-gray-600">Filter aktif:</span>
                                    <div id="activeFilters" class="flex flex-wrap gap-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 pb-6">
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 max-h-[600px] overflow-y-auto pr-2 custom-scrollbar"
                            id="itemGrid">
                            @forelse($receiptItems as $ri)
                                <label class="receipt-item-card relative cursor-pointer group"
                                    data-name="{{ strtolower(optional($ri)->grade_supplier_name ?? '') }}"
                                    data-supplier="{{ strtolower(optional($ri)->supplier_name ?? '') }}"
                                    data-receipt-date="{{ $ri->receipt_date ? \Carbon\Carbon::parse($ri->receipt_date)->format('Y-m-d') : '' }}"
                                    data-receipt-month="{{ $ri->receipt_date ? \Carbon\Carbon::parse($ri->receipt_date)->format('m') : '' }}"
                                    data-receipt-year="{{ $ri->receipt_date ? \Carbon\Carbon::parse($ri->receipt_date)->format('Y') : '' }}"
                                    data-tgl-datang="{{ $ri->receipt_date ? \Carbon\Carbon::parse($ri->receipt_date)->format('d/m/Y') : 'N/A' }}"
                                    data-berat-gudang="{{ $ri->warehouse_weight_grams ?? '0' }} g">
                                    <input type="radio" name="receipt_item_id" value="{{ $ri->id }}"
                                        form="mainForm" {{ old('receipt_item_id') == $ri->id ? 'checked' : '' }} required
                                        class="receipt-radio peer sr-only">

                                    <div
                                        class="border-2 border-gray-200 rounded-xl p-3 transition-all duration-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300 hover:shadow-md peer-checked:shadow-md h-full flex flex-col">

                                        {{-- AREA GAMBAR DIPERBAIKI --}}
                                        <div
                                            class="w-full h-20 bg-gray-100 rounded-lg flex items-center justify-center mb-3 overflow-hidden relative">
                                            @if (!empty($ri->grade_supplier_image_url))
                                                @php
                                                    $imgUrl = $ri->grade_supplier_image_url;
                                                    $isExternal = \Illuminate\Support\Str::startsWith($imgUrl, [
                                                        'http://',
                                                        'https://',
                                                    ]);
                                                    $finalSrc = $isExternal ? $imgUrl : asset('storage/' . $imgUrl);
                                                @endphp

                                                <img src="{{ $finalSrc }}" alt="{{ $ri->grade_supplier_name }}"
                                                    class="object-cover w-full h-full"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                                                {{-- Fallback jika gambar error load --}}
                                                <div
                                                    class="hidden w-full h-full items-center justify-center bg-gray-100 absolute top-0 left-0">
                                                    <svg class="w-8 h-8 text-gray-300" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            @else
                                                {{-- Icon Default --}}
                                                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2z" />
                                                </svg>
                                            @endif
                                        </div>
                                        {{-- END AREA GAMBAR --}}

                                        <div class="flex-1 flex flex-col justify-center">
                                            <p class="text-sm font-semibold text-gray-900 leading-snug text-center mb-1">
                                                {{ optional($ri)->grade_supplier_name ?: 'N/A' }}
                                            </p>
                                            <p class="text-xs text-gray-500 text-center leading-tight">
                                                {{ optional($ri)->supplier_name ?: 'Supplier N/A' }}
                                            </p>
                                        </div>

                                        <div
                                            class="absolute top-2 right-2 w-6 h-6 bg-white border-2 border-gray-300 rounded-full flex items-center justify-center transition-all duration-200 peer-checked:bg-blue-600 peer-checked:border-blue-600 shadow-sm">
                                            <svg class="w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 transition-opacity duration-200"
                                                fill="currentColor" viewBox="0 0 20 20">
                                                <circle cx="10" cy="10" r="4" />
                                            </svg>
                                        </div>
                                    </div>
                                </label>
                            @empty
                                <div class="col-span-full text-center py-12">
                                    <div
                                        class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm text-gray-500 font-medium">Tidak ada item ditemukan</p>
                                    <p class="text-xs text-gray-400 mt-1">Coba gunakan filter 'Nama Grade Supplier' di
                                        atas.</p>
                                </div>
                            @endforelse
                        </div>

                        <div id="noResults" class="hidden text-center py-12">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500 font-medium">Tidak ada item yang ditemukan</p>
                            <p class="text-xs text-gray-400 mt-1">Coba kata kunci lain di pencarian.</p>
                        </div>
                    </div>

                    <br>
                    <hr>
                    <br>

                    <div id="autofillContainer" class="hidden px-6 pb-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <label for="auto_tgl_datang" class="block text-sm font-medium text-gray-700">
                                Tanggal Datang
                            </label>
                            <div class="relative">
                                <input type="text" name="auto_tgl_datang" id="auto_tgl_datang"
                                    class="mt-1.5 shadow-sm block w-full sm:text-sm border-gray-300 rounded-md bg-white text-gray-900 font-semibold pl-4 pr-10 py-2.5"
                                    readonly>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="auto_berat_gudang" class="block text-sm font-medium text-gray-700">
                                Berat Gudang
                            </label>
                            <input type="text" name="auto_berat_gudang" id="auto_berat_gudang"
                                class="mt-1.5 shadow-sm block w-full sm:text-sm border-gray-300 rounded-md bg-white text-gray-900 font-semibold px-4 py-2.5"
                                readonly>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 p-6 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                        <button type="submit" form="mainForm"
                            class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-500 text-white rounded-md hover:bg-blue-700 w-full sm:w-auto">
                            Simpan & Lanjut ke Step 2
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
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

        input[type="date"]::-webkit-calendar-picker-indicator {
            background: transparent;
            bottom: 0;
            color: transparent;
            cursor: pointer;
            height: auto;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            width: auto;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('itemSearch');
            const monthFilter = document.getElementById('monthFilter');
            const yearFilter = document.getElementById('yearFilter');
            const supplierFilter = document.getElementById('supplierFilter');
            const resetFiltersBtn = document.getElementById('resetFilters');
            const gradeItems = document.querySelectorAll('.receipt-item-card');
            const noResults = document.getElementById('noResults');
            const gradeGrid = document.getElementById('itemGrid');
            const filterSummary = document.getElementById('filterSummary');
            const activeFilters = document.getElementById('activeFilters');
            const radioButtons = document.querySelectorAll('.receipt-radio');
            const tglDatangInput = document.getElementById('auto_tgl_datang');
            const beratGudangInput = document.getElementById('auto_berat_gudang');
            const autofillContainer = document.getElementById('autofillContainer');

            // Populate supplier filter options
            function populateSupplierFilter() {
                const suppliers = new Set();
                gradeItems.forEach(item => {
                    const supplierName = item.dataset.supplier;
                    if (supplierName && supplierName !== 'supplier n/a') {
                        suppliers.add(supplierName);
                    }
                });

                // Clear existing options (except "Semua Supplier")
                supplierFilter.innerHTML = '<option value="">Semua Supplier</option>';

                // Add unique suppliers
                Array.from(suppliers).sort().forEach(supplier => {
                    const option = document.createElement('option');
                    option.value = supplier.toLowerCase();
                    option.textContent = supplier.split(' ').map(word =>
                        word.charAt(0).toUpperCase() + word.slice(1)
                    ).join(' ');
                    supplierFilter.appendChild(option);
                });
            }

            // Enhanced filtering function
            function applyFilters() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const selectedMonth = monthFilter.value;
                const selectedYear = yearFilter.value;
                const selectedSupplier = supplierFilter.value;

                let hasResults = false;
                let visibleCount = 0;

                gradeItems.forEach(item => {
                    const gradeName = item.dataset.name || '';
                    const supplierName = item.dataset.supplier || '';
                    const receiptMonth = item.dataset.receiptMonth || '';
                    const receiptYear = item.dataset.receiptYear || '';

                    let showItem = true;

                    // Search filter
                    if (searchTerm && !gradeName.includes(searchTerm) && !supplierName.includes(
                        searchTerm)) {
                        showItem = false;
                    }

                    // Month filter
                    if (selectedMonth && receiptMonth !== selectedMonth) {
                        showItem = false;
                    }

                    // Year filter
                    if (selectedYear && receiptYear !== selectedYear) {
                        showItem = false;
                    }

                    // Supplier filter
                    if (selectedSupplier && supplierName !== selectedSupplier) {
                        showItem = false;
                    }

                    if (showItem) {
                        item.style.display = '';
                        hasResults = true;
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                        // Uncheck radio if hidden
                        const radio = item.querySelector('input[type="radio"]');
                        if (radio && radio.checked) {
                            radio.checked = false;
                            autofillContainer.classList.add('hidden');
                        }
                    }
                });

                // Update display
                if (hasResults) {
                    gradeGrid.style.display = 'grid';
                    noResults.style.display = 'none';
                } else {
                    gradeGrid.style.display = 'none';
                    noResults.style.display = 'block';
                }

                // Update filter summary
                updateFilterSummary(searchTerm, selectedMonth, selectedYear, selectedSupplier, visibleCount);
            }

            // Update filter summary display
            function updateFilterSummary(searchTerm, month, year, supplier, count) {
                const filters = [];

                if (searchTerm) filters.push(`Pencarian: "${searchTerm}"`);
                if (month) {
                    const monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                    ];
                    filters.push(`Bulan: ${monthNames[parseInt(month)]}`);
                }
                if (year) filters.push(`Tahun: ${year}`);
                if (supplier) {
                    const supplierName = supplier.split(' ').map(word =>
                        word.charAt(0).toUpperCase() + word.slice(1)
                    ).join(' ');
                    filters.push(`Supplier: ${supplierName}`);
                }

                if (filters.length > 0) {
                    activeFilters.innerHTML = filters.map(filter =>
                        `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    ${filter}
                </span>`
                    ).join('') + `<span class="text-xs text-gray-500 ml-2">(${count} item ditemukan)</span>`;
                    filterSummary.classList.remove('hidden');
                } else {
                    filterSummary.classList.add('hidden');
                }
            }

            // Reset all filters
            function resetAllFilters() {
                searchInput.value = '';
                monthFilter.value = '';
                yearFilter.value = '';
                supplierFilter.value = '';
                applyFilters();
            }

            // Event listeners
            if (searchInput) searchInput.addEventListener('input', applyFilters);
            if (monthFilter) monthFilter.addEventListener('change', applyFilters);
            if (yearFilter) yearFilter.addEventListener('change', applyFilters);
            if (supplierFilter) supplierFilter.addEventListener('change', applyFilters);
            if (resetFiltersBtn) resetFiltersBtn.addEventListener('click', resetAllFilters);

            // Autofill logic
            function updateAutofill(selectedRadio) {
                if (!selectedRadio) return;
                const card = selectedRadio.closest('.receipt-item-card');
                if (card && tglDatangInput && beratGudangInput && autofillContainer) {
                    const tglDatang = card.dataset.tglDatang;
                    const beratGudang = card.dataset.beratGudang;

                    tglDatangInput.value = tglDatang;
                    beratGudangInput.value = beratGudang;

                    autofillContainer.classList.remove('hidden');
                }
            }

            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        updateAutofill(this);
                    }
                });
            });

            // Initial setup
            populateSupplierFilter();

            // Set current year as default
            if (yearFilter) {
                yearFilter.value = new Date().getFullYear().toString();
            }

            // Apply initial filters
            applyFilters();

            // Initial check for previously selected radio
            const checkedRadio = document.querySelector('.receipt-radio:checked');
            if (checkedRadio) {
                updateAutofill(checkedRadio);
            }
        });
    </script>
@endsection
