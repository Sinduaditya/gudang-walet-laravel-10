@extends('layouts.app')

@section('title', 'Terima Barang Internal - Step 1')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header with Back Button and History Tab Toggle --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Terima Barang Internal</h1>
                <p class="mt-1 text-sm text-gray-600">Terima barang kembali dari lokasi internal (IDM/DMK)</p>
            </div>

            <div class="flex items-center gap-3">
                {{-- Tab Toggle Button --}}
                <button type="button"
                        onclick="toggleHistoryTab()"
                        id="historyToggleBtn"
                        class="inline-flex items-center px-4 py-2 border border-indigo-300 text-sm font-medium rounded-lg text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span id="historyToggleText">Riwayat Penerimaan Internal</span>
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
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-indigo-500 text-white font-semibold text-base shadow-sm">
                        1
                    </div>
                    <span class="mt-2 text-xs sm:text-sm font-medium text-indigo-600">
                        Data Penerimaan
                    </span>
                </div>

                <div class="flex-1 h-0.5 bg-gray-200 mx-2 sm:mx-4 -mt-6"></div>

                <div class="flex flex-col items-center flex-1">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-400 font-semibold text-base">
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
                    <div class="px-6 py-4 border-b border-gray-200 bg-indigo-50">
                        <h2 class="text-lg font-semibold text-gray-900">Informasi Penerimaan Internal</h2>
                        <p class="text-sm text-gray-500 mt-1">Terima barang dari lokasi internal (IDM/DMK) kembali ke Gudang Utama</p>
                    </div>

                    <form action="{{ route('barang.keluar.receive-internal.store-step1') }}" method="POST" class="p-6">
                        @csrf

                         <div class="space-y-6">
                            {{-- Grade --}}
                            <div>
                                <label class="block font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    Grade Perusahaan <span class="text-red-500">*</span>
                                </label>
                                <select name="grade_company_id" id="gradeSelect" required
                                        class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    <option value="">-- Pilih Grade --</option>
                                    @foreach ($grades as $grade)
                                        <option value="{{ $grade->id }}"
                                            {{ old('grade_company_id') == $grade->id ? 'selected' : '' }}>
                                            {{ $grade->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('grade_company_id')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                {{-- ✅ Stock Info Display --}}
                                <div id="stockInfo" class="hidden mt-3 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="flex-grow">
                                            <h4 class="text-sm font-semibold text-blue-800 mb-2">Stok Tersedia di Lokasi Internal</h4>
                                            <div id="stockLoading" class="hidden">
                                                <div class="flex items-center gap-2 text-sm text-blue-600">
                                                    <svg class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                    </svg>
                                                    Mengecek stok...
                                                </div>
                                            </div>
                                            <div id="stockContent">
                                                <!-- Content akan diisi via JavaScript -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Locations --}}
                            <div class="relative">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Lokasi Asal (Internal) <span class="text-red-500">*</span>
                                        </label>
                                        <select name="from_location_id" id="fromLocationSelect" required
                                                class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                            <option value="">-- Pilih Lokasi Asal --</option>
                                            @foreach ($locations as $loc)
                                                <option value="{{ $loc->id }}"
                                                    {{ old('from_location_id') == $loc->id ? 'selected' : '' }}>
                                                    {{ $loc->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1.5 text-xs text-gray-500">
                                            <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                      clip-rule="evenodd" />
                                            </svg>
                                            Lokasi internal seperti IDM/DMK
                                        </p>
                                        @error('from_location_id')
                                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                        @enderror

                                        {{-- ✅ Specific Location Stock Display --}}
                                        <div id="specificLocationStock" class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span class="text-sm font-medium text-green-800">Stok Tersedia:</span>
                                                </div>
                                                <div id="specificStockAmount" class="text-sm font-bold text-green-700">
                                                    <!-- Will be filled by JavaScript -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                            </svg>
                                            Lokasi Tujuan <span class="text-red-500">*</span>
                                        </label>

                                        <div class="w-full border border-gray-200 bg-gray-100 rounded-lg p-3 text-gray-700 flex items-center">
                                            <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                            </svg>
                                            <span class="font-semibold">Gudang Utama</span>
                                        </div>

                                        <p class="mt-1.5 text-xs text-gray-500">
                                            <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                      clip-rule="evenodd" />
                                            </svg>
                                            Barang akan masuk ke Gudang Utama (otomatis)
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Weight & Date --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                        </svg>
                                        Berat Diterima (gram) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="weight_grams" id="weightInput" value="{{ old('weight_grams') }}"
                                           step="0.01" min="0.01" placeholder="Masukkan berat dalam gram" required
                                           class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    
                                    {{-- ✅ Real-time Stock Validation --}}
                                    <div id="stockValidation" class="hidden mt-2">
                                        <!-- Will be filled by JavaScript -->
                                    </div>

                                    @error('weight_grams')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Tanggal Penerimaan
                                        <span class="text-gray-400 font-normal text-xs">(Opsional)</span>
                                    </label>
                                    <input type="date" name="transfer_date"
                                           value="{{ old('transfer_date', date('Y-m-d')) }}"
                                           class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    @error('transfer_date')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label class="block font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Catatan
                                    <span class="text-gray-400 font-normal text-xs">(Opsional)</span>
                                </label>
                                <textarea name="notes" rows="3" placeholder="Tambahkan catatan atau keterangan penerimaan internal..."
                                          class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition resize-none">{{ old('notes') }}</textarea>
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
                            <button type="submit"
                                    class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white font-semibold rounded-lg hover:from-indigo-700 hover:to-indigo-800 focus:ring-4 focus:ring-indigo-300 transition-all duration-200 shadow-lg hover:shadow-xl">
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
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Riwayat Penerimaan Internal</h2>
                                <p class="text-sm text-gray-500 mt-1">Daftar penerimaan barang dari lokasi internal</p>
                            </div>
                            <button onclick="toggleHistoryTab()"
                                    class="text-sm text-gray-600 hover:text-gray-800 flex items-center gap-1 px-3 py-1.5 hover:bg-gray-100 rounded transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Tutup
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Tanggal
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Grade
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Penerimaan
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Stok Bertambah
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($receiveInternalTransactions as $tx)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ \Carbon\Carbon::parse($tx->transaction_date)->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $tx->gradeCompany->name ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @php
                                                $stockTransfer = $tx->stockTransfer;
                                            @endphp
                                            @if($stockTransfer && $stockTransfer->fromLocation)
                                                <div class="flex items-center">
                                                    <span class="text-gray-700">{{ $stockTransfer->fromLocation->name }}</span>
                                                    <svg class="w-4 h-4 mx-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                    </svg>
                                                    <span class="text-indigo-700 font-medium">{{ $tx->location->name ?? '-' }}</span>
                                                </div>
                                            @else
                                                <span class="text-gray-700">{{ $tx->location->name ?? '-' }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <div class="text-sm font-semibold text-indigo-600">
                                                +{{ number_format(abs($tx->quantity_change_grams), 2) }} gr
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                ({{ number_format(abs($tx->quantity_change_grams) / 1000, 2) }} kg)
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                                    </svg>
                                                </div>
                                                <p class="text-gray-500 font-medium">Belum ada riwayat penerimaan internal</p>
                                                <p class="text-gray-400 text-sm mt-1">Transaksi akan muncul setelah Anda menerima barang dari internal</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($receiveInternalTransactions->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-600">
                                    Menampilkan {{ $receiveInternalTransactions->firstItem() }} - {{ $receiveInternalTransactions->lastItem() }} dari {{ $receiveInternalTransactions->total() }} transaksi
                                </div>
                                <div>
                                    {{ $receiveInternalTransactions->links() }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
    let currentStockData = {};
    let availableStockAtLocation = 0;

    // ✅ Function untuk load stok ketika grade dipilih
    function loadStockForGrade(gradeId, fromLocationId = null) {
        if (!gradeId) {
            document.getElementById('stockInfo').classList.add('hidden');
            return;
        }

        const stockInfo = document.getElementById('stockInfo');
        const stockLoading = document.getElementById('stockLoading');
        const stockContent = document.getElementById('stockContent');

        stockInfo.classList.remove('hidden');
        stockLoading.classList.remove('hidden');
        stockContent.innerHTML = '';

        const url = new URL('{{ route("barang.keluar.receive-internal.stock_check") }}');
        url.searchParams.append('grade_company_id', gradeId);
        if (fromLocationId) {
            url.searchParams.append('from_location_id', fromLocationId);
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                stockLoading.classList.add('hidden');
                
                if (data.success) {
                    currentStockData = data;
                    displayStockInfo(data);
                    updateSpecificLocationStock();
                    validateWeight();
                } else {
                    stockContent.innerHTML = `<div class="text-sm text-red-600">${data.message}</div>`;
                }
            })
            .catch(error => {
                stockLoading.classList.add('hidden');
                stockContent.innerHTML = '<div class="text-sm text-red-600">Error loading stock data</div>';
                console.error('Error:', error);
            });
    }

    // ✅ Display stok info
    function displayStockInfo(data) {
        const stockContent = document.getElementById('stockContent');
        
        if (!data.has_stock) {
            stockContent.innerHTML = `
                <div class="text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded p-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium">Tidak ada stok untuk grade ${data.grade_name} di lokasi internal</span>
                    </div>
                </div>
            `;
            return;
        }

        let html = `
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-blue-100 border border-blue-200 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-semibold text-blue-800">Total Stok ${data.grade_name}:</span>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-blue-700">${data.formatted_total_stock}</div>
                        <div class="text-sm text-blue-600">(${data.total_stock_kg} kg)</div>
                    </div>
                </div>
        `;

        if (data.locations.length > 1) {
            html += `<div class="text-xs font-medium text-gray-600 mb-2">Rincian per lokasi:</div>`;
            
            data.locations.forEach(location => {
                html += `
                    <div class="flex items-center justify-between p-2 bg-white border border-gray-200 rounded text-sm">
                        <span class="text-gray-700">${location.location_name}</span>
                        <span class="font-medium text-gray-900">${location.formatted_stock} (${location.stock_kg} kg)</span>
                    </div>
                `;
            });
        }

        html += `</div>`;
        stockContent.innerHTML = html;
    }

    // ✅ Update stok spesifik lokasi
    function updateSpecificLocationStock() {
        const fromLocationSelect = document.getElementById('fromLocationSelect');
        const specificLocationStock = document.getElementById('specificLocationStock');
        const specificStockAmount = document.getElementById('specificStockAmount');

        const selectedLocationId = fromLocationSelect.value;
        
        if (!selectedLocationId || !currentStockData.locations) {
            specificLocationStock.classList.add('hidden');
            availableStockAtLocation = 0;
            return;
        }

        const locationStock = currentStockData.locations.find(
            loc => loc.location_id == selectedLocationId
        );

        if (locationStock) {
            availableStockAtLocation = locationStock.stock_grams;
            specificStockAmount.textContent = `${locationStock.formatted_stock} (${locationStock.stock_kg} kg)`;
            specificLocationStock.classList.remove('hidden');
        } else {
            availableStockAtLocation = 0;
            specificLocationStock.classList.add('hidden');
        }

        validateWeight();
    }

    // ✅ Validasi berat input
    function validateWeight() {
        const weightInput = document.getElementById('weightInput');
        const stockValidation = document.getElementById('stockValidation');
        
        const inputWeight = parseFloat(weightInput.value) || 0;
        
        if (!availableStockAtLocation || inputWeight <= 0) {
            stockValidation.classList.add('hidden');
            return;
        }

        stockValidation.classList.remove('hidden');
        
        if (inputWeight > availableStockAtLocation) {
            // ❌ Tidak cukup stok
            stockValidation.innerHTML = `
                <div class="flex items-center gap-2 p-2 bg-red-50 border border-red-200 rounded text-red-700 text-sm">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span>Stok tidak cukup! Tersedia: <strong>${availableStockAtLocation.toLocaleString()} gram</strong></span>
                </div>
            `;
        } else {
            // ✅ Stok mencukupi
            const remaining = availableStockAtLocation - inputWeight;
            stockValidation.innerHTML = `
                <div class="flex items-center gap-2 p-2 bg-green-50 border border-green-200 rounded text-green-700 text-sm">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>Stok cukup! Sisa setelah pengambilan: <strong>${remaining.toLocaleString()} gram</strong></span>
                </div>
            `;
        }
    }

    // ✅ Event Listeners
    document.addEventListener('DOMContentLoaded', function() {
        const gradeSelect = document.getElementById('gradeSelect');
        const fromLocationSelect = document.getElementById('fromLocationSelect');
        const weightInput = document.getElementById('weightInput');

        // Grade selection change
        gradeSelect.addEventListener('change', function() {
            loadStockForGrade(this.value);
        });

        // Location selection change  
        fromLocationSelect.addEventListener('change', function() {
            updateSpecificLocationStock();
        });

        // Weight input change
        weightInput.addEventListener('input', function() {
            validateWeight();
        });

        // Load initial stock if grade is already selected
        if (gradeSelect.value) {
            loadStockForGrade(gradeSelect.value);
        }
    });
    
    function toggleHistoryTab() {
        const formTab = document.getElementById('formTab');
        const historyTab = document.getElementById('historyTab');
        const toggleBtn = document.getElementById('historyToggleBtn');
        const toggleText = document.getElementById('historyToggleText');
        const progressSteps = document.getElementById("progressSteps");

        if (historyTab.classList.contains('hidden')) {
            // Show history, hide form
            formTab.classList.add('hidden');
            historyTab.classList.remove('hidden');
            toggleText.textContent = 'Kembali ke Form';
            toggleBtn.classList.remove('bg-indigo-50', 'text-indigo-700', 'border-indigo-300');
            toggleBtn.classList.add('bg-gray-100', 'text-gray-700', 'border-gray-300');
            progressSteps.classList.add('hidden');
        } else {
            // Show form, hide history
            historyTab.classList.add('hidden');
            formTab.classList.remove('hidden');
            toggleText.textContent = 'Riwayat Penerimaan Internal';
            toggleBtn.classList.remove('bg-gray-100', 'text-gray-700', 'border-gray-300');
            toggleBtn.classList.add('bg-indigo-50', 'text-indigo-700', 'border-indigo-300');
            progressSteps.classList.remove('hidden');
        }
    }

    // Check if there's a page parameter (from pagination), if yes, show history tab
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('page')) {
            toggleHistoryTab();
        }
    });
</script>
@endpush
@endsection