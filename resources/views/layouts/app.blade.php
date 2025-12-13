<!-- filepath: resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'GudangWalet') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Desktop Sidebar -->
        <div class="hidden md:flex fixed left-0 top-0 h-full w-64 z-30 bg-white shadow-lg">
            <div class="sidebar-scroll w-full overflow-y-auto">
                @include('partials.sidebar')
            </div>
        </div>

        <!-- Mobile Overlay -->
        <div id="sidebar-overlay"
            class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity duration-300"></div>

        <!-- Mobile Sidebar -->
        <div id="mobile-sidebar"
            class="fixed left-0 top-0 z-50 h-full w-64 bg-white shadow-lg transform -translate-x-full sidebar-transition md:hidden">
            <div class="sidebar-scroll h-full overflow-y-auto">
                @include('partials.sidebar')
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col w-full md:ml-64">
            <!-- Header -->
            <div class="sticky top-0 z-20 bg-white shadow-sm">
                @include('partials.header')
            </div>

            <!-- Main Content Area -->
            <main id="main-content" class="flex-1 w-full overflow-x-hidden p-6">
                <div class="container mx-auto px-4 mt-4">
                    @if (session('success'))
                        <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4" id="alert-success">
                            <div class="flex justify-between items-center">
                                <p class="text-sm text-green-700">{{ session('success') }}</p>
                                <button onclick="document.getElementById('alert-success')?.remove()"
                                    class="text-green-500">✕</button>
                            </div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4" id="alert-error">
                            <div class="flex justify-between items-center">
                                <p class="text-sm text-red-700">{{ session('error') }}</p>
                                <button onclick="document.getElementById('alert-error')?.remove()"
                                    class="text-red-500">✕</button>
                            </div>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 bg-yellow-50 border-l-4 border-yellow-400 p-4" id="alert-validation">
                            <div class="flex justify-between">
                                <div>
                                    <p class="text-sm text-yellow-700 font-semibold">Terdapat error validasi:</p>
                                    <ul class="mt-2 text-sm text-yellow-700 list-disc ml-5">
                                        @foreach ($errors->all() as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <button onclick="document.getElementById('alert-validation')?.remove()"
                                    class="text-yellow-600">✕</button>
                            </div>
                        </div>
                    @endif
                </div>
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobile-menu-toggle');
            const mobileSidebar = document.getElementById('mobile-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const closeSidebarBtn = document.getElementById('close-sidebar');

            function openSidebar() {
                mobileSidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');

                setTimeout(() => {
                    overlay.classList.add('opacity-100');
                }, 10);
            }

            function closeSidebar() {
                overlay.classList.remove('opacity-100');
                mobileSidebar.classList.add('-translate-x-full');
                document.body.classList.remove('overflow-hidden');

                setTimeout(() => {
                    overlay.classList.add('hidden');
                }, 300);
            }

            if (mobileToggle) {
                mobileToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    openSidebar();
                });
            }

            if (overlay) {
                overlay.addEventListener('click', closeSidebar);
            }

            if (closeSidebarBtn) {
                closeSidebarBtn.addEventListener('click', closeSidebar);
            }

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !mobileSidebar.classList.contains('-translate-x-full')) {
                    closeSidebar();
                }
            });

            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    closeSidebar();
                }
            });


            function showModal(modalId) {
                document.getElementById(modalId).classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            function closeModal(modalId) {
                document.getElementById(modalId).classList.add('hidden');
                document.body.style.overflow = 'auto';
            }

            // Auto hide toasts
            document.addEventListener('DOMContentLoaded', function() {
                const toasts = document.querySelectorAll('[id^="toast-"]');
                if (toasts.length > 0) {
                    setTimeout(() => {
                        toasts.forEach(toast => {
                            if (toast) {
                                toast.style.opacity = '0';
                                toast.style.transition = 'opacity 0.5s ease-out';
                                setTimeout(() => toast.remove(), 500);
                            }
                        });
                    }, 5000);
                }
            });
        });
    </script>
    @stack('scripts')
</body>

</html>
