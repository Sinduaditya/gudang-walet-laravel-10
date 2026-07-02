@extends('layouts.app')

@section('title', 'Tracking Susut — ' . $grade->name)

@section('content')
<div class="bg-gray-50 min-h-screen py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">

        {{-- Header --}}
        <div class="flex items-start justify-between mb-6">
            <div>
                <nav class="text-xs text-gray-500 mb-2">
                    <a href="{{ route('tracking-stock.get.grade.company') }}" class="hover:text-gray-700">Tracking Stok</a>
                    <span class="mx-1.5 text-gray-300">/</span>
                    <span class="text-gray-700 font-medium">{{ $grade->name }} — Susut</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-900">Laporan Susut: {{ $grade->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Riwayat transfer yang memiliki nilai penyusutan gramasi (susut_grams &gt; 0).
                </p>
            </div>
            <a href="{{ route('tracking-stock.get.grade.company', $grade->id) }}"
                class="flex-shrink-0 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Kembali
            </a>
        </div>

        {{-- Grade Info Card --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 mb-6 flex items-center gap-4">
            <div class="w-14 h-14 bg-black rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                @if(!empty($grade->image_url))
                    <img src="{{ asset('storage/' . $grade->image_url) }}" alt="{{ $grade->name }}"
                        class="w-full h-full object-contain p-1">
                @else
                    <span class="text-gray-500 text-xs">No Image</span>
                @endif
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Grade</p>
                <p class="text-lg font-bold text-gray-900 uppercase">{{ $grade->name }}</p>
            </div>
            <div class="ml-auto text-right">
                <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-0.5">Total Records</p>
                <p class="text-lg font-bold text-gray-900">{{ $stockTransfers->total() }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 mb-6">
            <form method="GET" action="{{ route('tracking-stock.susut', $grade->id) }}">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                    <div>
                        <label for="date" class="block text-xs font-medium text-gray-600 mb-1.5">Tanggal Transfer</label>
                        <input type="date" name="date" id="date" value="{{ request('date') }}"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="from_location_id" class="block text-xs font-medium text-gray-600 mb-1.5">Dari Lokasi</label>
                        <select name="from_location_id" id="from_location_id"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua Lokasi</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ request('from_location_id') == $loc->id ? 'selected' : '' }}>
                                    {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="to_location_id" class="block text-xs font-medium text-gray-600 mb-1.5">Ke Lokasi</label>
                        <select name="to_location_id" id="to_location_id"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua Lokasi</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ request('to_location_id') == $loc->id ? 'selected' : '' }}>
                                    {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                            Filter
                        </button>
                        <a href="{{ route('tracking-stock.susut', $grade->id) }}"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors whitespace-nowrap">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Riwayat Transfer</h3>
                <span class="text-xs text-gray-500 bg-white border border-gray-200 px-3 py-1 rounded-full">
                    {{ $stockTransfers->total() }} records
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Berat Transfer</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Susut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dari &rarr; Ke</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($stockTransfers as $index => $transfer)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $stockTransfers->firstItem() + $index }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{ \Carbon\Carbon::parse($transfer->transfer_date)->translatedFormat('d F Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-semibold text-gray-900">
                                    {{ number_format($transfer->weight_grams, 0, ',', '.') }}
                                </span>
                                <span class="text-xs text-gray-400 ml-1">gr</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-bold {{ $transfer->susut_grams > 0 ? 'text-red-600' : 'text-gray-500' }}">
                                    {{ number_format($transfer->susut_grams, 0, ',', '.') }}
                                </span>
                                <span class="text-xs text-gray-400 ml-1">gr</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-700 border border-gray-200 rounded text-xs font-medium">
                                        {{ $transfer->fromLocation->name ?? 'Unknown' }}
                                    </span>
                                    <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                    <span class="px-2 py-0.5 bg-green-50 text-green-700 border border-green-100 rounded text-xs font-bold">
                                        {{ $transfer->toLocation->name ?? 'Unknown' }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                <p class="font-medium text-gray-900 mb-1">Tidak ada data ditemukan</p>
                                <p>Coba ubah filter atau belum ada transfer untuk grade ini.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($stockTransfers->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $stockTransfers->appends(request()->query())->links() }}
            </div>
            @endif
        </div>

    </div>
</div>
@endsection
