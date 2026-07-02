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
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 shadow-sm">
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

        {{-- Grades Card Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($gradeCompanies as $item)
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 flex flex-col">

                {{-- Image Area --}}
                <div class="bg-black rounded-t-lg aspect-square flex items-center justify-center overflow-hidden p-3">
                    @if(!empty($item->image_url))
                        <img src="{{ asset('storage/' . $item->image_url) }}" alt="{{ $item->name }}"
                            class="max-h-full max-w-full object-contain">
                    @else
                        <div class="flex items-center justify-center w-full h-full text-gray-500 text-xs">
                            <span>No Image</span>
                        </div>
                    @endif
                </div>

                {{-- Grade Name --}}
                <div class="px-4 py-3 border-b border-gray-100">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-1">{{ $item->name }}</h3>
                    @if($item->description)
                        <p class="text-xs text-gray-500">{{ Str::limit($item->description, 40) }}</p>
                    @endif
                </div>

                {{-- Stock Breakdown --}}
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex-1">
                    <div class="space-y-2">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-medium text-gray-500">Stok Grading</span>
                            <span class="text-sm font-bold {{ $item->total_stock > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                {{ number_format($item->total_stock, 0, ',', '.') }}
                                <span class="text-xs font-normal text-gray-400">gr</span>
                            </span>
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-medium text-gray-500">Stok Sortir</span>
                            <span class="text-sm font-bold {{ $item->sort_stock > 0 ? 'text-orange-700' : 'text-gray-400' }}">
                                {{ number_format($item->sort_stock, 0, ',', '.') }}
                                <span class="text-xs font-normal text-gray-400">gr</span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="px-4 py-3 flex gap-2">
                    <a href="{{ route('tracking-stock.detail', $item->id) }}"
                        class="flex-1 py-2 px-2 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 transition-colors text-center">
                        Per Lokasi
                    </a>
                    <a href="{{ route('tracking-stock.susut', $item->id) }}"
                        class="flex-1 py-2 px-2 bg-white border border-gray-300 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-50 transition-colors text-center">
                        Susut
                    </a>
                </div>

            </div>
            @empty
            <div class="col-span-1 sm:col-span-2 lg:col-span-3 xl:col-span-4 bg-white border border-dashed border-gray-300 rounded-lg p-12 text-center">
                <p class="text-sm text-gray-500">
                    @if(request('search'))
                        Tidak ada grade yang cocok dengan "{{ request('search') }}".
                    @else
                        Belum ada data grade untuk parent ini.
                    @endif
                </p>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($gradeCompanies->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $gradeCompanies->appends(request()->query())->links() }}
        </div>
        @endif

    </div>
</div>
@endsection
