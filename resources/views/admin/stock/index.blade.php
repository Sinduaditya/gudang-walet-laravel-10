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

        {{-- IDM Banner --}}
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-0.5">Manajemen IDM — Regrading</p>
                <p class="text-sm text-gray-700">6 bin: IDM A, IDM B &rarr; IDM, KAKIAN, PERUTAN, ALU/AFKIR</p>
            </div>
            <a href="{{ route('tracking-stock.idm-stocks') }}"
                class="flex-shrink-0 ml-6 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                Lihat IDM
            </a>
        </div>

        {{-- Search --}}
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4 shadow-sm">
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

        {{-- Parent Grades Table --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Parent Grade
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Grades
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Item Sortir
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($parentGrades as $parent)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <span class="text-sm font-semibold text-gray-900">{{ $parent->name }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                {{ $parent->grade_companies_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-50 text-orange-700 border border-orange-100">
                                {{ $parent->sort_materials_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('tracking-stock.parent-grades', $parent->id) }}"
                                    class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 transition-colors">
                                    Grades
                                </a>
                                <a href="{{ route('tracking-stock.parent-sorts', $parent->id) }}"
                                    class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-50 transition-colors">
                                    Sortir
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">
                            @if(request('search'))
                                Tidak ada parent grade yang cocok dengan "{{ request('search') }}".
                            @else
                                Belum ada Parent Grade Company.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @if($parentGrades->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $parentGrades->appends(request()->query())->links() }}
            </div>
            @endif
        </div>

    </div>
</div>
@endsection
