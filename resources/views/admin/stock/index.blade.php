@extends('layouts.app')

@section('title', 'Tracking Stok')

@section('content')
<div class="py-8 px-4" style="background-color: #f8f9fa;">

    <div class="max-w-7xl mx-auto">

            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Tracking stok</h1>
                <p class="text-gray-600">Lihat stok barang</p>
            </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 shadow-sm">
            <form method="GET" action="{{ route('tracking-stock.get.grade.company') }}">
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

                        <a href="{{ route('tracking-stock.get.grade.company') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium transition duration-200 whitespace-nowrap">
                            Reset
                        </a>
                    </div>
                </div>
            </form>

            @if (request('search'))
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <span class="text-sm text-gray-600">Menampilkan hasil untuk:</span>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 ml-2">
                        "{{ request('search') }}"
                    </span>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            @forelse($trackingStocks as $item)
                {{-- Card Item --}}
                <div class="bg-white rounded-2xl shadow-sm hover:shadow-lg hover:-translate-y-2 transition-all duration-300">

                    {{-- Area Gambar --}}
                    <div class="relative p-3">
                        <div class="bg-black rounded-xl flex items-center justify-center overflow-hidden"
                             style="height: 200px;">
                            @if(!empty($item->image_url))
                                <img src="{{ $item->image_url }}"
                                     alt="{{ $item->name }}"
                                     class="max-h-full max-w-full object-contain">
                            @else
                                <div class="text-white text-sm">No Image</div>
                            @endif
                        </div>
                        <div class="absolute top-5 right-5 w-7 h-7 bg-white rounded-full shadow-sm border border-gray-200 cursor-pointer"></div>
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
                {{-- State Kosong --}}
                <div class="col-span-1 md:col-span-2 lg:col-span-5 text-center py-12 bg-white rounded-lg border border-dashed border-gray-300">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2 text-gray-500">
                        @if(request('search'))
                            Tidak ada data yang cocok dengan "{{ request('search') }}".
                        @else
                            Data stok belum tersedia.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>

        @if($trackingStocks->hasPages())
            <div class="mt-6 flex justify-center">
                {{-- appends(request()->query()) PENTING agar search tidak hilang saat klik page 2 --}}
                {{ $trackingStocks->appends(request()->query())->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
