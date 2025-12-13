@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Back Button --}}
        <div class="mb-6">
            <a href="{{ route('barang.keluar.index') }}"
                class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Menu Utama
            </a>
        </div>

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Penjualan Langsung</h1>
            <p class="text-gray-600">Catat penjualan barang dengan mengurangi stok</p>
        </div>

        {{-- Form Penjualan --}}
        <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-8">
            <form action="{{ route('barang.keluar.sell.store') }}" method="POST">
                @csrf
                {{-- Form fields here --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Your form fields --}}
                </div>
                <div class="mt-6 flex justify-end gap-4">
                    <button type="reset" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Reset
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Simpan Penjualan
                    </button>
                </div>
            </form>
        </div>

        {{-- Riwayat Penjualan --}}
        <x-transaction-history-table
            title="Riwayat Penjualan Langsung"
            :transactions="$penjualanTransactions"
            type="sale"
            :showFilter="true"
        />

    </div>
</div>
@endsection
