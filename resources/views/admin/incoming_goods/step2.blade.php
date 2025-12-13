@extends('layouts.app')

@section('title', 'Input Barang Masuk - Step 2')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center">
                <div class="flex items-center">
                    <span class="w-10 h-10 flex items-center justify-center rounded-full bg-green-600 text-white">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    <span class="ml-2 text-sm font-medium text-gray-900">Pilih Supplier & Grade</span>
                </div>
                <div class="w-24 h-0.5 bg-blue-600 mx-4"></div>
                <div class="flex items-center">
                    <span class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-600 text-white font-medium">2</span>
                    <span class="ml-2 text-sm font-medium text-gray-900">Input Berat Nota</span>
                </div>
                <div class="w-24 h-0.5 bg-gray-300 mx-4"></div>
                <div class="flex items-center">
                    <span class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-300 text-gray-500 font-medium">3</span>
                    <span class="ml-2 text-sm font-medium text-gray-500">Input Berat Gudang</span>
                </div>
            </div>
        </div>

        <!-- Info Supplier -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-blue-900">Input untuk Supplier:</h3>
                    <p class="text-lg font-semibold text-blue-900">{{ $supplier->name }}</p>
                </div>
                <div class="text-right text-sm text-blue-700">
                    <p>Tanggal Kedatangan: {{ \Carbon\Carbon::parse($step1Data['receipt_date'])->format('d/m/Y') }}</p>
                    <p>Tanggal Bongkar: {{ \Carbon\Carbon::parse($step1Data['unloading_date'])->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>

        <form action="{{ route('incoming-goods.store-step2') }}" method="POST">
            @csrf

            <!-- Grade Cards -->
            <div class="bg-white rounded-lg shadow-sm border mb-6">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">Input Berat Nota & Kadar Air</h2>
                    <p class="text-sm text-gray-500 mt-1">Masukkan berat nota dari supplier dan kadar air untuk setiap grade</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($grades as $grade)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <!-- Grade Header -->
                            <div class="flex items-center mb-4">
                                @if($grade->image_url)
                                    <img src="{{ $grade->image_url }}" 
                                         alt="{{ $grade->name }}" 
                                         class="w-16 h-16 object-cover rounded-lg mr-3">
                                @else
                                    <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $grade->name }}</h3>
                                </div>
                            </div>

                            <!-- Berat Nota Input -->
                            <div class="mb-4">
                                <label for="berat_awal_{{ $grade->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                                    Berat Nota (gram) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" 
                                       name="berat_awal[{{ $grade->id }}]" 
                                       id="berat_awal_{{ $grade->id }}"
                                       value="{{ old('berat_awal.' . $grade->id) }}"
                                       required
                                       min="0"
                                       step="1"
                                       placeholder="Masukkan berat"
                                       class="w-full rounded-md border border-gray-300 py-2 px-3 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                @error('berat_awal.' . $grade->id)
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Kadar Air Input -->
                            <div>
                                <label for="kadar_air_{{ $grade->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                                    Kadar Air (%) 
                                </label>
                                <input type="number" 
                                       name="kadar_air[{{ $grade->id }}]" 
                                       id="kadar_air_{{ $grade->id }}"
                                       value="{{ old('kadar_air.' . $grade->id) }}"
                                       {{-- required --}}
                                       min="0"
                                       max="100"
                                       step="0.1"
                                       placeholder="Masukkan kadar air"
                                       class="w-full rounded-md border border-gray-300 py-2 px-3 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                @error('kadar_air.' . $grade->id)
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-between">
                <a href="{{ route('incoming-goods.step1') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali
                </a>
                <button type="submit"
                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Lanjut ke Tahap 3
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection