@extends('layouts.app')

@section('title', 'Terima Barang Internal - Konfirmasi')

@section('content')

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Terima Barang Internal</h1>
            <p class="mt-1 text-sm text-gray-600">Konfirmasi data penerimaan barang</p>
        </div>

        <!-- Progress Steps -->
        <div class="mb-8 bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center justify-between max-w-3xl mx-auto">
                <div class="flex flex-col items-center flex-1">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-green-600 text-white shadow-sm">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span class="mt-2 text-xs sm:text-sm font-medium text-gray-900">Data Penerimaan</span>
                </div>
                <div class="flex-1 h-0.5 bg-indigo-600 mx-2 sm:mx-4 -mt-6"></div>
                <div class="flex flex-col items-center flex-1">
                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-indigo-500 text-white font-semibold text-sm shadow-sm">2</div>
                    <span class="mt-2 text-xs sm:text-sm font-medium text-indigo-600">Konfirmasi</span>
                </div>
            </div>
        </div>

        <form action="{{ route('barang.keluar.receive-internal.store') }}" method="POST">
            @csrf

            <!-- Confirmation Card -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-indigo-50">
                    <h2 class="text-lg font-semibold text-gray-900">Detail Penerimaan Internal</h2>
                    <p class="text-sm text-gray-600 mt-1">Periksa kembali data sebelum menyimpan</p>
                </div>

                <div class="p-6">
                    <div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-lg p-6 border-2 border-indigo-200">
                        <div class="space-y-4">
                            <!-- Grade -->
                            <div class="flex items-center justify-between pb-3 border-b border-indigo-200">
                                <span class="text-sm font-medium text-gray-600 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    Grade
                                </span>
                                <span class="font-semibold text-gray-900">{{ $grade->name }}</span>
                            </div>

                            <!-- Transfer Route -->
                            <div class="bg-white rounded-lg p-4 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500 mb-1">Dari</p>
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-2">
                                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900">{{ $fromLocation->name }}</p>
                                            </div>
                                        </div>
                                        <p class="text-xs text-indigo-600 mt-1">(Internal)</p>
                                    </div>
                                    <div class="px-4">
                                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 text-right">
                                        <p class="text-xs text-gray-500 mb-1">Ke</p>
                                        <div class="flex items-center justify-end">
                                            <p class="font-semibold text-gray-900">{{ $toLocation->name }}</p>
                                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center ml-2">
                                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <p class="text-xs text-blue-600 mt-1">(Gudang Utama)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Weight -->
                            <div class="flex items-center justify-between pb-3 border-b border-indigo-200">
                                <span class="text-sm font-medium text-gray-600 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                                    </svg>
                                    Berat Diterima
                                </span>
                                <span class="font-semibold text-indigo-700 text-lg">
                                    {{ number_format($step1Data['weight_grams']) }} gram
                                    <span class="text-sm text-gray-600">
                                        ({{ number_format($step1Data['weight_grams'] / 1000, 2) }} kg)
                                    </span>
                                </span>
                            </div>

                            <!-- Date -->
                            @if(!empty($step1Data['transfer_date']))
                            <div class="flex items-center justify-between pb-3 border-b border-indigo-200">
                                <span class="text-sm font-medium text-gray-600 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Tanggal Penerimaan
                                </span>
                                <span class="font-semibold text-gray-900">
                                    {{ \Carbon\Carbon::parse($step1Data['transfer_date'])->format('d/m/Y') }}
                                </span>
                            </div>
                            @endif

                            <!-- Notes -->
                            @if(!empty($step1Data['notes']))
                            <div>
                                <span class="text-sm font-medium text-gray-600 flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Catatan
                                </span>
                                <p class="text-sm text-gray-700 bg-white rounded p-3">{{ $step1Data['notes'] }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Notice -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h4 class="text-sm font-semibold text-blue-800 mb-1">Informasi</h4>
                        <ul class="text-sm text-blue-700 list-disc list-inside space-y-1">
                            <li>Barang dari <strong>{{ $fromLocation->name }}</strong> akan masuk ke <strong>{{ $toLocation->name }}</strong></li>
                            <li>Stok akan <strong>bertambah</strong> setelah penerimaan dikonfirmasi</li>
                            <li>Data ini akan tercatat dalam sistem inventory</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Hidden Inputs -->
            <input type="hidden" name="grade_company_id" value="{{ $step1Data['grade_company_id'] }}">
            <input type="hidden" name="from_location_id" value="{{ $step1Data['from_location_id'] }}">
            <input type="hidden" name="to_location_id" value="{{ $step1Data['to_location_id'] }}">
            <input type="hidden" name="weight_grams" value="{{ $step1Data['weight_grams'] }}">
            <input type="hidden" name="transfer_date" value="{{ $step1Data['transfer_date'] ?? '' }}">
            <input type="hidden" name="notes" value="{{ $step1Data['notes'] ?? '' }}">

            <!-- Action Buttons -->
            <div class="flex items-center gap-3">
                <a href="{{ route('barang.keluar.receive-internal.step1') }}"
                   class="flex-1 inline-flex items-center justify-center px-6 py-3 border-2 border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali
                </a>
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white font-semibold rounded-lg hover:from-indigo-700 hover:to-indigo-800 focus:ring-4 focus:ring-indigo-300 transition-all duration-200 shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Konfirmasi & Terima Barang
                </button>
            </div>
        </form>
    </div>
</div>

@endsection