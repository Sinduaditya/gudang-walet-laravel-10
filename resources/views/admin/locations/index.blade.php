@extends('layouts.app')

@section('title', 'Manajemen Lokasi')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">Manajemen Lokasi</h1>
                <a href="{{ route('locations.export') }}" class="flex items-center text-sm text-gray-600 hover:text-gray-800">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" />
                    </svg>
                    Download as Excel
                </a>
            </div>

            <!-- Search Section -->
            <div class="mb-6">
                <!-- Search Form -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <form method="GET" action="{{ route('locations.index') }}">
                        <div class="flex flex-col lg:flex-row gap-4">
                            <!-- Search Input -->
                            <div class="flex-1">
                                <label class="flex items-center text-sm text-gray-600 mb-2">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Cari Lokasi
                                </label><input type="text" name="search" value="{{ request('search') }}"
                                    placeholder="Cari berdasarkan nama..."
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
                                <a href="{{ route('locations.index') }}"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium transition duration-200 whitespace-nowrap">
                                    Reset
                                </a>

                                <!-- Add Button -->
                                <a href="{{ route('locations.create') }}"
                                    class="flex items-center bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-200 whitespace-nowrap">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Lokasi
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

            <!-- Table -->
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="locationsTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama Lokasi
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tipe
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Deskripsi
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal Dibuat
                            </th>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($locations as $index => $location)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $location->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($location->is_jasa_cuci)
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            Jasa Cuci
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                            Non-Jasa Cuci
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $location->description ?: '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $location->created_at ? $location->created_at->format('d M Y') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                    <div class="flex items-center justify-center space-x-2">
                                        @php
                                            $locHasHistory = $location->stock_transfers_from_exists
                                                || $location->stock_transfers_to_exists
                                                || $location->inventory_transactions_exists
                                                || $location->sale_items_exists;
                                        @endphp
                                        <a href="{{ route('locations.edit', $location->id) }}"
                                            data-has-history="{{ $locHasHistory ? '1' : '0' }}"
                                            data-location-name="{{ $location->name }}"
                                            onclick="handleEditClick(event, this)"
                                            class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition duration-200">
                                            Edit
                                        </a>
                                        <button onclick="confirmDelete({{ $location->id }}, '{{ $location->name }}')"
                                            class="inline-flex items-center px-3 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200 transition duration-200">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                    Tidak ada data lokasi
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>

                <!-- Pagination -->
                @if ($locations->hasPages())
                    <div class="bg-white px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing {{ $locations->firstItem() }}-{{ $locations->lastItem() }} of
                                {{ $locations->total() }} results
                            </div>
                            <div>
                                {{ $locations->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Edit Warning Modal (untuk lokasi yang sudah punya history transaksi) -->
    <div id="editWarningModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-amber-100">
                    <svg class="h-6 w-6 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mt-4">Peringatan: Lokasi Sudah Punya History</h3>
                <p class="text-sm text-gray-500 mt-3 text-left">
                    Lokasi <span id="editWarningLocationName" class="font-semibold text-gray-900"></span>
                    sudah pernah dipakai di transaksi
                    (<span class="font-mono text-xs">stock_transfer</span>,
                    <span class="font-mono text-xs">inventory_transaction</span>, atau
                    <span class="font-mono text-xs">sale_item</span>).
                </p>
                <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded text-left">
                    <p class="text-xs text-amber-700">
                        <strong>Perhatian:</strong> Mengubah tipe lokasi
                        (Jasa Cuci ↔ Non-Jasa Cuci) akan mempengaruhi konsistensi
                        data historis dan reporting. Pastikan perubahan memang diperlukan.
                    </p>
                </div>
                <div class="flex gap-3 justify-center mt-6">
                    <button type="button" onclick="closeEditWarningModal()"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-medium transition duration-200">
                        Batal
                    </button>
                    <a id="editWarningConfirmBtn" href="#"
                        class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-md text-sm font-medium transition duration-200">
                        Lanjutkan Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mt-4">Hapus Lokasi</h3>
                <p class="text-sm text-gray-500 mt-2">
                    Apakah Anda yakin ingin menghapus lokasi <span id="locationName"
                        class="font-semibold text-gray-900"></span>?
                </p>
                <form id="deleteForm" method="POST" class="mt-6">
                    @csrf
                    @method('DELETE')
                    <div class="flex gap-3 justify-center">
                        <button type="button" onclick="closeDeleteModal()"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-medium transition duration-200">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md text-sm font-medium transition duration-200">
                            Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Export to Excel (placeholder)
        function exportExcel() {
            alert('Fitur export Excel akan segera tersedia');
            // Implement actual export logic here
        }

        // Delete modal functions
        function confirmDelete(id, name) {
            document.getElementById('locationName').textContent = name;
            var url = '{{ route('locations.destroy', ':id') }}';
            url = url.replace(':id', id);
            document.getElementById('deleteForm').action = url;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Edit warning modal: tampilkan popup konfirmasi saat klik Edit
        // jika lokasi punya history transaksi (supaya admin aware sebelum masuk halaman edit)
        function handleEditClick(event, el) {
            const hasHistory = el.dataset.hasHistory === '1';
            if (hasHistory) {
                event.preventDefault();
                document.getElementById('editWarningLocationName').textContent = el.dataset.locationName;
                document.getElementById('editWarningConfirmBtn').setAttribute('href', el.getAttribute('href'));
                document.getElementById('editWarningModal').classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
            // else: biarkan default behavior (langsung navigate ke halaman edit)
        }

        function closeEditWarningModal() {
            document.getElementById('editWarningModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        // Close edit warning modal when clicking outside
        document.getElementById('editWarningModal')?.addEventListener('click', function(event) {
            if (event.target === this) {
                closeEditWarningModal();
            }
        });

        // Close edit warning modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('editWarningModal').classList.contains('hidden')) {
                closeEditWarningModal();
            }
        });

        // Close modal when clicking outside
        document.getElementById('deleteModal')?.addEventListener('click', function(event) {
            if (event.target === this) {
                closeDeleteModal();
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('[id^="alert-"]');
            alerts.forEach(alert => {
                if (alert) {
                    alert.style.transition = 'opacity 0.5s ease-out';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>
@endsection
