@extends('layouts.app')

@section('title', 'Detail Grading Barang')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Detail Grading Barang</h1>
                    <p class="text-sm text-gray-600">Item ID: #{{ $grading->receiptItem->id ?? '-' }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('grading-goods.index') }}"
                        class="inline-flex items-center px-3 py-2 bg-gray-100 rounded hover:bg-gray-200 text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Kembali
                    </a>
                    <a href="{{ route('grading-goods.edit', $grading->receiptItem->id) }}"
                        class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </a>
                </div>
            </div>

            <!-- ✅ Informasi Barang Asal -->
            <div class="bg-white shadow rounded-lg border mb-6 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Informasi Barang Asal</h2>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Supplier</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $grading->receiptItem->purchaseReceipt->supplier->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Grade Supplier</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $grading->receiptItem->gradeSupplier->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Tanggal Kedatangan</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ optional($grading->receiptItem->purchaseReceipt->receipt_date)->format('d/m/Y') ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Berat Nota Supplier</p>
                        <p class="text-lg font-bold text-orange-600">
                            {{ number_format($grading->receiptItem->supplier_weight_grams ?? 0, 0, ',', '.') }} gr
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Berat Barang di Gudang</p>
                        <p class="text-lg font-bold text-blue-600">
                            {{ number_format($grading->receiptItem->warehouse_weight_grams ?? 0, 0, ',', '.') }} gr
                        </p>
                    </div>
                </div>
            </div>

            <!-- ✅ Hasil Grading Detail -->
            <div class="bg-white shadow rounded-lg border overflow-hidden mb-6">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Detail Hasil Grading</h2>
                        <span class="text-sm text-gray-600">{{ $allGradingResults->count() }} grade hasil</span>
                    </div>
                </div>

                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-left text-xs font-medium text-gray-500 bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 rounded-tl-md">No</th>
                                    <th class="px-4 py-3">Tanggal Grading</th>
                                    <th class="px-4 py-3">Grade Company</th>
                                    <th class="px-4 py-3">Kategori</th>
                                    <th class="px-4 py-3">Jenis Keluar</th>
                                    <th class="px-4 py-3">Jumlah Item</th>
                                    <th class="px-4 py-3 rounded-tr-md">Berat Hasil (gr)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @php
                                    $totalGradingWeight = 0;
                                    $warehouseWeight = $grading->receiptItem->warehouse_weight_grams ?? 0;
                                @endphp
                                @foreach ($allGradingResults as $index => $result)
                                    @php
                                        $totalGradingWeight += $result->weight_grams ?? 0;
                                    @endphp
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $index + 1 }}</td>
                                        <td class="px-4 py-3 text-gray-700">
                                            {{ \Carbon\Carbon::parse($result->grading_date)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $result->gradeCompany->name ?? '-' }}
                                            </span>
                                        </td>
                                        <!-- ✅ Kolom Kategori -->
                                        <td class="px-4 py-3">
                                            @if ($result->category_grade)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                                    @if($result->category_grade == 'IDM A') bg-indigo-100 text-indigo-800
                                                    @else bg-indigo-100 text-indigo-800 @endif">
                                                    {{ $result->category_grade }}
                                                </span>
                                            @else
                                                <span class="text-gray-400 text-xs">-</span>
                                            @endif
                                        </td>
                                        <!-- ✅ Kolom Jenis Keluar -->
                                        <td class="px-4 py-3">
                                            @if ($result->outgoing_type)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                    @if($result->outgoing_type == 'penjualan_langsung') bg-green-100 text-green-800
                                                    @elseif($result->outgoing_type == 'internal') bg-blue-100 text-blue-800
                                                    @else bg-orange-100 text-orange-800 @endif">
                                                    @switch($result->outgoing_type)
                                                        @case('penjualan_langsung')
                                                            Penjualan Langsung
                                                        @break
                                                        @case('internal')
                                                            Internal
                                                        @break
                                                        @case('external')
                                                            External
                                                        @break
                                                        @default
                                                            {{ $result->outgoing_type }}
                                                    @endswitch
                                                </span>
                                            @else
                                                <span class="text-gray-400 text-xs">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-mono text-gray-700">
                                            {{ number_format($result->quantity ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 font-mono font-semibold text-green-600">
                                            {{ number_format($result->weight_grams ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- ✅ Catatan Grading -->
                    @if ($grading->notes)
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Catatan Grading</h3>
                            <p class="text-sm text-gray-600">{{ $grading->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- ✅ Distribusi Kategori & Jenis Keluar -->
            <div class="bg-white shadow rounded-lg border p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Distribusi Berdasarkan Kategori & Jenis Keluar</h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    <!-- ✅ Kategori Grade Statistics -->
                    <div>
                        <h4 class="text-md font-medium text-gray-700 mb-3">Berdasarkan Kategori Grade</h4>
                        <div class="space-y-3">
                            @php
                                $categoryStats = $allGradingResults->groupBy('category_grade');
                                $totalWeight = $allGradingResults->sum('weight_grams');
                            @endphp
                            
                            @foreach(['IDM A', 'IDM B', null] as $category)
                                @php
                                    $items = $categoryStats->get($category, collect());
                                    $weight = $items->sum('weight_grams');
                                    $count = $items->count();
                                    $percentage = $totalWeight > 0 ? ($weight / $totalWeight) * 100 : 0;
                                @endphp
                                
                                @if($count > 0)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 rounded-full mr-3
                                                @if($category == 'IDM A') bg-purple-500
                                                @elseif($category == 'IDM B') bg-indigo-500  
                                                @else bg-gray-400 @endif">
                                            </div>
                                            <span class="font-medium text-gray-700">
                                                {{ $category ?? 'Tidak Dikategorikan' }}
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-semibold text-gray-900">{{ number_format($weight, 0, ',', '.') }} gr</div>
                                            <div class="text-xs text-gray-500">{{ $count }} item ({{ number_format($percentage, 1) }}%)</div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <!-- ✅ Outgoing Type Statistics -->
                    <div>
                        <h4 class="text-md font-medium text-gray-700 mb-3">Berdasarkan Jenis Barang Keluar</h4>
                        <div class="space-y-3">
                            @php
                                $outgoingStats = $allGradingResults->groupBy('outgoing_type');
                            @endphp
                            
                            @foreach(['penjualan_langsung', 'internal', 'external', null] as $type)
                                @php
                                    $items = $outgoingStats->get($type, collect());
                                    $weight = $items->sum('weight_grams');
                                    $count = $items->count();
                                    $percentage = $totalWeight > 0 ? ($weight / $totalWeight) * 100 : 0;
                                @endphp
                                
                                @if($count > 0)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 rounded-full mr-3
                                                @if($type == 'penjualan_langsung') bg-green-500
                                                @elseif($type == 'internal') bg-blue-500
                                                @elseif($type == 'external') bg-orange-500
                                                @else bg-gray-400 @endif">
                                            </div>
                                            <span class="font-medium text-gray-700">
                                                @switch($type)
                                                    @case('penjualan_langsung')
                                                        Penjualan Langsung
                                                        @break
                                                    @case('internal')
                                                        Internal
                                                        @break
                                                    @case('external')
                                                        External
                                                        @break
                                                    @default
                                                        Tidak Ditentukan
                                                @endswitch
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-semibold text-gray-900">{{ number_format($weight, 0, ',', '.') }} gr</div>
                                            <div class="text-xs text-gray-500">{{ $count }} item ({{ number_format($percentage, 1) }}%)</div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ Ringkasan Total -->
            <div class="bg-white shadow rounded-lg border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan Grading</h3>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    @php
                        $totalDifference = $totalGradingWeight - $warehouseWeight;
                        $totalPercentage = $warehouseWeight > 0 ? abs($totalDifference / $warehouseWeight) * 100 : 0;
                        $notaWeight = $grading->receiptItem->supplier_weight_grams ?? 0;
                    @endphp

                    <!-- Total Grade Results -->
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-4H9m4 8H9m-4-4h.01M6 16h.01" />
                            </svg>
                            <div>
                                <div class="text-sm text-blue-600 font-medium">Total Grade Results</div>
                                <div class="text-2xl font-bold text-blue-900">{{ $allGradingResults->count() }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Berat Gudang -->
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 p-4 rounded-lg border border-gray-200">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16l-3-9l3-9z" />
                            </svg>
                            <div>
                                <div class="text-sm text-gray-600 font-medium">Berat Gudang</div>
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ number_format($warehouseWeight, 0, ',', '.') }}<span class="text-sm text-gray-600 ml-1">gr</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Berat Nota -->
                    <div class="bg-gradient-to-r from-orange-50 to-orange-100 p-4 rounded-lg border border-orange-200">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-orange-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <div>
                                <div class="text-sm text-orange-600 font-medium">Berat Nota</div>
                                <div class="text-2xl font-bold text-orange-900">
                                    {{ number_format($notaWeight, 0, ',', '.') }}<span class="text-sm text-orange-600 ml-1">gr</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Berat Grading -->
                    <div class="bg-gradient-to-r from-green-50 to-green-100 p-4 rounded-lg border border-green-200">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <div>
                                <div class="text-sm text-green-600 font-medium">Total Berat Grading</div>
                                <div class="text-2xl font-bold text-green-900">
                                    {{ number_format($totalGradingWeight, 0, ',', '.') }}<span class="text-sm text-green-600 ml-1">gr</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Selisih -->
                    <div class="bg-gradient-to-r from-{{ $totalDifference < 0 ? 'red' : 'green' }}-50 to-{{ $totalDifference < 0 ? 'red' : 'green' }}-100 p-4 rounded-lg border border-{{ $totalDifference < 0 ? 'red' : 'green' }}-200">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-{{ $totalDifference < 0 ? 'red' : 'green' }}-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if ($totalDifference < 0)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                @endif
                            </svg>
                            <div>
                                <div class="text-sm text-{{ $totalDifference < 0 ? 'red' : 'green' }}-600 font-medium">
                                    Total Selisih
                                    <span class="text-xs">({{ number_format($totalPercentage, 1, ',', '.') }}%)</span>
                                </div>
                                <div class="text-2xl font-bold text-{{ $totalDifference < 0 ? 'red' : 'green' }}-900">
                                    {{ $totalDifference > 0 ? '+' : '' }}{{ number_format($totalDifference, 0, ',', '.') }}<span class="text-sm ml-1">gr</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection