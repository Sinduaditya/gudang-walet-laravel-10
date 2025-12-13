<div class="bg-white text-gray-800 w-64 min-h-screen flex flex-col shadow-lg border-r border-gray-200">
    <div class="p-5 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-blue-600">
            GudangWalet
        </h2>
        <button id="close-sidebar" class="md:hidden p-1 rounded-md hover:bg-gray-100 transition-colors">
            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">

        @php
        // Helper untuk active state
        $isDashboard = request()->routeIs('dashboard');
        $isBarangMasuk = request()->routeIs('incoming-goods.*');
        $isGrading = request()->routeIs('grading-goods.*');
        $isManajemenIdm = request()->routeIs('manajemen-idm.*');
        $isTransferIdm = request()->routeIs('transfer-idm.*');
        $isBarangKeluar = request()->routeIs('barang.keluar.*');
        $isTrackingStock = request()->routeIs('tracking-stock.*');
        $isMasterGradeCompany = request()->routeIs('grade-company.*');
        $isMasterSupplier = request()->routeIs('suppliers.*');
        $isMasterGradeSupplier = request()->routeIs('grade-supplier.*');
        $isMasterLokasi = request()->routeIs('locations.*');
        @endphp

        <a href="{{ route('dashboard') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 group
            {{ $isDashboard ? 'bg-blue-50 text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800' }}">
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                </path>
            </svg>
            <span class="font-medium">Dashboard</span>
        </a>

        <a href="{{ route('incoming-goods.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 group
            {{ $isBarangMasuk ? 'bg-blue-50 text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800' }}">
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                </path>
            </svg>
            <span class="font-medium">Barang Masuk</span>
        </a>


        <a href="{{ route('grading-goods.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 group
            {{ $isGrading ? 'bg-blue-50 text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800' }}">
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                </path>
            </svg>
            <span class="font-medium">Manajemen Grading</span>
        </a>

        <a href="{{ route('manajemen-idm.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 group
            {{ $isManajemenIdm ? 'bg-blue-50 text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800' }}">
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                </path>
            </svg>
            <span class="font-medium">Manajemen IDM</span>
        </a>


        <a href="{{ route('barang.keluar.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 group
            {{ $isBarangKeluar ? 'bg-blue-50 text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800' }}">
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h16m0 0l-4-4m4 4l-4 4">
                </path>
            </svg>
            <span class="font-medium">Barang Keluar</span>
        </a>

        <a href="{{ route('tracking-stock.get.grade.company') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 group
            {{ $isTrackingStock ? 'bg-blue-50 text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800' }}">
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <span class="font-medium">Tracking Stok</span>
        </a>

        <div class="pt-4">
            <div class="border-t border-gray-200 mb-4"></div>
            <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
                Master Data
            </h3>

            <div class="space-y-2">
                <a href="{{ route('suppliers.index') }}"
                    class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 group
                    {{ $isMasterSupplier ? 'bg-blue-50 text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800' }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                    <span class="font-medium">Data Supplier</span>
                </a>

                <a href="{{ route('grade-supplier.index') }}"
                    class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 group
                    {{ $isMasterGradeSupplier ? 'bg-blue-50 text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800' }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                        </path>
                    </svg>
                    <span class="font-medium">Grading Supplier</span>
                </a>

                <a href="{{ route('grade-company.index') }}"
                    class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 group
                    {{ $isMasterGradeCompany ? 'bg-blue-50 text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800' }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                        </path>
                    </svg>
                    <span class="font-medium">Grading Perusahaan</span>
                </a>

                <a href="{{ route('locations.index') }}"
                    class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 group
                    {{ $isMasterLokasi ? 'bg-blue-50 text-blue-600 font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800' }}">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="font-medium">Data Lokasi</span>
                </a>

                <a href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 group text-red-600 hover:bg-red-50 hover:text-red-700">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H3"></path>
                    </svg>
                    <span class="font-medium">Logout</span>
                </a>

                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </div>
    </nav>

    <div class="p-4 border-t border-gray-200">
        <div class="flex items-center text-xs text-gray-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>v1.0.0</span>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleSubmenu(submenuId, arrowId) {
        const submenu = document.getElementById(submenuId);
        const arrow = document.getElementById(arrowId);

        submenu.classList.toggle('hidden');
        arrow.classList.toggle('rotate-90');
    }
</script>
@endpush