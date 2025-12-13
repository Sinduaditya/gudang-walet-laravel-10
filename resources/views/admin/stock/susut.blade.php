@extends('layouts.app')

@section('title', 'Tracking Susut - ' . $grade->name)

@section('content')
<div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">

        {{-- TOMBOL KEMBALI --}}
        <div class="flex justify-end mb-6">
            <a href="{{ route('tracking-stock.get.grade.company', $grade->id) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium transition duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali
            </a>
        </div>

        {{-- SECTION 1: Header Grade Info --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm mb-8">
            <div class="flex flex-col md:flex-row gap-6 items-center md:items-start">

                {{-- KARTU KIRI: Grade Image (Fixed Size w-64 seperti request sebelumnya) --}}
                <div class="w-64 bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex-shrink-0">
                    <div class="relative w-full aspect-square bg-black rounded-xl mb-4 flex items-center justify-center overflow-hidden group shadow-inner">
                        {{-- Badge Centang --}}
                        <div class="absolute top-2 right-2 bg-white rounded-full p-1 shadow-sm z-10">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>

                        @if(!empty($grade->image_url))
                            <img src="{{ $grade->image_url }}" alt="{{ $grade->name }}" class="w-full h-full object-contain p-4 transition-transform duration-300 group-hover:scale-110">
                        @else
                            <div class="flex flex-col items-center text-gray-500 text-xs"><span>No Image</span></div>
                        @endif
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-bold text-gray-900 uppercase leading-tight">{{ $grade->name }}</h3>
                        <p class="text-sm text-gray-500 mt-1">Grade Quality</p>
                    </div>
                </div>

                {{-- KANAN: Judul & Deskripsi Singkat --}}
                <div class="flex-1 flex flex-col justify-center h-full pt-4 text-center md:text-left">
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">Laporan Tracking Susut</h1>
                    <p class="text-gray-600 max-w-2xl">
                        Halaman ini menampilkan riwayat transfer barang yang memiliki nilai penyusutan gramasi.
                        Data difilter khusus untuk grade <strong>{{ $grade->name }}</strong>.
                    </p>
                </div>
            </div>
        </div>

        {{-- SECTION 2: Filters --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 mb-8">
            <form method="GET" action="{{ route('tracking-stock.susut', $grade->id) }}">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">

                    {{-- Filter 1: Tanggal Transfer --}}
                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Transfer</label>
                        <input type="date" name="date" id="date" value="{{ request('date') }}"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>

                    {{-- Filter 2: Dari Lokasi --}}
                    <div>
                        <label for="from_location_id" class="block text-sm font-medium text-gray-700 mb-1">Dari Lokasi</label>
                        <select name="from_location_id" id="from_location_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Semua Lokasi</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ request('from_location_id') == $loc->id ? 'selected' : '' }}>
                                    {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter 3: Ke Lokasi --}}
                    <div>
                        <label for="to_location_id" class="block text-sm font-medium text-gray-700 mb-1">Ke Lokasi</label>
                        <select name="to_location_id" id="to_location_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Semua Lokasi</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ request('to_location_id') == $loc->id ? 'selected' : '' }}>
                                    {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex gap-2">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition duration-200 flex-1 md:flex-none justify-center">
                            Filter
                        </button>
                       <a href="{{ route('tracking-stock.susut', $grade->id) }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium transition duration-200 whitespace-nowrap">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- SECTION 3: Table Data --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h3 class="text-gray-700 font-semibold">Riwayat Transfer</h3>
                <span class="text-sm text-gray-500 bg-white border border-gray-200 px-3 py-1 rounded-full">
                    Total Data: {{ $stockTransfers->total() }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">No</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Transfer</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Susut (Gram)</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transfer (Dari &rarr; Ke)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($stockTransfers as $index => $transfer)
                            <tr class="hover:bg-blue-50 transition-colors duration-150">
                                {{-- 1. Nomor --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $stockTransfers->firstItem() + $index }}
                                </td>

                                {{-- 2. Tanggal Transfer --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ \Carbon\Carbon::parse($transfer->transfer_date)->translatedFormat('d F Y') }}
                                </td>

                                {{-- 3. Susut Grams --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-bold {{ $transfer->susut_grams > 0 ? 'text-red-600' : 'text-gray-700' }}">
                                        {{ number_format($transfer->susut_grams, 0, ',', '.') }}
                                    </span>
                                    <span class="text-xs text-gray-500 ml-1">Gr</span>
                                </td>

                                {{-- 4. Transfer Info (From -> To) --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex items-center">
                                        <span class="text-gray-700 font-medium bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                                            {{ $transfer->fromLocation->name ?? 'Unknown' }}
                                        </span>

                                        <svg class="w-4 h-4 mx-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                        </svg>

                                        <span class="text-green-700 font-bold bg-green-50 px-2 py-0.5 rounded border border-green-100">
                                            {{ $transfer->toLocation->name ?? 'Unknown' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <p class="font-medium text-gray-900">Tidak ada data ditemukan</p>
                                        <p class="mt-1 text-gray-500">Coba ubah filter pencarian Anda.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($stockTransfers->hasPages())
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    {{ $stockTransfers->appends(request()->query())->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
