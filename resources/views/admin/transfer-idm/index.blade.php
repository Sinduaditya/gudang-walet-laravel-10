@extends('layouts.app')

@section('title', 'Transfer IDM')

@section('content')
<div class="bg-white min-h-screen">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header Section -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Transfer IDM</h1>
                <p class="mt-1 text-sm text-gray-600">Kelola data transfer IDM anda</p>
            </div>

            <a href="{{ route('barang.keluar.transfer-idm.create') }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium transition duration-200 shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Data
            </a>
        </div>

        <!-- Filter & Search Section -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
            <form action="{{ route('barang.keluar.transfer-idm.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">

                <!-- Search Bar -->
                <div class="flex-1 min-w-[200px]">
                    <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Cari Kode
                    </label>
                    <div class="flex gap-2">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Cari kode transfer..."
                               class="w-full h-10 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <button type="submit"
                                class="h-10 px-4 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium transition duration-200">
                            Cari
                        </button>
                    </div>
                </div>

                <!-- Date Filters Wrapper -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="w-full sm:w-auto">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Awal</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}"
                               class="w-full sm:w-40 h-10 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    </div>
                    <div class="w-full sm:w-auto">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Akhir</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}"
                               class="w-full sm:w-40 h-10 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    </div>
                </div>

                 <!-- Action Buttons -->
                <div class="flex items-end gap-2">
                    <button type="submit"
                            class="h-10 px-4 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium transition duration-200 whitespace-nowrap shadow-sm flex items-center justify-center">
                        Filter
                    </button>
                    <a href="{{ route('barang.keluar.transfer-idm.index') }}"
                       class="h-10 px-4 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium transition duration-200 text-center whitespace-nowrap shadow-sm flex items-center justify-center">
                        Reset
                    </a>
                </div>
            </form>

            @if(request('search') || request('start_date') || request('end_date'))
                <div class="mt-3 pt-3 border-t border-gray-200 flex flex-wrap gap-2">
                    <span class="text-sm text-gray-600 self-center">Filter aktif:</span>
                    @if(request('search'))
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Search: "{{ request('search') }}"
                        </span>
                    @endif
                    @if(request('start_date'))
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Start: {{ request('start_date') }}
                        </span>
                    @endif
                    @if(request('end_date'))
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            End: {{ request('end_date') }}
                        </span>
                    @endif
                </div>
            @endif
        </div>

        <!-- Table Section -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Transfer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Transfer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Barang</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Transfer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($transfers as $index => $transfer)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transfers->firstItem() + $index }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $transfer->transfer_code }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($transfer->transfer_date)->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transfer->sum_goods }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">Rp {{ number_format($transfer->price_transfer, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('barang.keluar.transfer-idm.show', $transfer->id) }}"
                                           class="text-blue-600 hover:text-blue-800 font-medium">Detail</a>
                                        <a href="{{ route('barang.keluar.transfer-idm.edit', $transfer->id) }}"
                                           class="text-yellow-600 hover:text-yellow-800 font-medium">Edit</a>
                                        <button onclick="confirmDelete('{{ route('barang.keluar.transfer-idm.destroy', $transfer->id) }}')"
                                            class="text-red-600 hover:text-red-800 font-medium">Hapus</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p>Data tidak ditemukan {{ request('search') ? 'untuk keyword "'.request('search').'"' : '' }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($transfers->hasPages())
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    {{ $transfers->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
    <!-- Delete Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-96">
            <h3 class="font-medium mb-4">Hapus Data</h3>
            <p class="text-sm text-gray-600 mb-4">Apakah Anda yakin ingin menghapus data transfer ini?</p>
            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="flex gap-3">
                    <button type="button" onclick="closeDeleteModal()"
                        class="flex-1 px-3 py-2 bg-gray-200 rounded">Batal</button>
                    <button type="submit" class="flex-1 px-3 py-2 bg-red-600 text-white rounded">Hapus</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function confirmDelete(url) {
                const modal = document.getElementById('deleteModal');
                const form = document.getElementById('deleteForm');
                form.action = url; 
                modal.classList.remove('hidden');
            }

            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
            }
        </script>
    @endpush
@endsection
