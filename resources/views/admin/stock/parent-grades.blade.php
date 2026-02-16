@extends('layouts.app')

@section('title', 'Tracking Stok - ' . $parentGrade->name)

@section('content')
    <div class="py-8 px-4" style="background-color: #f8f9fa;">

        <div class="max-w-7xl mx-auto">

            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Tracking Stok</h1>
                    <p class="text-gray-600">List Grade untuk Parent: <span
                            class="font-semibold text-blue-600">{{ $parentGrade->name }}</span></p>
                </div>
                <a href="{{ route('tracking-stock.get.grade.company') }}"
                    class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Index
                </a>
            </div>

            <!-- Global Stock Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-50 rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium uppercase tracking-wider">Stok Global (All Grades)</p>
                        <h3 class="text-3xl font-extrabold text-gray-900 mt-1">
                            {{ number_format($globalStock, 0, ',', '.') }} <span
                                class="text-lg text-gray-500 font-medium">gram</span>
                        </h3>
                    </div>
                </div>
            </div>


            <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 shadow-sm">
                <form method="GET" action="{{ route('tracking-stock.parent-grades', $parentGrade->id) }}">
                    <div class="flex flex-col lg:flex-row gap-4">
                        <div class="flex-1">
                            <label class="flex items-center text-sm text-gray-600 mb-2">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Cari Grade
                            </label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Cari berdasarkan nama grade..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        </div>

                        <div class="flex items-end gap-2">
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium transition duration-200 whitespace-nowrap">
                                Cari
                            </button>

                            <a href="{{ route('tracking-stock.parent-grades', $parentGrade->id) }}"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium transition duration-200 whitespace-nowrap">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>

                @if (request('search'))
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <span class="text-sm text-gray-600">Menampilkan hasil untuk:</span>
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 ml-2">
                            "{{ request('search') }}"
                        </span>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                @forelse($gradeCompanies as $item)
                    {{-- Card Item --}}
                    <div
                        class="bg-white rounded-2xl shadow-sm hover:shadow-lg hover:-translate-y-2 transition-all duration-300">
                        {{-- Area Gambar --}}
                        <div class="relative p-3">
                            <div class="bg-black rounded-xl flex items-center justify-center overflow-hidden"
                                style="height: 200px;">
                                @if (!empty($item->image_url))
                                    <img src="{{ asset('storage/' . $item->image_url) }}" alt="{{ $item->name }}"
                                        class="max-h-full max-w-full object-contain">
                                @else
                                    <img src="{{ asset('storage/sarang_walet.png') }}" alt="{{ $item->name }}"
                                        class="max-h-full max-w-full object-contain">
                                @endif
                            </div>
                        </div>

                        {{-- Nama Grade --}}
                        <div class="text-center px-4 py-3">
                            <h6 class="font-bold uppercase text-sm tracking-wide text-gray-800">
                                {{ $item->name }}
                            </h6>
                        </div>

                        <div class="border-t border-gray-200 mx-4"></div>

                        {{-- Tombol Aksi --}}
                        <div class="px-4 py-4">
                            <div class="flex flex-col gap-2">
                                <a href="{{ route('tracking-stock.detail', $item->id) }}"
                                    class="w-full text-center py-2.5 px-4 text-sm font-medium bg-blue-500 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                    Detail
                                </a>
                                <a href="{{ route('tracking-stock.susut', $item->id) }}"
                                    class="w-full text-center py-2.5 px-4 text-sm font-medium bg-green-500 text-white rounded-lg hover:bg-green-700 transition-colors duration-200">
                                    Tracking Stok
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div
                        class="col-span-1 md:col-span-2 lg:col-span-5 text-center py-12 bg-white rounded-lg border border-dashed border-gray-300">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-2 text-gray-500">
                            @if (request('search'))
                                Tidak ada data yang cocok dengan "{{ request('search') }}".
                            @else
                                Data grade belum tersedia untuk parent ini.
                            @endif
                        </p>
                    </div>
                @endforelse
            </div>

            @if ($gradeCompanies->hasPages())
                <div class="mt-6 flex justify-center">
                    {{ $gradeCompanies->appends(request()->query())->links() }}
                </div>
            @endif

        </div>
    </div>
@endsection