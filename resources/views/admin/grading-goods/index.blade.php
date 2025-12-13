@extends('layouts.app')

@section('title', 'Data Grading Barang')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Data Grading Barang</h1>
                    <p class="mt-1 text-sm text-gray-600">Kelola dan lihat hasil grading barang</p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Export Button - Using current filter -->
                    <a href="{{ route('grading-goods.export', ['month' => request('month'), 'year' => request('year')]) }}"
                        class="flex items-center text-sm text-gray-600 hover:text-gray-800 bg-green-50 hover:bg-green-100 px-3 py-2 rounded-md border border-green-200">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" />
                        </svg>
                        Export Excel
                    </a>

                    <a href="{{ route('grading-goods.step1') }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Grading
                    </a>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                <form method="GET" action="{{ route('grading-goods.index') }}"
                    class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <!-- Filter Bulan -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                            <select name="month"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Semua Bulan</option>
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ sprintf('%02d', $i) }}" {{ request('month') == sprintf('%02d', $i) ? 'selected' : '' }}>
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
                        <a href="{{ route('grading-goods.index') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- ✅ Updated Table with Enhanced Columns -->
            <div class="bg-white shadow-sm border rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Tanggal Grading</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Supplier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Grade Supplier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Berat Nota (gr)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Berat Gudang (gr)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Total Grading (gr)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Selisih (gr)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Persentase (%)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($gradings as $i => $grading)
                                @php
                                    // ✅ Hitung selisih dan persentase
                                    $supplierWeight = $grading->supplier_weight_grams ?? 0;
                                    $warehouseWeight = $grading->warehouse_weight_grams ?? 0;
                                    $totalGradingWeight = $grading->total_grading_weight ?? 0;
                                    $difference = $totalGradingWeight - $warehouseWeight;
                                    $percentage = $warehouseWeight > 0 ? abs($difference / $warehouseWeight) * 100 : 0;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $gradings->firstItem() + $i }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $grading->grading_date ? \Carbon\Carbon::parse($grading->grading_date)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                        {{ $grading->supplier_name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $grading->grade_supplier_name ?? '-' }}
                                        <div class="text-xs text-gray-500">
                                            {{ $grading->total_grades }} grade hasil
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-mono">
                                        {{ number_format($supplierWeight, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-mono">
                                        {{ number_format($warehouseWeight, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-mono font-semibold text-blue-600">
                                        {{ number_format($totalGradingWeight, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @if ($difference < 0)
                                            <div class="flex flex-col">
                                                <span class="text-red-600 font-semibold font-mono">{{ number_format($difference, 0, ',', '.') }}</span>
                                                <span class="text-xs text-red-500">(susut)</span>
                                            </div>
                                        @elseif($difference > 0)
                                            <div class="flex flex-col">
                                                <span class="text-green-600 font-semibold font-mono">+{{ number_format($difference, 0, ',', '.') }}</span>
                                                <span class="text-xs text-green-500">(kelebihan)</span>
                                            </div>
                                        @else
                                            <div class="flex flex-col">
                                                <span class="text-gray-600 font-mono">0</span>
                                                <span class="text-xs text-gray-500">(sama)</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @php
                                            $percentageFormatted = ($percentage == floor($percentage))
                                                ? number_format($percentage, 0, ',', '.')
                                                : number_format($percentage, 1, ',', '.');
                                            
                                            $percentageClass = 'text-gray-600';
                                            if ($percentage > 5) {
                                                $percentageClass = 'text-red-600 font-bold bg-red-50 px-1 py-0.5 rounded';
                                            } elseif ($percentage > 1) {
                                                $percentageClass = 'text-orange-600 font-semibold';
                                            } elseif ($percentage > 0) {
                                                $percentageClass = 'text-green-600';
                                            }
                                        @endphp
                                        <span class="{{ $percentageClass }} font-semibold">
                                            {{ $percentageFormatted }}%
                                            @if($percentage > 5) ⚠️ @endif
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="flex items-center gap-2">
                                            {{-- ✅ FIX: Gunakan receipt_item_id --}}
                                            <a href="{{ route('grading-goods.show', $grading->receipt_item_id) }}"
                                                class="text-blue-600 hover:text-blue-800 font-medium">Detail</a>
                                            <a href="{{ route('grading-goods.edit', $grading->receipt_item_id) }}"
                                                class="text-yellow-600 hover:text-yellow-800 font-medium">Edit</a>
                                            <button onclick="confirmDelete({{ $grading->receipt_item_id }})"
                                                class="text-red-600 hover:text-red-800 font-medium">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                                        @if(request('month') || request('year'))
                                            Tidak ada data grading untuk filter yang dipilih.
                                        @else
                                            Belum ada data grading barang.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($gradings->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $gradings->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-96">
            <h3 class="font-medium mb-4">Hapus Data</h3>
            <p class="text-sm text-gray-600 mb-4">Apakah Anda yakin ingin menghapus data grading ini?</p>
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
                form.action = `grading-goods/delete/${id}`;
                modal.classList.remove('hidden');
            }

            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
            }
        </script>
    @endpush
@endsection