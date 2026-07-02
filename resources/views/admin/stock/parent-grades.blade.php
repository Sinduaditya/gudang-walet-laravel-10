@extends('layouts.app')

@section('title', 'Grades — ' . $parentGrade->name)

@section('content')
<div class="bg-gray-50 min-h-screen py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">

        {{-- Header --}}
        <div class="flex items-start justify-between mb-6">
            <div>
                <nav class="text-xs text-gray-500 mb-2">
                    <a href="{{ route('tracking-stock.get.grade.company') }}" class="hover:text-gray-700">Tracking Stok</a>
                    <span class="mx-1.5 text-gray-300">/</span>
                    <span class="text-gray-700 font-medium">{{ $parentGrade->name }}</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-900">{{ $parentGrade->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">Daftar grade dan posisi stok per grade.</p>
            </div>
            <a href="{{ route('tracking-stock.get.grade.company') }}"
                class="flex-shrink-0 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Kembali
            </a>
        </div>

        {{-- Global Stock Summary --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 mb-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                        Stok Global — Semua Grades
                    </p>
                    <p class="text-3xl font-bold text-gray-900">
                        {{ number_format($globalStock, 0, ',', '.') }}
                        <span class="text-base font-normal text-gray-500 ml-1">gram</span>
                    </p>
                </div>
                <div class="text-xs text-gray-400 sm:text-right leading-relaxed">
                    <p>Net dari seluruh transaksi inventori</p>
                    <p class="mt-0.5">Grading + Revert &minus; Penjualan &minus; Transfer &minus; IDM Out + IDM In</p>
                </div>
            </div>
        </div>

        {{-- Search --}}
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4 shadow-sm">
            <form method="GET" action="{{ route('tracking-stock.parent-grades', $parentGrade->id) }}" class="flex gap-3">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama grade..."
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition-colors">
                    Cari
                </button>
                @if(request('search'))
                    <a href="{{ route('tracking-stock.parent-grades', $parentGrade->id) }}"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 transition-colors">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        {{-- Grades Table --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Grade
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Stok Grading
                            <span class="ml-1 font-normal normal-case text-gray-400">(net all transaksi)</span>
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Stok Sortir
                            <span class="ml-1 font-normal normal-case text-gray-400">(bahan masuk sortir)</span>
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($gradeCompanies as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if(!empty($item->image_url))
                                    <img src="{{ asset('storage/' . $item->image_url) }}" alt="{{ $item->name }}"
                                        class="w-9 h-9 rounded-lg object-contain bg-black p-1 flex-shrink-0">
                                @else
                                    <div class="w-9 h-9 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                @endif
                                <span class="text-sm font-semibold text-gray-900 uppercase">{{ $item->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-bold {{ $item->total_stock > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                {{ number_format($item->total_stock, 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-gray-400 ml-1">gr</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-bold {{ $item->sort_stock > 0 ? 'text-orange-700' : 'text-gray-400' }}">
                                {{ number_format($item->sort_stock, 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-gray-400 ml-1">gr</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('tracking-stock.detail', $item->id) }}"
                                    class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 transition-colors">
                                    Per Lokasi
                                </a>
                                <a href="{{ route('tracking-stock.susut', $item->id) }}"
                                    class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-50 transition-colors">
                                    Susut
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">
                            @if(request('search'))
                                Tidak ada grade yang cocok dengan "{{ request('search') }}".
                            @else
                                Belum ada data grade untuk parent ini.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @if($gradeCompanies->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $gradeCompanies->appends(request()->query())->links() }}
            </div>
            @endif
        </div>

    </div>
</div>
@endsection
