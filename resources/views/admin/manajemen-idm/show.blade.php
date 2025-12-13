@extends('layouts.app')

@section('title', 'Detail IDM')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Detail IDM</h1>
                    <p class="text-sm text-gray-600">ID: #{{ $idmManagement->id }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('manajemen-idm.index') }}"
                        class="inline-flex items-center px-3 py-2 bg-gray-100 rounded hover:bg-gray-200 text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Kembali
                    </a>
                </div>
            </div>

            <!-- ✅ Informasi Barang Asal -->
            <div class="bg-white shadow rounded-lg border mb-6 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Informasi Barang Asal</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Supplier</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $idmManagement->supplier->name ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Kategori IDM</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium {{ ($idmManagement->sourceItems->first()->category_grade ?? '') == 'IDM A' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $idmManagement->sourceItems->first()->category_grade ?? '-' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Grade Company</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $idmManagement->gradeCompany->name ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Tanggal Grading</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ \Carbon\Carbon::parse($idmManagement->grading_date)->format('d/m/Y') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Berat Awal</p>
                        <p class="text-lg font-bold text-blue-600">
                            {{ number_format($idmManagement->initial_weight, 2) }} gr
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Susut</p>
                        <p class="text-lg font-bold text-red-600">
                            {{ number_format($idmManagement->shrinkage, 2) }} gr
                        </p>
                    </div>
                </div>
            </div>

            <!-- ✅ Detail Hasil IDM -->
            <div class="bg-white shadow rounded-lg border overflow-hidden mb-6">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">Detail Hasil IDM</h2>
                </div>

                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-left text-xs font-medium text-gray-500 bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 rounded-tl-md">No</th>
                                    <th class="px-4 py-3">Jenis</th>
                                    <th class="px-4 py-3">Berat (gr)</th>
                                    <th class="px-4 py-3">Harga Satuan</th>
                                    <th class="px-4 py-3 rounded-tr-md">Total Harga</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($idmManagement->details as $index => $detail)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $index + 1 }}</td>
                                        <td class="px-4 py-3 text-gray-900 font-medium capitalize">
                                            {{ $detail->grade_idm_name }}
                                        </td>
                                        <td class="px-4 py-3 font-mono text-gray-700">
                                            {{ number_format($detail->weight, 2) }}
                                        </td>
                                        <td class="px-4 py-3 font-mono text-gray-700">
                                            Rp {{ number_format($detail->price, 0, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 font-mono font-semibold text-gray-900">
                                            Rp {{ number_format($detail->total_price, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ✅ Ringkasan Keuangan -->
            <div class="bg-white shadow rounded-lg border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan Keuangan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Total Estimasi Harga Jual -->
                    <div class="bg-gradient-to-r from-green-50 to-green-100 p-4 rounded-lg border border-green-200">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <div class="text-sm text-green-600 font-medium">Total Estimasi Harga Jual per Gram</div>
                                <div class="text-2xl font-bold text-green-900">
                                    Rp {{ number_format($idmManagement->estimated_selling_price, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Keuntungan -->
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <div>
                                <div class="text-sm text-blue-600 font-medium">Keuntungan</div>
                                <div class="text-2xl font-bold text-blue-900">
                                    @php
                                        $totalInitialCost = $idmManagement->initial_weight * $idmManagement->initial_price;
                                        $profit = max(0, $idmManagement->estimated_selling_price - $totalInitialCost);
                                    @endphp
                                    Rp {{ number_format($profit, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
