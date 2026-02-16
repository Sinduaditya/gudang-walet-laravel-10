@extends('layouts.app')

@section('title', 'Tracking Stok - Parent Grades')

@section('content')
    <div class="py-8 px-4" style="background-color: #f8f9fa;">

        <div class="max-w-7xl mx-auto">

            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Tracking Stok</h1>
                <p class="text-gray-600">Pilih Parent Grade Company untuk melihat stok grades atau bahan sortir.</p>
            </div>

            <!-- Search Section -->
            <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 shadow-sm">
                <form method="GET" action="{{ route('tracking-stock.get.grade.company') }}">
                    <div class="flex flex-col lg:flex-row gap-4">
                        <div class="flex-1">
                            <label class="flex items-center text-sm text-gray-600 mb-2">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Cari Parent Grade
                            </label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Cari Parent Grade..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        </div>

                        <div class="flex items-end gap-2">
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium transition duration-200 whitespace-nowrap">
                                Cari
                            </button>

                            <a href="{{ route('tracking-stock.get.grade.company') }}"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium transition duration-200 whitespace-nowrap">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @forelse($parentGrades as $parent)
                    {{-- Card 1: Child Grades --}}
                    <div
                        class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 overflow-hidden group">
                        <div class="p-5">
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                        </path>
                                    </svg>
                                </div>
                                <span
                                    class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-blue-200">
                                    {{ $parent->grade_companies_count }} Grades
                                </span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 mb-1 group-hover:text-blue-600 transition-colors">
                                {{ $parent->name }}
                            </h3>
                            <p class="text-sm text-gray-500 mb-4">
                                Lihat daftar grade company
                            </p>
                            <a href="{{ route('tracking-stock.parent-grades', $parent->id) }}"
                                class="block w-full text-center py-2 px-4 bg-gray-50 hover:bg-blue-50 text-gray-700 hover:text-blue-700 rounded-lg text-sm font-medium transition-colors border border-gray-200 hover:border-blue-200">
                                Buka Detail
                            </a>
                        </div>
                    </div>

                    {{-- Card 2: Sort Materials --}}
                    <div
                        class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 overflow-hidden group">
                        <div class="p-5">
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-12 h-12 rounded-lg bg-orange-50 flex items-center justify-center text-orange-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                                        </path>
                                    </svg>
                                </div>
                                <span
                                    class="bg-orange-100 text-orange-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-orange-200">
                                    {{ $parent->sort_materials_count }} Item
                                </span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 mb-1 group-hover:text-orange-600 transition-colors">
                                {{ $parent->name }} (Sort)
                            </h3>
                            <p class="text-sm text-gray-500 mb-4">
                                Lihat data bahan sortir
                            </p>
                            <a href="{{ route('tracking-stock.parent-sorts', $parent->id) }}"
                                class="block w-full text-center py-2 px-4 bg-gray-50 hover:bg-orange-50 text-gray-700 hover:text-orange-700 rounded-lg text-sm font-medium transition-colors border border-gray-200 hover:border-orange-200">
                                Buka Detail
                            </a>
                        </div>
                    </div>
                @empty
                    <div
                        class="col-span-1 md:col-span-2 lg:col-span-4 text-center py-12 bg-white rounded-lg border border-dashed border-gray-300">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-2 text-gray-500">Belum ada Parent Grade Company.</p>
                    </div>
                @endforelse
            </div>

            @if ($parentGrades->hasPages())
                <div class="mt-6 flex justify-center">
                    {{ $parentGrades->appends(request()->query())->links() }}
                </div>
            @endif

        </div>
    </div>
@endsection