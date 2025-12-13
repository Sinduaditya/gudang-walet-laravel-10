<!-- filepath: resources/views/layouts/guest.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'GudangWalet') }} - Login</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @yield('content')
    </div>

    <!-- Toast for flash messages -->
    @if (session('success') || session('error'))
        <div id="toast" class="fixed top-4 right-4 z-50 transform transition-all duration-500 translate-x-full">
            <div
                class="bg-white rounded-lg shadow-lg border-l-4 {{ session('success') ? 'border-green-500' : 'border-red-500' }} p-4 max-w-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @if (session('success'))
                            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @else
                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @endif
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900">
                            {{ session('success') ?? session('error') }}
                        </p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button onclick="closeToast()" class="text-gray-400 hover:text-gray-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Show toast
            document.addEventListener('DOMContentLoaded', function() {
                const toast = document.getElementById('toast');
                if (toast) {
                    setTimeout(() => {
                        toast.classList.remove('translate-x-full');
                    }, 100);

                    // Auto hide after 5 seconds
                    setTimeout(() => {
                        closeToast();
                    }, 5000);
                }
            });

            function closeToast() {
                const toast = document.getElementById('toast');
                if (toast) {
                    toast.classList.add('translate-x-full');
                    setTimeout(() => {
                        toast.remove();
                    }, 500);
                }
            }

            function togglePassword() {
                const passwordInput = document.getElementById('password');
                const eyeOpen = document.getElementById('eye-open');
                const eyeClosed = document.getElementById('eye-closed');

                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    eyeOpen.classList.add('hidden');
                    eyeClosed.classList.remove('hidden');
                } else {
                    passwordInput.type = 'password';
                    eyeOpen.classList.remove('hidden');
                    eyeClosed.classList.add('hidden');
                }
            }
        </script>
    @endif
    @stack('scripts')
</body>

</html>
