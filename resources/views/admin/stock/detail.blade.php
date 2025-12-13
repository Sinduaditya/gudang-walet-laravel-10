@extends('layouts.app')

@section('title', 'Detail Stok - ' . $grade->name)

@section('content')
<div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">

        {{-- TOMBOL KEMBALI --}}
        <div class="flex justify-end mb-6">
            <a href="{{ route('tracking-stock.get.grade.company') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium transition duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali
            </a>
        </div>

        {{-- SECTION 1: Header Area --}}
        <div class="flex flex-col md:flex-row gap-6 mb-8 items-start">
            {{-- Kartu Grade Info --}}
            <div class="w-64 bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex-shrink-0">
                <div class="relative w-full aspect-square bg-black rounded-xl mb-4 flex items-center justify-center overflow-hidden group shadow-inner">
                    @if(!empty($grade->image_url))
                        <img src="{{ $grade->image_url }}" alt="{{ $grade->name }}" class="w-full h-full object-contain p-4">
                    @else
                        <span class="text-gray-500 text-xs">No Image</span>
                    @endif
                </div>
                <div class="text-center">
                    <h3 class="text-lg font-bold text-gray-900 uppercase leading-tight">{{ $grade->name }}</h3>
                </div>
            </div>

            {{-- Kartu Stok Total --}}
            <div class="flex-1 w-full flex flex-col gap-4">
                <div class="bg-blue-500 rounded-2xl p-6 text-white shadow-md flex flex-col justify-center h-40">
                    <span class="text-sm font-bold tracking-widest uppercase mb-2">TOTAL STOK GLOBAL</span>
                    <div class="flex items-baseline">
                        <span class="text-5xl font-bold">{{ number_format($globalStock, 0, ',', '.') }}</span>
                        <span class="ml-3 text-xl font-medium text-blue-100">Gr</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 2: Search & Filter --}}
        <div class="mb-6">
             <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <form method="GET" action="{{ route('tracking-stock.detail', $grade->id) }}">
                    <div class="flex flex-col lg:flex-row gap-4">
                        {{-- 1. Search Input --}}
                        <div class="w-full lg:w-3/5">
                            <label class="block text-sm text-gray-600 mb-2">Cari Lokasi Stok</label>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari lokasi..." class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        </div>

                        {{-- 2. Filter Supplier --}}
                        <div class="w-full lg:w-2/5">
                            <label for="supplierFilter" class="block text-sm text-gray-600 mb-2">Filter Supplier</label>
                            <select id="supplierFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white">
                                <option value="">Semua Supplier</option>
                                {{-- Options diisi via JS --}}
                            </select>
                        </div>

                        {{-- Button --}}
                        <div class="flex items-end gap-2">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm">Cari</button>
                            <a href="{{ route('tracking-stock.detail', $grade->id) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm">Reset</a>
                        </div>
                    </div>
                </form>
             </div>
        </div>

        {{-- SECTION 3: Table --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-gray-700 font-semibold">Rincian Stok (Lokasi & Supplier)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-16">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lokasi</th>
                            {{-- KOLOM BARU --}}
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stok (Gram)</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($locationStocks as $stock)
                        @php
                            // Ambil nama supplier dari relasi yang sudah di-load di Service
                            $supplierName = $stock->supplier->name ?? 'Tanpa Supplier';
                            $locationName = $stock->location->name ?? 'Lokasi Tidak Diketahui';
                        @endphp

                        <tr class="location-row hover:bg-blue-50 transition-colors duration-150"
                            data-supplier="{{ strtolower($supplierName) }}">

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 row-number">{{ $loop->iteration }}</td>

                            {{-- Lokasi --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $locationName }}</div>
                            </td>

                            {{-- Supplier --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                    {{ $supplierName }}
                                </span>
                            </td>

                            {{-- Stok --}}
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-bold text-gray-900">{{ number_format($stock->total_stock, 0, ',', '.') }}</span>
                                <span class="text-xs text-gray-500 ml-1">Gr</span>
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($stock->total_stock > 0)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Available</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Empty</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr id="noDataRow">
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">Tidak ada data stok.</td>
                        </tr>
                    @endforelse
                    <tr id="filterNoData" class="hidden">
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">Tidak ada supplier yang sesuai filter.</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const supplierFilter = document.getElementById('supplierFilter');
        const rows = document.querySelectorAll('.location-row');
        const filterNoData = document.getElementById('filterNoData');
        const originalNoData = document.getElementById('noDataRow');

        // 1. Populate Dropdown (Otomatis dari data tabel)
        function populateSupplierOptions() {
            const suppliers = new Set();
            rows.forEach(row => {
                const sup = row.getAttribute('data-supplier');
                if (sup) suppliers.add(sup);
            });
            Array.from(suppliers).sort().forEach(supplier => {
                const option = document.createElement('option');
                option.value = supplier;
                option.textContent = supplier.replace(/\b\w/g, l => l.toUpperCase());
                supplierFilter.appendChild(option);
            });
        }

        // 2. Filter Logic
        supplierFilter.addEventListener('change', function() {
            const selectedSupplier = this.value.toLowerCase();
            let visibleCount = 0;

            rows.forEach(row => {
                const rowSupplier = row.getAttribute('data-supplier');
                if (selectedSupplier === '' || rowSupplier === selectedSupplier) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            updateRowNumbers();

            // Handle Empty State
            if (visibleCount === 0) {
                if(filterNoData) filterNoData.classList.remove('hidden');
                if(originalNoData) originalNoData.style.display = 'none';
            } else {
                if(filterNoData) filterNoData.classList.add('hidden');
            }
        });

        // Helper: Update Nomor Urut
        function updateRowNumbers() {
            let number = 1;
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const numCell = row.querySelector('.row-number');
                    if (numCell) numCell.textContent = number++;
                }
            });
        }

        populateSupplierOptions();
    });
</script>
@endsection
