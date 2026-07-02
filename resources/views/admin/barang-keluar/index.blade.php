@extends('layouts.app')

@section('content')
<div class="bg-white min-h-screen">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="mb-10">
            <h1 class="text-2xl font-bold text-gray-900">Pergerakan Barang</h1>
            <p class="mt-1 text-gray-500 text-sm">Pilih jenis aktivitas sesuai dengan apa yang sedang terjadi di gudang.</p>
        </div>

        {{-- Section: Barang Keluar --}}
        <div class="mb-10">
            <div class="flex items-center gap-3 mb-5">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-red-400"></div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Barang Keluar dari Gudang</span>
                </div>
                <div class="flex-1 h-px bg-gray-200"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                {{-- Penjualan Langsung --}}
                <a href="{{ route('barang.keluar.sell.form') }}"
                   class="group relative bg-white rounded-2xl border border-gray-200 p-6 hover:border-blue-400 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0 w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center group-hover:bg-blue-500 transition-colors duration-200">
                            <svg class="w-5 h-5 text-blue-500 group-hover:text-white transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors duration-200">Jual ke Pembeli</h3>
                            <p class="mt-1 text-sm text-gray-500 leading-relaxed">Barang dibeli oleh pelanggan dan langsung keluar dari stok gudang.</p>
                            <div class="mt-3 inline-flex items-center gap-1 text-xs text-gray-400 bg-gray-50 rounded-lg px-2.5 py-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Ada transaksi pembayaran dari pelanggan
                            </div>
                        </div>
                    </div>
                    <div class="absolute bottom-5 right-5 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </div>
                </a>

                {{-- Transfer Internal --}}
                <a href="{{ route('barang.keluar.transfer.step1') }}"
                   class="group relative bg-white rounded-2xl border border-gray-200 p-6 hover:border-purple-400 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0 w-11 h-11 bg-purple-50 rounded-xl flex items-center justify-center group-hover:bg-purple-500 transition-colors duration-200">
                            <svg class="w-5 h-5 text-purple-500 group-hover:text-white transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900 group-hover:text-purple-600 transition-colors duration-200">Pindah ke Gudang Lain</h3>
                            <p class="mt-1 text-sm text-gray-500 leading-relaxed">Barang dipindahkan ke lokasi internal lain seperti IDM atau DMK — masih milik perusahaan.</p>
                            <div class="mt-3 inline-flex items-center gap-1 text-xs text-gray-400 bg-gray-50 rounded-lg px-2.5 py-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Barang tidak hilang, hanya berpindah lokasi
                            </div>
                        </div>
                    </div>
                    <div class="absolute bottom-5 right-5 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </div>
                </a>

                {{-- Transfer External --}}
                <a href="{{ route('barang.keluar.external-transfer.step1') }}"
                   class="group relative bg-white rounded-2xl border border-gray-200 p-6 hover:border-orange-400 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0 w-11 h-11 bg-orange-50 rounded-xl flex items-center justify-center group-hover:bg-orange-500 transition-colors duration-200">
                            <svg class="w-5 h-5 text-orange-500 group-hover:text-white transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7M12 3v18" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900 group-hover:text-orange-600 transition-colors duration-200">Kirim ke Jasa Cuci</h3>
                            <p class="mt-1 text-sm text-gray-500 leading-relaxed">Barang dikirim ke pihak luar untuk proses pencucian sebelum kembali ke gudang.</p>
                            <div class="mt-3 inline-flex items-center gap-1 text-xs text-gray-400 bg-gray-50 rounded-lg px-2.5 py-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Stok akan tercatat sebagai "sedang di cuci"
                            </div>
                        </div>
                    </div>
                    <div class="absolute bottom-5 right-5 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </div>
                </a>

            </div>
        </div>

        {{-- Section: Barang Masuk / Kembali --}}
        <div>
            <div class="flex items-center gap-3 mb-5">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-green-400"></div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Barang Kembali ke Gudang</span>
                </div>
                <div class="flex-1 h-px bg-gray-200"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                {{-- Kembali dari Jasa Cuci --}}
                <a href="{{ route('barang.keluar.receive-external.step1') }}"
                   class="group relative bg-white rounded-2xl border border-gray-200 p-6 hover:border-green-400 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0 w-11 h-11 bg-green-50 rounded-xl flex items-center justify-center group-hover:bg-green-500 transition-colors duration-200">
                            <svg class="w-5 h-5 text-green-500 group-hover:text-white transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900 group-hover:text-green-600 transition-colors duration-200">Terima dari Jasa Cuci</h3>
                            <p class="mt-1 text-sm text-gray-500 leading-relaxed">Barang yang sudah selesai dicuci diterima kembali dan stoknya masuk ke gudang.</p>
                            <div class="mt-3 inline-flex items-center gap-1 text-xs text-gray-400 bg-gray-50 rounded-lg px-2.5 py-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Lakukan setelah "Kirim ke Jasa Cuci" selesai
                            </div>
                        </div>
                    </div>
                    <div class="absolute bottom-5 right-5 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </div>
                </a>

            </div>
        </div>

    </div>
</div>
@endsection
