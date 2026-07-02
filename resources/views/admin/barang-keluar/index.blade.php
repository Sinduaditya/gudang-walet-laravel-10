@extends('layouts.app')

@section('title', 'Barang Keluar')

@section('content')
<div class="bg-gray-50 min-h-screen py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Barang Keluar</h1>
            <p class="text-sm text-gray-500 mt-1">
                Pilih jenis aktivitas sesuai dengan operasional gudang Anda.
            </p>
        </div>

        {{-- Barang Keluar Section --}}
        <div class="mb-10">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-red-500"></div>
                    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Barang Keluar dari Gudang</h2>
                </div>
                <div class="flex-1 h-px bg-gray-200"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- Penjualan Langsung --}}
                <a href="{{ route('barang.keluar.sell.form') }}"
                    class="group bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md hover:border-blue-300 transition-all duration-200 p-5">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-blue-600 transition-colors duration-200">
                            <svg class="w-5 h-5 text-blue-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-sm group-hover:text-blue-600 transition-colors">Jual ke Pembeli</h3>
                            <p class="text-xs text-gray-500 mt-1">Catat penjualan barang dari stok ke customer</p>
                        </div>
                    </div>
                    <div class="text-xs text-gray-400 bg-gray-50 rounded px-2 py-1.5 inline-block">
                        Ada transaksi pembayaran dari pelanggan
                    </div>
                </a>

                {{-- Transfer Eksternal --}}
                <a href="{{ route('barang.keluar.transfer.step1') }}"
                    class="group bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md hover:border-purple-300 transition-all duration-200 p-5 relative">
                    <div class="absolute top-3 right-3">
                        <span class="inline-block bg-purple-100 text-purple-700 text-xs font-semibold px-2.5 py-1 rounded">
                            EKSTERNAL
                        </span>
                    </div>
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-purple-600 transition-colors duration-200">
                            <svg class="w-5 h-5 text-purple-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-sm group-hover:text-purple-600 transition-colors">Pindah ke Gudang Lain</h3>
                            <p class="text-xs text-gray-500 mt-1">Transfer antar lokasi gudang (IDM, DMK, atau gudang lain)</p>
                        </div>
                    </div>
                    <div class="text-xs text-gray-400 bg-gray-50 rounded px-2 py-1.5 inline-block">
                        Barang pindah ke lokasi gudang yang berbeda
                    </div>
                </a>

                {{-- Transfer Internal (Jasa Cuci) --}}
                <a href="{{ route('barang.keluar.external-transfer.step1') }}"
                    class="group bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md hover:border-orange-300 transition-all duration-200 p-5 relative">
                    <div class="absolute top-3 right-3">
                        <span class="inline-block bg-orange-100 text-orange-700 text-xs font-semibold px-2.5 py-1 rounded">
                            INTERNAL
                        </span>
                    </div>
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-orange-600 transition-colors duration-200">
                            <svg class="w-5 h-5 text-orange-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7M12 3v18" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-sm group-hover:text-orange-600 transition-colors">Kirim ke Jasa Cuci</h3>
                            <p class="text-xs text-gray-500 mt-1">Kirim barang untuk proses pencucian (layanan in-house)</p>
                        </div>
                    </div>
                    <div class="text-xs text-gray-400 bg-gray-50 rounded px-2 py-1.5 inline-block">
                        Barang tetap di bawah kontrol perusahaan
                    </div>
                </a>

            </div>
        </div>

        {{-- Barang Kembali Section --}}
        <div>
            <div class="mb-4 flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Barang Kembali ke Gudang</h2>
                </div>
                <div class="flex-1 h-px bg-gray-200"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- Kembali dari Jasa Cuci --}}
                <a href="{{ route('barang.keluar.receive-external.step1') }}"
                    class="group bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md hover:border-green-300 transition-all duration-200 p-5">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-green-600 transition-colors duration-200">
                            <svg class="w-5 h-5 text-green-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-sm group-hover:text-green-600 transition-colors">Terima dari Jasa Cuci</h3>
                            <p class="text-xs text-gray-500 mt-1">Terima barang yang sudah selesai dicuci</p>
                        </div>
                    </div>
                    <div class="text-xs text-gray-400 bg-gray-50 rounded px-2 py-1.5 inline-block">
                        Lakukan setelah "Kirim ke Jasa Cuci"
                    </div>
                </a>

            </div>
        </div>

    </div>
</div>
@endsection
