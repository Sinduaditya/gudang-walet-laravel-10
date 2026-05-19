@extends('layouts.app')

@section('title', 'Data Grading Barang')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Data Grading Barang</h1>
                    <p class="mt-1 text-sm text-gray-600">Kelola dan lihat hasil grading barang</p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Export Button - Using current filter -->
                    <a href="{{ route('grading-goods.export', [
                        'month' => request('month'),
                        'year' => request('year'),
                        'supplier_name' => request('supplier_name'),
                        'grading_date' => request('grading_date'),
                    ]) }}"
                        class="flex items-center text-sm text-gray-600 hover:text-gray-800 bg-green-50 hover:bg-green-100 px-3 py-2 rounded-md border border-green-200">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" />
                        </svg>
                        Export Excel
                    </a>

                    <a href="{{ route('grading-goods.step1') }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Grading
                    </a>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                <form method="GET" action="{{ route('grading-goods.index') }}" class="flex flex-col gap-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Filter Supplier -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                            <select name="supplier_name"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Semua Supplier</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->name }}"
                                        {{ request('supplier_name') == $supplier->name ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter Tanggal Grading -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Grading</label>
                            <input type="date" name="grading_date" value="{{ request('grading_date') }}"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Filter Bulan -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                            <select name="month"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Semua Bulan</option>
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ sprintf('%02d', $i) }}"
                                        {{ request('month') == sprintf('%02d', $i) ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <!-- Filter Tahun -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                            <select name="year"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Semua Tahun</option>
                                @for ($year = date('Y'); $year >= 2020; $year--)
                                    <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <!-- Filter Per Page -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data per Halaman</label>
                            <select name="per_page"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @foreach ([10, 20, 30, 40, 50, 60] as $option)
                                    <option value="{{ $option }}" {{ $perPage == $option ? 'selected' : '' }}>
                                        {{ $option }} data
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                            Filter
                        </button>
                        <a href="{{ route('grading-goods.index') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- ✅ Updated Table with Enhanced Columns -->
            <div class="bg-white shadow-sm border rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Tanggal Grading</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Supplier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Grade Supplier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Berat Nota (gr)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Berat Gudang (gr)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Total Grading (gr)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Selisih (gr)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Persentase (%)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($gradings as $i => $grading)
                                @php
                                    // ✅ Hitung selisih dan persentase
                                    $supplierWeight = $grading->supplier_weight_grams ?? 0;
                                    $warehouseWeight = $grading->warehouse_weight_grams ?? 0;
                                    $totalGradingWeight = $grading->total_grading_weight ?? 0;
                                    $difference = $totalGradingWeight - $warehouseWeight;
                                    $percentage = $warehouseWeight > 0 ? abs($difference / $warehouseWeight) * 100 : 0;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $gradings->firstItem() + $i }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $grading->grading_date ? \Carbon\Carbon::parse($grading->grading_date)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                        {{ $grading->supplier_name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $grading->grade_supplier_name ?? '-' }}
                                        <div class="text-xs text-gray-500">
                                            {{ $grading->total_grades }} grade hasil
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-mono">
                                        {{ number_format($supplierWeight, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-mono">
                                        {{ number_format($warehouseWeight, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-mono font-semibold text-blue-600">
                                        {{ number_format($totalGradingWeight, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @if ($difference < 0)
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-red-600 font-semibold font-mono">{{ number_format($difference, 0, ',', '.') }}</span>
                                                <span class="text-xs text-red-500">(susut)</span>
                                            </div>
                                        @elseif($difference > 0)
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-green-600 font-semibold font-mono">+{{ number_format($difference, 0, ',', '.') }}</span>
                                                <span class="text-xs text-green-500">(kelebihan)</span>
                                            </div>
                                        @else
                                            <div class="flex flex-col">
                                                <span class="text-gray-600 font-mono">0</span>
                                                <span class="text-xs text-gray-500">(sama)</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @php
                                            $percentageFormatted =
                                                $percentage == floor($percentage)
                                                    ? number_format($percentage, 0, ',', '.')
                                                    : number_format($percentage, 1, ',', '.');

                                            $percentageClass = 'text-gray-600';
                                            // ✅ G-19: Threshold standar 2% (konsisten dengan barang masuk)
                                            if ($percentage > 2) {
                                                $percentageClass =
                                                    'text-red-600 font-bold bg-red-50 px-1 py-0.5 rounded';
                                            } elseif ($percentage > 0) {
                                                $percentageClass = 'text-orange-600 font-semibold';
                                            }
                                        @endphp
                                        <span class="{{ $percentageClass }} font-semibold">
                                            {{ $percentageFormatted }}%
                                            @if ($percentage > 2)
                                                ⚠️
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="flex items-center gap-2">
                                            {{-- ✅ FIX: Gunakan receipt_item_id --}}
                                            <a href="{{ route('grading-goods.show', array_merge(['receiptItemId' => $grading->receipt_item_id], request()->query())) }}"
                                                class="text-blue-600 hover:text-blue-800 font-medium">Detail</a>
                                            <button onclick="openEditModal({{ $grading->receipt_item_id }})"
                                                class="text-amber-600 hover:text-amber-800 font-medium">Edit</button>
                                            <button onclick="confirmDelete({{ $grading->receipt_item_id }})"
                                                class="text-red-600 hover:text-red-800 font-medium">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                                        @if (request('month') || request('year'))
                                            Tidak ada data grading untuk filter yang dipilih.
                                        @else
                                            Belum ada data grading barang.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($gradings->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $gradings->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50 flex items-center justify-center"
         data-url-template="{{ route('grading-goods.destroy', ['receiptItemId' => ':id']) }}">
        <div class="bg-white rounded-lg p-6 w-96">
            <h3 class="font-medium mb-4">Hapus Data</h3>
            <p class="text-sm text-gray-600 mb-4">Apakah Anda yakin ingin menghapus data grading ini?</p>
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

    <!-- Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col m-4">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 rounded-t-lg">
                <h3 class="text-lg font-semibold text-gray-900" id="editModalTitle">Edit Jenis Barang Keluar</h3>
                <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Modal Body (Scrollable if too many items) -->
            <form id="editForm" onsubmit="submitEditForm(event)" class="flex flex-col flex-1 overflow-hidden">
                @csrf
                @method('PUT')
                <div class="p-6 overflow-y-auto space-y-4 flex-1">
                    <div id="editModalLoading" class="flex justify-center items-center py-8">
                        <svg class="animate-spin h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="ml-3 text-gray-600">Memuat data...</span>
                    </div>
                    
                    <div id="editModalError" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
                    </div>
                    
                    <div id="editModalContent" class="hidden space-y-4">
                        <!-- Table of Grade Items -->
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Grade</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Berat</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Jumlah</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Kategori</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Jenis Barang Keluar</th>
                                    </tr>
                                </thead>
                                <tbody id="editModalTableBody" class="bg-white divide-y divide-gray-200 text-sm text-gray-900">
                                    <!-- Dynamic rows will be inserted here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3 rounded-b-lg">
                    <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium">Batal</button>
                    <button type="submit" id="editSubmitBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium hidden">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function confirmDelete(id) {
                const modal = document.getElementById('deleteModal');
                const form = document.getElementById('deleteForm');
                const urlTemplate = modal.dataset.urlTemplate;
                form.action = urlTemplate.replace(':id', id);
                modal.classList.remove('hidden');
            }

            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
            }

            let currentReceiptItemId = null;

            function openEditModal(receiptItemId) {
                currentReceiptItemId = receiptItemId;
                const modal = document.getElementById('editModal');
                const loading = document.getElementById('editModalLoading');
                const errorDiv = document.getElementById('editModalError');
                const content = document.getElementById('editModalContent');
                const submitBtn = document.getElementById('editSubmitBtn');
                const tableBody = document.getElementById('editModalTableBody');

                // Reset UI
                errorDiv.classList.add('hidden');
                errorDiv.innerText = '';
                content.classList.add('hidden');
                submitBtn.classList.add('hidden');
                loading.classList.remove('hidden');
                tableBody.innerHTML = '';

                modal.classList.remove('hidden');

                // Fetch data
                const url = `{{ route('grading-goods.edit-ajax', ['receiptItemId' => ':id']) }}`.replace(':id', receiptItemId);
                
                fetch(url)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            loading.classList.add('hidden');
                            content.classList.remove('hidden');
                            submitBtn.classList.remove('hidden');

                            if (result.data.length === 0) {
                                tableBody.innerHTML = `<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Tidak ada data grade.</td></tr>`;
                                return;
                            }

                            result.data.forEach(item => {
                                const row = document.createElement('tr');
                                row.className = 'hover:bg-gray-50';
                                
                                const formattedWeight = new Intl.NumberFormat('id-ID').format(item.weight_grams);
                                const formattedQty = new Intl.NumberFormat('id-ID').format(item.quantity);

                                row.innerHTML = `
                                    <td class="px-4 py-3 font-medium text-gray-900">${item.grade_name}</td>
                                    <td class="px-4 py-3 font-mono">${formattedWeight} gr</td>
                                    <td class="px-4 py-3 font-mono">${formattedQty}</td>
                                    <td class="px-4 py-3" id="category-badge-${item.id}">
                                        ${item.category_grade ? `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">${item.category_grade}</span>` : '<span class="text-gray-400 text-xs">-</span>'}
                                    </td>
                                    <td class="px-4 py-3">
                                        <select name="outgoing_types[${item.id}]" onchange="checkCategoryMutualExclusivity(${item.id}, this, '${item.category_grade || ''}')" class="w-full border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                            <option value="">Pilih Jenis Keluar</option>
                                            <option value="penjualan_langsung" ${item.outgoing_type === 'penjualan_langsung' ? 'selected' : ''}>Penjualan Langsung</option>
                                            <option value="internal" ${item.outgoing_type === 'internal' ? 'selected' : ''}>Internal</option>
                                            <option value="external" ${item.outgoing_type === 'external' ? 'selected' : ''}>External</option>
                                        </select>
                                        ${item.category_grade ? `<p class="text-[10px] text-orange-600 mt-1 font-medium italic" id="warning-mut-excl-${item.id}">* Memilih jenis keluar akan menghapus kategori ${item.category_grade}</p>` : ''}
                                    </td>
                                `;
                                tableBody.appendChild(row);
                            });
                        } else {
                            throw new Error(result.message || 'Gagal memuat data.');
                        }
                    })
                    .catch(err => {
                        loading.classList.add('hidden');
                        errorDiv.innerText = err.message || 'Terjadi kesalahan sistem.';
                        errorDiv.classList.remove('hidden');
                    });
            }

            function checkCategoryMutualExclusivity(itemId, selectEl, originalCategoryGrade) {
                const warningMsg = document.getElementById(`warning-mut-excl-${itemId}`);
                const badgeContainer = document.getElementById(`category-badge-${itemId}`);
                if (selectEl.value !== "") {
                    if (badgeContainer) {
                        badgeContainer.innerHTML = '<span class="text-gray-400 text-xs line-through italic">- (akan dihapus)</span>';
                    }
                    if (warningMsg) {
                        warningMsg.classList.add('text-red-600', 'font-bold');
                        warningMsg.innerText = `* Kategori ${originalCategoryGrade} akan dihapus setelah disimpan`;
                    }
                } else {
                    if (badgeContainer) {
                        if (originalCategoryGrade) {
                            badgeContainer.innerHTML = `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">${originalCategoryGrade}</span>`;
                        } else {
                            badgeContainer.innerHTML = '<span class="text-gray-400 text-xs">-</span>';
                        }
                    }
                    if (warningMsg) {
                        warningMsg.classList.remove('text-red-600', 'font-bold');
                        warningMsg.className = "text-[10px] text-orange-600 mt-1 font-medium italic";
                        warningMsg.innerText = `* Memilih jenis keluar akan menghapus kategori ${originalCategoryGrade}`;
                    }
                }
            }

            function closeEditModal() {
                document.getElementById('editModal').classList.add('hidden');
            }

            function submitEditForm(event) {
                event.preventDefault();
                
                const submitBtn = document.getElementById('editSubmitBtn');
                const originalBtnText = submitBtn.innerText;
                submitBtn.disabled = true;
                submitBtn.innerText = 'Menyimpan...';

                const errorDiv = document.getElementById('editModalError');
                errorDiv.classList.add('hidden');
                errorDiv.innerText = '';

                const form = document.getElementById('editForm');
                const formData = new FormData(form);

                const url = `{{ route('grading-goods.update-ajax', ['receiptItemId' => ':id']) }}`.replace(':id', currentReceiptItemId);

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        window.location.reload();
                    } else {
                        throw new Error(result.message || 'Gagal menyimpan data.');
                    }
                })
                .catch(err => {
                    submitBtn.disabled = false;
                    submitBtn.innerText = originalBtnText;
                    errorDiv.innerText = err.message || 'Terjadi kesalahan sistem.';
                    errorDiv.classList.remove('hidden');
                });
            }
        </script>
    @endpush
@endsection
