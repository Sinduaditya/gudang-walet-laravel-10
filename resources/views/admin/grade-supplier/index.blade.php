@extends('layouts.app')

@section('title', 'Manajemen Grade Supplier')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">Manajemen Grade Supplier</h1>
                <button onclick="exportExcel()" class="flex items-center text-sm text-gray-600 hover:text-gray-800">
                    <a href="{{ route('grade-supplier.export') }}"
                        class="flex items-center text-sm text-gray-600 hover:text-gray-800">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" />
                        </svg>
                        Download as Excel
                    </a>
            </div>

            <div class="mb-6">
                <!-- Search Form -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <form method="GET" action="{{ route('grade-supplier.index') }}">
                        <div class="flex flex-col lg:flex-row gap-4">
                            <!-- Search Input -->
                            <div class="flex-1">
                                <label class="flex items-center text-sm text-gray-600 mb-2">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Cari Grade Supplier
                                </label><input type="text" name="search" value="{{ request('search') }}"
                                    placeholder="Cari berdasarkan nama atau deskripsi..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            </div>

                            <!-- Buttons Group -->
                            <div class="flex items-end gap-2">
                                <!-- Search Button -->
                                <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium transition duration-200 whitespace-nowrap">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Cari
                                </button>

                                <!-- Reset Button -->
                                <a href="{{ route('grade-company.index') }}"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium transition duration-200 whitespace-nowrap">
                                    Reset
                                </a>

                                <!-- Add Button -->
                                <a href="{{ route('grade-supplier.create') }}"
                                    class="flex items-center bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-200 whitespace-nowrap">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Grade Supplier
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Active Search Display -->
                    @if (request('search'))
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <div class="flex flex-wrap gap-2 items-center">
                                <span class="text-sm text-gray-600">Pencarian aktif:</span>
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    "{{ request('search') }}"
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="gradeSupplierTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    No
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nama Grade</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Gambar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Deskripsi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tanggal Dibuat</th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($grades as $index => $grade)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $grades->firstItem() + $index }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $grade->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($grade->image_url)
                                            <img src="{{ Str::startsWith($grade->image_url, ['http://', 'https://']) ? $grade->image_url : asset('storage/' . $grade->image_url) }}"
                                                alt="{{ $grade->name }}"
                                                class="h-10 w-10 sm:h-12 sm:w-12 rounded-md object-cover">
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $grade->description ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $grade->created_at?->format('d M Y') ?? '-' }}</td>
                                    <td class="px-6 py-4 text-center text-sm">
                                        <div class="flex items-center justify-center space-x-2">
                                            <a href="{{ route('grade-supplier.edit', $grade->id) }}"
                                                class="px-3 py-1 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition">Edit</a>
                                            <button onclick="confirmDelete({{ $grade->id }}, '{{ $grade->name }}')"
                                                class="px-3 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200 transition">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-sm text-gray-500">Belum ada data
                                        Grade
                                        Supplier</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($grades->hasPages())
                    <div class="bg-white px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Menampilkan {{ $grades->firstItem() }}–{{ $grades->lastItem() }} dari
                                {{ $grades->total() }} data
                            </div>
                            <div>

                                {{ $grades->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-96">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Hapus Grade Supplier</h3>
            <p class="text-sm text-gray-500 mb-4">Apakah Anda yakin ingin menghapus <span id="gradeName"
                    class="font-semibold text-gray-900"></span>?</p>
            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeDeleteModal()"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">Batal</button>
                    <button type="submit"
                        class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md text-sm">Hapus</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal delete
        function confirmDelete(id, name) {
            const baseUrl = `{{ route('grade-supplier.destroy', ':id') }}`;
            document.getElementById('gradeName').textContent = name;
            document.getElementById('deleteForm').action = baseUrl.replace(':id', id);
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Auto hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('[id^="alert-"]');
            alerts.forEach(alert => {
                if (alert) {
                    alert.style.transition = 'opacity 0.5s ease-out';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 4000);
    </script>
@endsection
