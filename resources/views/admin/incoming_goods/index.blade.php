@extends('layouts.app')

@section('title', 'Data Barang Masuk')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Data Barang Masuk</h1>
                    <p class="mt-1 text-sm text-gray-600">Daftar penerimaan barang</p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Export Button - Berpatokan pada filter yang aktif -->
                    <a href="{{ route('incoming-goods.export', request()->query()) }}"
                        class="flex items-center text-sm text-gray-600 hover:text-gray-800 bg-green-50 hover:bg-green-100 px-3 py-2 rounded-md border border-green-200">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" />
                        </svg>
                        Export Excel
                        @if (request('month') || request('year'))
                            <span class="ml-1 text-xs text-blue-600">(Filtered)</span>
                        @endif
                    </a>

                    <a href="{{ route('incoming-goods.step1') }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Input Barang Masuk
                    </a>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                <form method="GET" action="{{ route('incoming-goods.index') }}"
                    class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <!-- Filter Bulan -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                            <select name="month"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Semua Bulan</option>
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <!-- Filter Tahun -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                            <select name="year"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Semua Tahun</option>
                                @for ($year = date('Y'); $year >= 2020; $year--)
                                    <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                            Filter
                        </button>
                        <a href="{{ route('incoming-goods.index') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                            Reset
                        </a>
                    </div>
                </form>

                <!-- Active Filter Display -->
                @if (request('month') || request('year'))
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="flex flex-wrap gap-2 items-center">
                            <span class="text-sm text-gray-600">Filter aktif:</span>

                            @if (request('month'))
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    Bulan: {{ date('F', mktime(0, 0, 0, request('month'), 1)) }}
                                </span>
                            @endif

                            @if (request('year'))
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    Tahun: {{ request('year') }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm border rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Tanggal Kedatangan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Supplier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Tanggal Bongkar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Jumlah Item</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Total Berat Bersih(Gram)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($receipts as $i => $receipt)
                                @php
                                    $hasHighPercentage = $receipt->receiptItems->some(function ($item) {
                                        return abs($item->percentage_difference ?? 0) > 2; 
                                    });
                                @endphp
                                <tr class="{{ $hasHighPercentage ? 'bg-red-50 border-l-4 border-red-500' : '' }}">
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $receipts->firstItem() + $i }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ optional($receipt->receipt_date)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $receipt->supplier->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ optional($receipt->unloading_date)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $receipt->receiptItems->count() }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ number_format($receipt->receiptItems->sum('warehouse_weight_grams')) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        @php
                                            $mentahCount = $receipt->receiptItems->where('status', 'mentah')->count();
                                            $selesaiCount = $receipt->receiptItems
                                                ->where('status', 'selesai_disortir')
                                                ->count();
                                            $totalCount = $receipt->receiptItems->count();
                                        @endphp

                                        @if ($selesaiCount === $totalCount)
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Selesai Disortir
                                            </span>
                                        @elseif($mentahCount === $totalCount)
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Siap Digrading
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Sebagian Disortir
                                            </span>
                                        @endif

                                        @if ($hasHighPercentage)
                                            <br>
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 mt-1"
                                                title="Ada item dengan selisih > 2%">
                                                Selisih Tinggi (>2%)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('incoming-goods.show', $receipt->id) }}"
                                                class="text-blue-600 hover:text-blue-800">Lihat</a>
                                            <a href="{{ route('incoming-goods.edit', $receipt->id) }}"
                                                class="text-yellow-600 hover:text-yellow-800">Edit</a>
                                            <button onclick="confirmDelete({{ $receipt->id }})"
                                                class="text-red-600 hover:text-red-800">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        @if (request('month') || request('year'))
                                            Tidak ada data barang masuk untuk filter yang dipilih.
                                        @else
                                            Belum ada data barang masuk.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($receipts->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $receipts->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-96">
            <h3 class="font-medium mb-4">Hapus Data</h3>
            <p class="text-sm text-gray-600 mb-4">Apakah Anda yakin ingin menghapus penerimaan ini?</p>
            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="flex gap-3">
                    <button type="button" onclick="closeDeleteModal()"
                        class="flex-1 px-3 py-2 bg-gray-200 rounded">Batal</button>
                    <button type="submit" class="flex-1 px-3 py-2 bg-red-600 text-white rounded">Hapus</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function confirmDelete(id) {
                const modal = document.getElementById('deleteModal');
                const form = document.getElementById('deleteForm');
                form.action = `/admin/incoming-goods/${id}`;
                modal.classList.remove('hidden');
            }

            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
            }
        </script>
    @endpush
@endsection