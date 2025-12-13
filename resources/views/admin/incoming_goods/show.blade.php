@extends('layouts.app')

@section('title', 'Detail Barang Masuk')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Detail Penerimaan</h1>
                    <p class="text-sm text-gray-600">ID: #{{ $receipt->id }}</p>
                    <p class="text-xs text-gray-500">
                        Dibuat: {{ $receipt->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('incoming-goods.edit', $receipt->id) }}"
                        class="inline-flex items-center px-3 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </a>
                    <a href="{{ route('incoming-goods.index') }}"
                        class="inline-flex items-center px-3 py-2 bg-gray-100 rounded hover:bg-gray-200 text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Kembali
                    </a>
                </div>
            </div>

            <!-- Receipt Info Card -->
            <div class="bg-white shadow rounded-lg border mb-6 p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Supplier</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $receipt->supplier->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Tanggal Kedatangan</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ optional($receipt->receipt_date)->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Tanggal Bongkar</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ optional($receipt->unloading_date)->format('d/m/Y') }}</p>
                    </div>
                </div>
                @if ($receipt->notes)
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-sm text-gray-500 font-medium mb-1">Catatan</p>
                        <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-md">{{ $receipt->notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Items Table -->
            <div class="bg-white shadow rounded-lg border overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Item Penerimaan</h2>
                        <span class="text-sm text-gray-600">{{ $receipt->receiptItems->count() }} item</span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-left text-xs font-medium text-gray-500 bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 rounded-tl-md">Grade</th>
                                    <th class="px-4 py-3">Berat Supplier (gr)</th>
                                    <th class="px-4 py-3">Berat Gudang (gr)</th>
                                    <th class="px-4 py-3">Selisih (gr)</th>
                                    <th class="px-4 py-3">Rasio Desimal</th>
                                    <th class="px-4 py-3">Persentase (%)</th>
                                    <th class="px-4 py-3">Kadar Air (%)</th>
                                    <th class="px-4 py-3 rounded-tr-md">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($receipt->receiptItems as $item)
                                    <tr
                                        class="hover:bg-gray-50 transition-colors {{ $item->isPercentageAboveThreshold() ? 'bg-red-50 border-l-4 border-red-500' : '' }}">
                                        <td class="px-4 py-3 font-semibold text-gray-900">
                                            {{ $item->gradeSupplier->name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 font-mono text-gray-700">
                                            {{ number_format($item->supplier_weight_grams) }}
                                        </td>
                                        <td class="px-4 py-3 font-mono text-gray-700">
                                            {{ number_format($item->warehouse_weight_grams) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($item->difference_grams < 0)
                                                <div class="flex flex-col">
                                                    <span
                                                        class="text-red-600 font-semibold font-mono">{{ number_format($item->difference_grams, 0, ',', '.') }}</span>
                                                    <span class="text-xs text-red-500">(susut)</span>
                                                </div>
                                            @elseif($item->difference_grams > 0)
                                                <div class="flex flex-col">
                                                    <span
                                                        class="text-green-600 font-semibold font-mono">+{{ number_format($item->difference_grams, 0, ',', '.') }}</span>
                                                    <span class="text-xs text-green-500">(kelebihan)</span>
                                                </div>
                                            @else
                                                <div class="flex flex-col">
                                                    <span
                                                        class="text-gray-600 font-mono">{{ number_format($item->difference_grams, 0, ',', '.') }}</span>
                                                    <span class="text-xs text-gray-500">(sama)</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $decimal =
                                                    $item->supplier_weight_grams > 0
                                                        ? $item->difference_grams / $item->supplier_weight_grams
                                                        : 0;
                                                $decimalFormatted = number_format($decimal, 3, ',', '.'); // ✅ 3 desimal, koma sebagai desimal

                                                $decimalClass = 'text-gray-600';
                                                $warningIcon = '';

                                                // ✅ UPDATED: Ganti threshold dari 5% ke 2%
                                                if (abs($decimal) > 0.02) {
                                                    // 2%
                                                    $decimalClass =
                                                        'text-red-600 font-bold bg-red-100 px-2 py-1 rounded-md';
                                                    $warningIcon = ' ⚠️';
                                                } elseif (abs($decimal) > 0.01) {
                                                    // 1%
                                                    $decimalClass = 'text-orange-600 font-semibold';
                                                } elseif ($decimal != 0) {
                                                    $decimalClass = 'text-green-600';
                                                }
                                            @endphp
                                            <span class="{{ $decimalClass }} font-mono text-lg">
                                                {{ $decimalFormatted }}{{ $warningIcon }}
                                            </span>
                                        </td>
                                        {{-- ✅ UPDATED: Kolom Persentase threshold 2% --}}
                                        <td class="px-4 py-3">
                                            @php
                                                $percentage =
                                                    $item->supplier_weight_grams > 0
                                                        ? abs($item->difference_grams / $item->supplier_weight_grams) *
                                                            100
                                                        : 0;

                                                // ✅ Format: bulat jika bilangan bulat, 1 desimal jika tidak
                                                $percentageFormatted =
                                                    $percentage == floor($percentage)
                                                        ? number_format($percentage, 0, ',', '.')
                                                        : number_format($percentage, 1, ',', '.');

                                                $percentageClass = 'text-gray-600';
                                                $percentWarningIcon = '';

                                                // ✅ UPDATED: Ganti threshold dari 5% ke 2%
                                                if ($percentage > 2) {
                                                    // 2%
                                                    $percentageClass =
                                                        'text-red-600 font-bold bg-red-100 px-2 py-1 rounded-md';
                                                    $percentWarningIcon = ' ⚠️';
                                                } elseif ($percentage > 1) {
                                                    // 1%
                                                    $percentageClass = 'text-orange-600 font-semibold';
                                                } elseif ($percentage > 0) {
                                                    $percentageClass = 'text-green-600';
                                                }
                                            @endphp
                                            <span class="{{ $percentageClass }} font-semibold">
                                                {{ $percentageFormatted }}%{{ $percentWarningIcon }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if ($item->moisture_percentage)
                                                <span
                                                    class="font-mono text-gray-700">{{ $item->moisture_percentage }}%</span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($item->status === 'mentah')
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                    </svg>
                                                    Mentah
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                    Selesai Disortir
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- ✅ UPDATED: Alert jika ada persentase di atas 2% --}}
                    @php
                        $highPercentageItems = $receipt->receiptItems->filter(function ($item) {
                            return $item->isPercentageAboveThreshold(); // 2% threshold
                        });

                        $totalDifference = $receipt->receiptItems->sum('difference_grams');
                        $totalSupplier = $receipt->receiptItems->sum('supplier_weight_grams');
                        $overallPercentage = $totalSupplier > 0 ? ($totalDifference / $totalSupplier) * 100 : 0;
                    @endphp

                    {{-- ✅ Enhanced Summary Statistics --}}
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan Penerimaan</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Total Items -->
                            <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
                                <div class="flex items-center">
                                    <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14-4H9m4 8H9m-4-4h.01M6 16h.01" />
                                    </svg>
                                    <div>
                                        <div class="text-sm text-blue-600 font-medium">Total Items</div>
                                        <div class="text-2xl font-bold text-blue-900">{{ $receipt->receiptItems->count() }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                             <!-- Total Berat Supplier -->
                            <div class="bg-gradient-to-r from-gray-50 to-gray-100 p-4 rounded-lg border border-gray-200">
                                <div class="flex items-center">
                                    <svg class="w-8 h-8 text-gray-600 mr-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div>
                                        <div class="text-sm text-gray-600 font-medium">Total Berat Supplier</div>
                                        <div class="text-2xl font-bold text-gray-900">
                                            {{ number_format($receipt->receiptItems->sum('supplier_weight_grams')) }}<span
                                                class="text-sm text-gray-600 ml-1">gr</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Berat Gudang -->
                            <div
                                class="bg-gradient-to-r from-green-50 to-green-100 p-4 rounded-lg border border-green-200">
                                <div class="flex items-center">
                                    <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    <div>
                                        <div class="text-sm text-green-600 font-medium">Total Berat Gudang</div>
                                        <div class="text-2xl font-bold text-green-900">
                                            {{ number_format($receipt->receiptItems->sum('warehouse_weight_grams')) }}<span
                                                class="text-sm text-green-600 ml-1">gr</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Selisih -->
                            <div
                                class="bg-gradient-to-r from-{{ $totalDifference < 0 ? 'red' : 'green' }}-50 to-{{ $totalDifference < 0 ? 'red' : 'green' }}-100 p-4 rounded-lg border border-{{ $totalDifference < 0 ? 'red' : 'green' }}-200">
                                <div class="flex items-center">
                                    <svg class="w-8 h-8 text-{{ $totalDifference < 0 ? 'red' : 'green' }}-600 mr-3"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if ($totalDifference < 0)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                        @endif
                                    </svg>
                                    <div>
                                        <div
                                            class="text-sm text-{{ $totalDifference < 0 ? 'red' : 'green' }}-600 font-medium">
                                            Total Selisih
                                            <span
                                                class="text-xs">({{ number_format(abs($overallPercentage), 1) }}%)</span>
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-{{ $totalDifference < 0 ? 'red' : 'green' }}-900">
                                            {{ $totalDifference > 0 ? '+' : '' }}{{ number_format($totalDifference) }}<span
                                                class="text-sm ml-1">gr</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection