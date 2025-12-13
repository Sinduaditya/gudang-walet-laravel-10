@extends('layouts.app')

@section('title', 'Transfer Internal - Step 1')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header with Back Button and History Tab Toggle --}}
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Transfer Internal</h1>
                    <p class="mt-1 text-sm text-gray-600">Pindahkan stok barang antar lokasi</p>
                </div>

                <div class="flex items-center gap-3">
                    {{-- Tab Toggle Button --}}
                    <button type="button" onclick="toggleHistoryTab()" id="historyToggleBtn"
                        class="inline-flex items-center px-4 py-2 border border-purple-300 text-sm font-medium rounded-lg text-purple-700 bg-purple-50 hover:bg-purple-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span id="historyToggleText">Daftar transfer internal</span>
                    </button>

                    {{-- Back Button --}}
                    <a href="{{ route('barang.keluar.index') }}"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Kembali
                    </a>
                </div>
            </div>

            {{-- Progress Steps --}}
            <div id="progressSteps" class="mb-8 bg-white rounded-lg shadow-sm border p-6">

                <div class="flex items-center justify-between max-w-3xl mx-auto">
                    <div class="flex flex-col items-center flex-1">
                        <div
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-purple-500 text-white font-semibold text-base shadow-sm">
                            1
                        </div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-purple-600">
                            Data Transfer
                        </span>
                    </div>

                    <div class="flex-1 h-0.5 bg-gray-200 mx-2 sm:mx-4 -mt-6"></div>

                    <div class="flex flex-col items-center flex-1">
                        <div
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-400 font-semibold text-base">
                            2
                        </div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-gray-400">
                            Konfirmasi
                        </span>
                    </div>
                </div>
            </div>

            {{-- Error Messages --}}
            @if ($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r-lg shadow-sm">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="font-semibold mb-1">Terdapat kesalahan:</p>
                            <ul class="list-disc list-inside text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Tab Content Container --}}
            <div class="space-y-8">

                {{-- Form Tab (Default Active) --}}
                <div id="formTab" class="tab-content">
                    <div class="bg-white rounded-xl shadow-md border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Informasi Transfer</h2>
                            <p class="text-sm text-gray-500 mt-1">Lengkapi data transfer stok internal</p>
                        </div>

                        <form id="transferForm" action="{{ route('barang.keluar.transfer.store') }}" method="POST" class="p-6">
                            @csrf

                            <div class="space-y-6">

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Supplier Filter for Grade --}}
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2">
                                            Filter Supplier <span class="text-gray-400 font-normal text-xs">(Opsional)</span>
                                        </label>
                                        <select id="filter_supplier_id" 
                                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                            <option value="">-- Semua Supplier --</option>
                                            @foreach($suppliers as $s)
                                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Grade Select --}}
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                            Grade Perusahaan <span class="text-red-500">*</span>
                                        </label>

                                        <select name="grade_company_id" id="grade_company_id" required
                                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                                            <option value="">-- Pilih Grade --</option>
                                            @foreach ($gradesWithStock as $g)
                                                <option value="{{ $g['id'] }}" 
                                                    data-stock="{{ $g['batch_stock_grams'] }}"
                                                    data-supplier-id="{{ $g['supplier_id'] }}"
                                                    {{ old('grade_company_id') == $g['id'] ? 'selected' : '' }}>
                                                    {{ $g['name'] }} - {{ $g['supplier_name'] }} - {{ $g['grading_date'] }} (Batch: {{ number_format($g['batch_stock_grams'], 0, ',', '.') }} gr)
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('grade_company_id')
                                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                        {{-- Stock hint --}}
                                        <p id="grade-stock-hint" class="mt-2 text-sm text-gray-500">
                                            Stok tersedia: <span id="grade-stock-value" class="font-semibold">-</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="relative">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                                </svg>
                                                Lokasi Asal <span class="text-red-500">*</span>
                                            </label>

                                            <div
                                                class="w-full border border-gray-200 bg-blue-50 rounded-lg p-3 text-blue-800 font-medium">
                                                {{ $gudangUtama->name ?? 'Gudang Utama' }}
                                            </div>

                                            <p class="mt-1.5 text-xs text-blue-600">
                                                <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Barang akan dikirim dari lokasi ini
                                            </p>
                                            <input type="hidden" name="from_location_id" value="{{ $gudangUtama->id ?? 1 }}">
                                        </div>

                                        <div>
                                            <label class="block font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Lokasi Tujuan <span class="text-red-500">*</span>
                                            </label>
                                            
                                            <div class="w-full border border-gray-200 bg-gray-50 rounded-lg p-3 text-gray-800 font-medium">
                                                {{ $dmkLocation->name ?? 'DMK' }}
                                            </div>
                                            <input type="hidden" name="to_location_id" value="{{ $dmkLocation->id ?? '' }}">
                                            
                                            @error('to_location_id')
                                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                {{-- Weight & Date --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                            </svg>
                                            Berat Transfer (gram) <span class="text-red-500">*</span>
                                        </label>
                                            <div class="flex gap-2">
                                                <input type="number" name="weight_grams" id="weight_grams"
                                                    value="{{ old('weight_grams') }}" step="0.01" min="0.01"
                                                    placeholder="Masukkan berat dalam gram" required
                                                    class="flex-1 border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                                                    oninput="calculateTotalDeduction()">
                                                <button type="button" onclick="checkStock()" id="btnCheckStock"
                                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                                                                    Cek Stok
                                                </button>
                                            </div>
                                            <p id="stock-check-result" class="mt-2 text-sm hidden"></p>
                                            @error('weight_grams')
                                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                                            </svg>
                                            Susut (gram)
                                            <span class="text-gray-400 font-normal text-xs">(Opsional)</span>
                                        </label>
                                        <input type="number" name="susut_grams" id="susut_grams"
                                            value="{{ old('susut_grams', 0) }}" step="0.01" min="0"
                                            placeholder="Masukkan berat susut (jika ada)"
                                                oninput="calculateTotalDeduction()"
                                                class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                                            <p class="mt-1.5 text-xs text-gray-500">
                                                <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Susut akan ditambahkan ke pengurangan stok
                                            </p>
                                            <p id="total-deduction-info" class="mt-2 text-sm text-gray-600 font-medium hidden"></p>
                                            @error('susut_grams')
                                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                {{-- Tanggal Transfer (Lebar Penuh) --}}
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Tanggal Transfer
                                        <span class="text-gray-400 font-normal text-xs">(Opsional)</span>
                                    </label>
                                    <input type="date" name="transfer_date"
                                        value="{{ old('transfer_date', date('Y-m-d')) }}"
                                        class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                                    @error('transfer_date')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Catatan
                                        <span class="text-gray-400 font-normal text-xs">(Opsional)</span>
                                    </label>
                                    <textarea name="notes" rows="3" placeholder="Tambahkan catatan atau keterangan transfer..."
                                        class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Form Actions --}}
                            <div class="flex items-center gap-3 pt-6 border-t border-gray-200 mt-6">
                                <a href="{{ route('barang.keluar.index') }}"
                                    class="flex-1 inline-flex items-center justify-center px-4 py-3 border-2 border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    Batal
                                </a>
                                <button type="button" onclick="showConfirmationModal()"
                                    class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-purple-800 focus:ring-4 focus:ring-purple-300 transition-all duration-200 shadow-lg hover:shadow-xl">
                                    Lanjut ke Konfirmasi
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- History Tab (Hidden by default) --}}
                <div id="historyTab" class="tab-content hidden">
                    <div class="bg-white rounded-xl shadow-md border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <form action="{{ route('barang.keluar.transfer.step1') }}" method="GET" class="flex flex-wrap items-end gap-4">
                                {{-- Keep existing filters if any --}}
                                @if(request('page'))
                                    <input type="hidden" name="page" value="{{ request('page') }}">
                                @endif
                                
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" 
                                           class="w-full md:w-auto text-sm border-gray-300 rounded-md px-3 py-2 focus:ring-purple-500 focus:border-purple-500">
                                </div>
                                
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" 
                                           class="w-full md:w-auto text-sm border-gray-300 rounded-md px-3 py-2 focus:ring-purple-500 focus:border-purple-500">
                                </div>

                                <div>
                                    <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                                    <select name="supplier_id" id="supplier_id" class="w-full md:w-auto text-sm border-gray-300 rounded-md px-3 py-2 focus:ring-purple-500 focus:border-purple-500">
                                        <option value="">Semua Supplier</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="grade_company_id" class="block text-sm font-medium text-gray-700 mb-1">Grade</label>
                                    <select name="grade_company_id" id="grade_company_id" class="w-full md:w-auto text-sm border-gray-300 rounded-md px-3 py-2 focus:ring-purple-500 focus:border-purple-500">
                                        <option value="">Semua Grade</option>
                                        @foreach($grades as $grade)
                                            <option value="{{ $grade->id }}" {{ request('grade_company_id') == $grade->id ? 'selected' : '' }}>
                                                {{ $grade->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="flex gap-2">
                                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors">
                                        Filter
                                    </button>
                                    
                                    @if(request('start_date') || request('end_date'))
                                        <a href="{{ route('barang.keluar.transfer.step1') }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                                            Reset
                                        </a>
                                    @endif
                                </div>
                            </form>

                            <button onclick="toggleHistoryTab()"
                                    class="text-sm text-gray-600 hover:text-gray-800 flex items-center gap-1 px-3 py-1.5 hover:bg-gray-100 rounded transition md:ml-auto">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Tutup
                            </button>
                            </div>
                        </div>

                        {{-- Summary Section --}}
                        @if(isset($summary) && $summary->count() > 0)
                            <div class="px-6 py-4 bg-purple-50 border-b border-purple-100">
                                <h4 class="text-sm font-semibold text-purple-800 mb-2">Total Stok Ditransfer per Grade (Sesuai Filter)</h4>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    @foreach($summary as $gradeName => $totalWeight)
                                        <div class="bg-white p-3 rounded shadow-sm border border-purple-100">
                                            <div class="text-xs text-gray-500">{{ $gradeName }}</div>
                                            <div class="text-lg font-bold text-purple-600">
                                                {{ number_format($totalWeight, 0, ',', '.') }} <span class="text-xs font-normal text-gray-500">gr</span>
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                {{ number_format($totalWeight / 1000, 2, ',', '.') }} kg
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Tanggal
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Grade
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Supplier
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Transfer
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Berat
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Susut
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Referensi
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($transferInternalTransactions as $transfer)
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ \Carbon\Carbon::parse($transfer->transfer_date)->format('d/m/Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $transfer->gradeCompany->name ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $transfer->sortingResult->receiptItem->purchaseReceipt->supplier->name ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="flex items-center">
                                                    <span
                                                        class="text-gray-700">{{ $transfer->fromLocation->name ?? '-' }}</span>
                                                    <svg class="w-4 h-4 mx-2 text-purple-600" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                    </svg>
                                                    <span
                                                        class="text-purple-700 font-medium">{{ $transfer->toLocation->name ?? '-' }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-semibold text-purple-600">
                                                    {{ number_format($transfer->weight_grams, 2) }} gr
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    ({{ number_format($transfer->weight_grams / 1000, 2) }} kg)
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                @if($transfer->susut_grams > 0)
                                                    <div class="text-sm font-semibold text-red-600">
                                                        {{ number_format($transfer->susut_grams, 2) }} gr
                                                    </div>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded text-xs font-mono bg-gray-100 text-gray-700">
                                                    #{{ $transfer->id }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <div class="flex justify-center items-center space-x-2">
                                                    <a href="{{ route('barang.keluar.transfer.edit', $transfer->id) }}" class="text-blue-600 hover:text-blue-900" title="Edit">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </a>
                                                    <form action="{{ route('barang.keluar.transfer.destroy', $transfer->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini? Stok akan dikembalikan.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Hapus">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-12 text-center">
                                                <div class="flex flex-col items-center justify-center">
                                                    <div
                                                        class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                        <svg class="w-8 h-8 text-gray-400" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                                        </svg>
                                                    </div>
                                                    <p class="text-gray-500 font-medium">Belum ada daftar transfer internal
                                                    </p>
                                                    <p class="text-gray-400 text-sm mt-1">Transaksi akan muncul setelah
                                                        Anda melakukan transfer</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($transferInternalTransactions->hasPages())
                            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm text-gray-600">
                                        Menampilkan {{ $transferInternalTransactions->firstItem() }} -
                                        {{ $transferInternalTransactions->lastItem() }} dari
                                        {{ $transferInternalTransactions->total() }} transaksi
                                    </div>
                                    <div>
                                        {{ $transferInternalTransactions->links() }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity" aria-hidden="true" onclick="closeConfirmationModal()"></div>

            <!-- Modal panel -->
            <!-- Modal panel -->
            <div class="relative inline-block w-full max-w-md overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="w-full">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-purple-100">
                            <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Konfirmasi Transfer Internal
                            </h3>
                            <div class="mt-4 text-left">
                                <div class="bg-gray-50 rounded-lg p-4 space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Grade:</span>
                                        <span class="font-medium text-gray-900" id="modal-grade"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Lokasi Asal:</span>
                                        <span class="font-medium text-gray-900" id="modal-from"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Lokasi Tujuan:</span>
                                        <span class="font-medium text-gray-900" id="modal-to"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Berat Transfer:</span>
                                        <span class="font-medium text-gray-900" id="modal-weight"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Susut:</span>
                                        <span class="font-medium text-red-600" id="modal-susut"></span>
                                    </div>
                                    <div class="pt-2 border-t border-gray-200 flex justify-between font-semibold">
                                        <span class="text-gray-700">Total Pengurangan Stok:</span>
                                        <span class="text-purple-700" id="modal-total"></span>
                                    </div>
                                    <div class="pt-2 border-t border-gray-200">
                                        <span class="block text-gray-500 mb-1">Catatan:</span>
                                        <p class="text-gray-700 italic" id="modal-notes">-</p>
                                    </div>
                                </div>
                                <p class="mt-4 text-sm text-gray-500">
                                    Pastikan data sudah benar. Stok akan berkurang secara otomatis setelah konfirmasi.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-row justify-between gap-3">
                    <button type="button" onclick="closeConfirmationModal()" class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                    <button type="button" onclick="submitTransferForm()" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:w-auto sm:text-sm">
                        Konfirmasi Transfer
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function toggleHistoryTab() {
                const formTab = document.getElementById("formTab");
                const historyTab = document.getElementById("historyTab");
                const btnText = document.getElementById("historyToggleText");
                const progressSteps = document.getElementById("progressSteps");

                const isHistoryOpen = !historyTab.classList.contains("hidden");

                if (isHistoryOpen) {
                    // Kembali ke FORM
                    historyTab.classList.add("hidden");
                    formTab.classList.remove("hidden");
                    progressSteps.classList.remove("hidden");
                    btnText.textContent = "Daftar transfer internal";
                } else {
                    // Buka HISTORY
                    historyTab.classList.remove("hidden");
                    formTab.classList.add("hidden");
                    progressSteps.classList.add("hidden");
                    btnText.textContent = "Kembali ke form";
                }
            }

            // Grade Selection and Stock Check (same as sell form)
            // Grade Selection and Stock Check
            // Grade Selection and Stock Check
            const gradeSelect = document.getElementById('grade_company_id');
            const gradeStockValue = document.getElementById('grade-stock-value');
            const supplierFilter = document.getElementById('filter_supplier_id');

            // Filter Grades by Supplier
            supplierFilter.addEventListener('change', function() {
                const selectedSupplierId = this.value;
                const options = gradeSelect.querySelectorAll('option');
                
                // Reset selection
                gradeSelect.value = "";
                if (gradeStockValue) {
                    gradeStockValue.textContent = '-';
                    gradeStockValue.classList.remove('text-green-600', 'text-red-600');
                }
                const resultEl = document.getElementById('stock-check-result');
                if (resultEl) resultEl.classList.add('hidden');

                options.forEach(option => {
                    if (option.value === "") return; // Skip placeholder

                    const gradeSupplierId = option.dataset.supplierId;
                    
                    if (!selectedSupplierId || gradeSupplierId == selectedSupplierId) {
                        option.style.display = '';
                        option.disabled = false;
                    } else {
                        option.style.display = 'none';
                        option.disabled = true;
                    }
                });
            });

            // Auto fetch stock info when grade changes
            gradeSelect.addEventListener('change', function() {
                // Reset stock check result
                const resultEl = document.getElementById('stock-check-result');
                if (resultEl) resultEl.classList.add('hidden');

                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value) {
                    const stock = selectedOption.dataset.stock || 0;
                    if (gradeStockValue) {
                        gradeStockValue.textContent = new Intl.NumberFormat('id-ID').format(stock) + ' gr';
                        gradeStockValue.classList.remove('text-red-600');
                        gradeStockValue.classList.add('text-green-600');
                    }
                } else {
                    if (gradeStockValue) {
                        gradeStockValue.textContent = '-';
                        gradeStockValue.classList.remove('text-green-600', 'text-red-600');
                    }
                }
            });

                function calculateTotalDeduction() {
                    const weight = parseFloat(document.getElementById('weight_grams').value || 0);
                    const susut = parseFloat(document.getElementById('susut_grams').value || 0);
                    const total = weight + susut;

                    const infoEl = document.getElementById('total-deduction-info');
                    if (total > 0) {
                        infoEl.textContent = `Total pengurangan stok: ${new Intl.NumberFormat('id-ID').format(total)} gr (Transfer: ${weight} + Susut: ${susut})`;
                        infoEl.classList.remove('hidden');
                    } else {
                        infoEl.classList.add('hidden');
                    }
                }

                function checkStock() {
                    const gradeId = document.getElementById('grade_company_id').value;
                    const weight = parseFloat(document.getElementById('weight_grams').value || 0);
                    const susut = parseFloat(document.getElementById('susut_grams').value || 0);
                    const totalNeeded = weight + susut;
                    const resultEl = document.getElementById('stock-check-result');

                    if (!gradeId) {
                        showStockResult('Pilih grade terlebih dahulu.', 'error');
                        return;
                    }

                    if (totalNeeded <= 0) {
                        showStockResult('Masukkan berat yang valid.', 'error');
                        return;
                    }

                    // Show loading
                    showStockResult('Mengecek stok...', 'info');

                    fetch(
                            `{{ route('barang.keluar.transfer.stock_check') }}?grade_company_id=${gradeId}&location_id={{ $gudangUtama->id ?? 1 }}`
                            )
                        .then(response => response.json())
                        .then(data => {
                            if (!data.ok) {
                                showStockResult('Gagal mengecek stok.', 'error');
                                return;
                            }

                            const available = parseFloat(data.available_grams);
                            if (available >= totalNeeded) {
                                showStockResult(
                                    `✓ Stok mencukupi! Tersedia ${new Intl.NumberFormat('id-ID').format(available)} gram. (Butuh: ${new Intl.NumberFormat('id-ID').format(totalNeeded)} gr)`,
                                    'success'
                                );
                            } else {
                                showStockResult(
                                    `⚠ Stok tidak mencukupi! Hanya tersedia ${new Intl.NumberFormat('id-ID').format(available)} gram. (Butuh: ${new Intl.NumberFormat('id-ID').format(totalNeeded)} gr)`,
                                    'error'
                                );
                            }
                        })
                        .catch(() => {
                            showStockResult('Gagal mengecek stok. Silakan coba lagi.', 'error');
                        });
                }

            function showStockResult(message, type) {
                const resultEl = document.getElementById('stock-check-result');
                resultEl.classList.remove('hidden', 'text-red-600', 'text-green-600', 'text-purple-600');

                switch (type) {
                    case 'success':
                        resultEl.classList.add('text-green-600');
                        break;
                    case 'error':
                        resultEl.classList.add('text-red-600');
                        break;
                    case 'info':
                        resultEl.classList.add('text-purple-600');
                        break;
                }

                resultEl.textContent = message;
            }

            // Check if there's a page parameter (from pagination), if yes, show history tab
            // Check if there's a page parameter (from pagination), if yes, show history tab
            // Check if there's a page parameter (from pagination), if yes, show history tab
            document.addEventListener('DOMContentLoaded', function() {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('page') || urlParams.has('start_date') || urlParams.has('end_date')) {
                    toggleHistoryTab();
                }

                // Initialize stock display if grade is selected
                if (gradeSelect.value) {
                    const selectedOption = gradeSelect.options[gradeSelect.selectedIndex];
                    const stock = selectedOption.dataset.stock || 0;
                    if (gradeStockValue) {
                        gradeStockValue.textContent = new Intl.NumberFormat('id-ID').format(stock) + ' gr';
                        gradeStockValue.classList.remove('text-red-600');
                        gradeStockValue.classList.add('text-green-600');
                    }
                }
            });

            function showConfirmationModal() {
                const form = document.getElementById('transferForm');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                // Get values
                const gradeSelect = document.getElementById('grade_company_id');
                const selectedOption = gradeSelect.options[gradeSelect.selectedIndex];
                const availableStock = parseFloat(selectedOption.dataset.stock || 0);

                const weight = parseFloat(document.getElementById('weight_grams').value || 0);
                const susut = parseFloat(document.getElementById('susut_grams').value || 0);
                const totalNeeded = weight + susut;

                if (totalNeeded > availableStock) {
                    showStockResult(`Stok tidak mencukupi! Tersedia: ${new Intl.NumberFormat('id-ID').format(availableStock)} gr. Dibutuhkan: ${new Intl.NumberFormat('id-ID').format(totalNeeded)} gr.`, 'error');
                    document.getElementById('stock-check-result').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                const gradeName = selectedOption.text;
                const fromLocation = "Gudang Utama"; // Fixed as per controller
                const toLocation = "DMK"; // Static as per requirement
                const notes = document.querySelector('textarea[name="notes"]').value;

                // Populate modal
                document.getElementById('modal-grade').textContent = gradeName;
                document.getElementById('modal-from').textContent = fromLocation;
                document.getElementById('modal-to').textContent = toLocation;
                document.getElementById('modal-weight').textContent = new Intl.NumberFormat('id-ID').format(weight) + ' gr';
                document.getElementById('modal-susut').textContent = new Intl.NumberFormat('id-ID').format(susut) + ' gr';
                document.getElementById('modal-total').textContent = new Intl.NumberFormat('id-ID').format(weight + susut) + ' gr';
                document.getElementById('modal-notes').textContent = notes || '-';

                // Show modal
                document.getElementById('confirmationModal').classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeConfirmationModal() {
                document.getElementById('confirmationModal').classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            function submitTransferForm() {
                document.getElementById('transferForm').submit();
            }
        </script>
    @endpush
@endsection
