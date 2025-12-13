<header class="bg-white border-b border-gray-200 px-4 lg:px-6 py-4">
    <div class="flex items-center justify-between">
        <!-- Hamburger menu untuk mobile -->
        <div class="flex items-center">
            <button id="mobile-menu-toggle" class="md:hidden p-2 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            
            <!-- Breadcrumb atau title halaman -->
            <div class="ml-4 md:ml-0">
                <h1 class="text-xl font-semibold text-gray-800">
                    @yield('title', 'Dashboard')
                </h1>
                <div class="text-sm text-gray-500">
                    @yield('breadcrumb', 'Sistem Manajemen Gudang Walet')
                </div>
            </div>
        </div>

        <!-- User menu dan notifikasi -->
        <div class="flex items-center space-x-4">
            <!-- User profile -->
            <div class="relative">
                <button class="flex items-center space-x-3 p-2 rounded-md hover:bg-gray-100 transition-colors">
                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                        <span class="text-white font-medium text-sm">{{ substr(auth()->user()->name ?? 'U', 0, 1) }}</span>
                    </div>
                    <div class="hidden md:block text-left">
                        <div class="text-sm font-medium text-gray-800">{{ auth()->user()->name ?? 'User' }}</div>
                        <div class="text-xs text-gray-500">{{ auth()->user()->email ?? 'user@example.com' }}</div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</header>