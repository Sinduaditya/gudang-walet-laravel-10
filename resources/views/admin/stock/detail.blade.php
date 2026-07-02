@extends('layouts.app')

@section('title', 'Stok Per Lokasi — ' . $grade->name)

@section('content')
<div class="bg-gray-50 min-h-screen py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">

        {{-- Header --}}
        <div class="flex items-start justify-between mb-6">
            <div>
                <nav class="text-xs text-gray-500 mb-2">
                    <a href="{{ route('tracking-stock.get.grade.company') }}" class="hover:text-gray-700">Tracking Stok</a>
                    <span class="mx-1.5 text-gray-300">/</span>
                    <span class="text-gray-700 font-medium">{{ $grade->name }} — Per Lokasi</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-900">{{ $grade->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">Distribusi stok per lokasi dan supplier.</p>
            </div>
            <a href="{{ session('tracking_stock_referrer') ?? url()->previous() ?? route('tracking-stock.get.grade.company') }}"
                class="flex-shrink-0 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Kembali
            </a>
        </div>

        {{-- Grade Info + Total Stock --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            {{-- Grade Image Card --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 flex items-center gap-4">
                <div class="w-16 h-16 bg-black rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                    @if(!empty($grade->image_url))
                        <img src="{{ asset('storage/' . $grade->image_url) }}" alt="{{ $grade->name }}"
                            class="w-full h-full object-contain p-1">
                    @else
                        <span class="text-gray-500 text-xs text-center">No Image</span>
                    @endif
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Grade</p>
                    <p class="text-lg font-bold text-gray-900 uppercase">{{ $grade->name }}</p>
                    @if($grade->description)
                        <p class="text-xs text-gray-500 mt-0.5">{{ $grade->description }}</p>
                    @endif
                </div>
            </div>

            {{-- Total Stock --}}
            <div class="sm:col-span-2 bg-white border border-gray-200 rounded-lg shadow-sm p-5 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total Stok Global</p>
                    <p class="text-4xl font-bold text-gray-900">
                        {{ number_format($globalStock, 0, ',', '.') }}
                        <span class="text-xl font-normal text-gray-500 ml-1">gram</span>
                    </p>
                </div>
                <div class="text-xs text-gray-400 text-right leading-relaxed max-w-xs">
                    <p>Net dari semua transaksi: GRADING_IN dikurangi SALE_OUT, TRANSFER_OUT, IDM_REGRADING_OUT, ditambah revert.</p>
                </div>
            </div>
        </div>

        {{-- Filter --}}
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4 shadow-sm">
            <div class="flex flex-col sm:flex-row gap-3">
                <form method="GET" action="{{ route('tracking-stock.detail', $grade->id) }}" class="flex gap-3 flex-1">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari lokasi..."
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition-colors">
                        Cari
                    </button>
                    @if(request('search'))
                        <a href="{{ route('tracking-stock.detail', $grade->id) }}"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 transition-colors">
                            Reset
                        </a>
                    @endif
                </form>

                <div class="sm:w-56">
                    <select id="supplierFilter"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Supplier</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Stok Table --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Rincian Stok per Lokasi &amp; Supplier</h3>
                <p class="text-xs text-gray-400">Dikelompokkan per kombinasi lokasi + supplier (FIFO tracking)</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($locationStocks as $stock)
                            @php
                                $supplierName = $stock->supplier->name ?? 'Tanpa Supplier';
                                $locationName = $stock->location->name ?? 'Lokasi Tidak Diketahui';
                            @endphp
                            <tr class="location-row hover:bg-gray-50 transition-colors"
                                data-supplier="{{ strtolower($supplierName) }}">
                                <td class="px-6 py-4 text-sm text-gray-500 row-number">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $locationName }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        {{ $supplierName }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-bold text-gray-900">{{ number_format($stock->total_stock, 0, ',', '.') }}</span>
                                    <span class="text-xs text-gray-400 ml-1">gr</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($stock->total_stock > 0)
                                        <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-green-50 text-green-800 border border-green-100">
                                            Ada
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-red-50 text-red-700 border border-red-100">
                                            Kosong
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr id="noDataRow">
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                    Tidak ada data stok untuk grade ini.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="filterNoData" class="hidden">
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                Tidak ada data supplier yang cocok dengan filter.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const supplierFilter = document.getElementById('supplierFilter');
        const rows = document.querySelectorAll('.location-row');
        const filterNoData = document.getElementById('filterNoData');
        const originalNoData = document.getElementById('noDataRow');

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

        supplierFilter.addEventListener('change', function () {
            const selected = this.value.toLowerCase();
            let visible = 0;
            rows.forEach(row => {
                const match = selected === '' || row.getAttribute('data-supplier') === selected;
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            updateRowNumbers();
            if (filterNoData) filterNoData.classList.toggle('hidden', visible > 0);
        });

        function updateRowNumbers() {
            let n = 1;
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const cell = row.querySelector('.row-number');
                    if (cell) cell.textContent = n++;
                }
            });
        }

        populateSupplierOptions();
    });
</script>
@endsection
