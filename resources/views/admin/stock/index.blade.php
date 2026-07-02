@extends('layouts.app')

@section('title', 'Tracking Stok')

@section('content')
<div class="bg-gray-50 min-h-screen py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Tracking Stok</h1>
            <p class="text-sm text-gray-500 mt-1">
                Stok dihitung real-time dari seluruh transaksi inventori (grading, penjualan, transfer, IDM).
            </p>
        </div>

        {{-- IDM Card --}}
        <div class="mb-6">
            <a href="{{ route('tracking-stock.idm-stocks') }}"
                class="block bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Manajemen IDM — Regrading</p>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">IDM Regrading</h3>
                        <p class="text-sm text-gray-600">
                            Input: IDM A, IDM B &rarr; Output: IDM, KAKIAN, PERUTAN, ALU/AFKIR
                        </p>
                        <div class="mt-3 flex gap-4 text-sm">
                            <span class="text-gray-700"><span class="font-bold text-red-600">6 Grades</span> tracked</span>
                        </div>
                    </div>
                    <svg class="w-8 h-8 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
            </a>
        </div>

        {{-- Search --}}
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 shadow-sm">
            <form method="GET" action="{{ route('tracking-stock.get.grade.company') }}" class="flex gap-3">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari parent grade..."
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition-colors">
                    Cari
                </button>
                @if(request('search'))
                    <a href="{{ route('tracking-stock.get.grade.company') }}"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 transition-colors">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        {{-- Parent Grades Card Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($parentGrades as $parent)
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-900 mb-2">{{ $parent->name }}</h3>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        Parent company dengan breakdown grades dan bahan sortir.
                    </p>
                </div>
                <div class="px-5 py-4 bg-gray-50 border-b border-gray-100">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Grades</p>
                            <p class="text-xl font-bold text-blue-600">{{ $parent->grade_companies_count }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Sortir</p>
                            <p class="text-xl font-bold text-orange-600">{{ $parent->sort_materials_count }}</p>
                        </div>
                    </div>
                </div>
                <div class="px-5 py-4 flex gap-2">
                    <a href="{{ route('tracking-stock.parent-grades', $parent->id) }}"
                        class="flex-1 py-2.5 px-3 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors text-center">
                        Grades
                    </a>
                    <a href="{{ route('tracking-stock.parent-sorts', $parent->id) }}"
                        class="flex-1 py-2.5 px-3 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors text-center">
                        Sortir
                    </a>
                </div>
            </div>
            @empty
            <div class="col-span-1 md:col-span-2 lg:col-span-3 bg-white border border-dashed border-gray-300 rounded-lg p-12 text-center">
                <p class="text-sm text-gray-500">
                    @if(request('search'))
                        Tidak ada parent grade yang cocok dengan "{{ request('search') }}".
                    @else
                        Belum ada Parent Grade Company.
                    @endif
                </p>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($parentGrades->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $parentGrades->appends(request()->query())->links() }}
        </div>
        @endif

    </div>
</div>
@endsection
