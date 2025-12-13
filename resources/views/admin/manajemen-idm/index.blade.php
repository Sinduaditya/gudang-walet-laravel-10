@extends('layouts.app')

@section('title', 'Manajemen IDM')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Manajemen IDM</h1>
                    <p class="mt-1 text-sm text-gray-600">Kelola data estimasi IDM</p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ Route::has('manajemen-idm.create') ? route('manajemen-idm.create') : '#' }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Estimasi IDM
                    </a>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                <form method="GET" action="{{ Route::has('manajemen-idm.index') ? route('manajemen-idm.index') : '#' }}"
                    class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <!-- Filter Supplier -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                            <select name="supplier_id"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Semua Supplier</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter Grade Company -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Grade Company</label>
                            <select name="grade_company_id"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Semua Grade</option>
                                @foreach ($gradeCompanies as $grade)
                                    <option value="{{ $grade->id }}" {{ request('grade_company_id') == $grade->id ? 'selected' : '' }}>
                                        {{ $grade->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter Kategori IDM -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori IDM</label>
                            <select name="category_grade"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Semua Kategori</option>
                                <option value="IDM A" {{ request('category_grade') == 'IDM A' ? 'selected' : '' }}>IDM A</option>
                                <option value="IDM B" {{ request('category_grade') == 'IDM B' ? 'selected' : '' }}>IDM B</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                            Filter
                        </button>
                        <a href="{{ Route::has('manajemen-idm.index') ? route('manajemen-idm.index') : '#' }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="bg-white shadow-sm border rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">NO</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Nama Supplier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Kategori IDM</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Nama Grade</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Berat Awal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Susut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Estimasi Harga Jual per Gram</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($idmManagements ?? [] as $index => $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                        {{ $item->supplier->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        @php
                                            $category = $item->sourceItems->first()->category_grade ?? '-';
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $category == 'IDM A' ? 'bg-green-100 text-green-800' : ($category == 'IDM B' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                            {{ $category }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $item->gradeCompany->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-mono">
                                        {{ number_format($item->initial_weight ?? 0, 2) }} gr
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-mono">
                                        {{ number_format($item->shrinkage ?? 0, 2) }} gr
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-mono">
                                        Rp {{ number_format($item->estimated_selling_price ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ Route::has('manajemen-idm.show') ? route('manajemen-idm.show', $item->id) : '#' }}"
                                                class="text-blue-600 hover:text-blue-800 font-medium">Detail</a>
                                            <a href="{{ Route::has('manajemen-idm.edit') ? route('manajemen-idm.edit', $item->id) : '#' }}"
                                                class="text-yellow-600 hover:text-yellow-800 font-medium">Edit</a>
                                            <button onclick="confirmDelete({{ $item->id }})"
                                                class="text-red-600 hover:text-red-800 font-medium">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        @if(request('month') || request('year'))
                                            Tidak ada data estimasi IDM untuk filter yang dipilih.
                                        @else
                                            Belum ada data estimasi IDM.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(isset($idmManagements) && method_exists($idmManagements, 'links') && $idmManagements->isNotEmpty())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $idmManagements->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-96">
            <h3 class="font-medium mb-4">Hapus Data</h3>
            <p class="text-sm text-gray-600 mb-4">Apakah Anda yakin ingin menghapus data ini?</p>
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
            function confirmDelete(id) {
                const modal = document.getElementById('deleteModal');
                const form = document.getElementById('deleteForm');
                // Use route helper with placeholder
                const url = "{{ route('manajemen-idm.destroy', ':id') }}";
                form.action = url.replace(':id', id);
                modal.classList.remove('hidden');
            }

            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
            }
        </script>
    @endpush
@endsection
