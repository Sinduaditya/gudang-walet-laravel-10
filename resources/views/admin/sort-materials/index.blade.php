@extends('layouts.app')

@section('title', 'Sortir Bahan')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">Sortir Bahan</h1>
            </div>

            <!-- Tab Navigation -->
            <div class="mb-6 border-b border-gray-200">
                <div class="flex gap-6">
                    <button id="tabList" onclick="showTab('list')"
                        class="pb-3 px-1 text-sm font-medium border-b-2 border-blue-500 text-blue-600">
                        Daftar Sortir
                    </button>
                    <button id="tabGlobal" onclick="showTab('global')"
                        class="pb-3 px-1 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                        Sortir Global (ALU, AF, Indomie P)
                    </button>
                </div>
            </div>

            <!-- Tab: List -->
            <div id="contentList">
                <!-- Search Section -->
            <div class="mb-6">
                <!-- Search Form -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <form method="GET" action="{{ route('sort-materials.index') }}">
                        <div class="flex flex-col lg:flex-row gap-4">
                            <!-- Search Input -->
                            <div class="flex-1">
                                <label class="flex items-center text-sm text-gray-600 mb-2">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Cari Sortir Bahan
                                </label>
                                <input type="text" name="search" value="{{ request('search') }}"
                                    placeholder="Cari berdasarkan nama Parent Grade..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            </div>

                            <!-- Buttons Group -->
                            <div class="flex items-end gap-2">
                                <!-- Search Button -->
                                <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium transition duration-200 whitespace-nowrap">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Cari
                                </button>

                                <!-- Reset Button -->
                                <a href="{{ route('sort-materials.index') }}"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium transition duration-200 whitespace-nowrap">
                                    Reset
                                </a>

                                <!-- Export Excel Button -->
                                <a href="{{ route('sort-materials.export', request()->query()) }}"
                                    class="flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium transition duration-200 whitespace-nowrap">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" />
                                    </svg>
                                    Export Excel
                                </a>

                                <!-- Add Button -->
                                <a href="{{ route('sort-materials.create') }}"
                                    class="flex items-center bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-200 whitespace-nowrap">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Sortir Bahan
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
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Parent Grade</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Grade Company</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Berat (Gram)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Deskripsi</th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($sortMaterials as $index => $item)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $sortMaterials->firstItem() + $index }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $item->sort_date->format('d M Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $item->parentGradeCompany->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $item->gradeCompany ? $item->gradeCompany->name : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($item->weight, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                                        @if ($item->description)
                                            <span class="truncate block" title="{{ $item->description }}">
                                                {{ Str::limit($item->description, 50) }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 italic">Tidak ada deskripsi</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                        <button onclick="confirmDelete({{ $item->id }})"
                                            class="inline-flex items-center px-3 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200 transition duration-200">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                        @if (request('search'))
                                            Tidak ada data yang sesuai dengan pencarian
                                            "{{ request('search') }}".
                                        @else
                                            Belum ada data Sortir Bahan.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($sortMaterials->hasPages())
                    <div class="bg-white px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing {{ $sortMaterials->firstItem() }}-{{ $sortMaterials->lastItem() }} of
                                {{ $sortMaterials->total() }} results
                            </div>
                            <div>
                                {{ $sortMaterials->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Tab: Global Sortir -->
            <div id="contentGlobal" class="hidden">
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                <form id="globalSortForm" method="POST" action="{{ route('sort-materials.store-global') }}">
                    @csrf

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="grade_company_id">
                            Pilih Grade Company
                        </label>
                        <select name="grade_company_id" id="grade_company_id" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">-- Pilih Grade --</option>
                            @foreach($grades as $grade)
                                <option value="{{ $grade->id }}" data-parent="{{ $grade->parentGradeCompany->name ?? '' }}">
                                    {{ $grade->name }} (Stock: {{ number_format($grade->global_stock, 0) }} gram)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="stockInfo" class="hidden mb-6 p-4 bg-blue-50 border border-blue-200 rounded">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Informasi Stok</h3>
                        <p class="text-gray-600">Grade: <span id="gradeName" class="font-medium"></span></p>
                        <p class="text-gray-600">Global Stock: <span id="globalStock" class="font-bold text-blue-600"></span> gram</p>
                        <input type="hidden" id="isAlu" value="0">
                    </div>

                    <div id="actionSection" class="hidden mb-6">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Pilih Aksi</h3>
                        <div class="flex gap-3" id="actionButtons">
                            <button type="button" id="btnMasukStok"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium transition duration-200">
                                MASUK STOK
                            </button>
                            <button type="button" id="btnPenjualan"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium transition duration-200">
                                PENJUALAN LANGSUNG
                            </button>
                        </div>
                        <p id="aluNote" class="hidden mt-3 text-sm text-green-600 font-medium">
                            * ALU otomatis masuk stok, tidak ada pilihan penjualan.
                        </p>
                    </div>

                    <div id="formSection" class="hidden">
                        <input type="hidden" name="action" id="actionType" value="">

                        <div id="destinationDiv" class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="destination">
                                Tujuan Sortir
                            </label>
                            <select name="destination" id="destination"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">-- Pilih Tujuan --</option>
                                @foreach($destinations as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="weight_grams">
                                Berat Input (gram)
                            </label>
                            <input type="number" step="0.01" name="weight_grams" id="weight_grams"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                placeholder="Masukkan berat dalam gram">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="sort_date">
                                Tanggal Sortir
                            </label>
                            <input type="date" name="sort_date" id="sort_date" value="{{ date('Y-m-d') }}"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="flex gap-3 justify-end mt-6">
                            <button type="button" id="btnCancel"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-medium transition duration-200">
                                Batal
                            </button>
                            <button type="submit" id="btnSubmit"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium transition duration-200">
                                Proses
                            </button>
                        </div>
                    </div>
                </form>

                <div class="mt-6 text-sm text-gray-500">
                    <p><strong>Catatan:</strong></p>
                    <ul class="list-disc list-inside mt-1">
                        <li><strong>ALU</strong>: 100% langsung masuk stok gudang (tidak ada penjualan langsung)</li>
                        <li><strong>AF / Indomie P</strong>: Sebagian masuk stok, sisa langsung dijual otomatis</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mt-4">Hapus Data Sortir</h3>
                <p class="text-sm text-gray-500 mt-2">
                    Apakah Anda yakin ingin menghapus data ini?
                    <span class="font-semibold text-gray-900">Stok Parent Grade akan dikembalikan.</span>
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

    @push('scripts')
        <script>
            function confirmDelete(id) {
                const baseUrl = "{{ route('sort-materials.destroy', ':id') }}";
                document.getElementById('deleteForm').action = baseUrl.replace(':id', id);
                document.getElementById('deleteModal').classList.remove('hidden');
            }

            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
            }

            // Close modal when clicking outside
            window.onclick = function (event) {
                let deleteModal = document.getElementById('deleteModal');
                if (event.target == deleteModal) {
                    closeDeleteModal();
                }
            }

            function showTab(tab) {
                const contentList = document.getElementById('contentList');
                const contentGlobal = document.getElementById('contentGlobal');
                const tabList = document.getElementById('tabList');
                const tabGlobal = document.getElementById('tabGlobal');

                if (tab === 'list') {
                    contentList.classList.remove('hidden');
                    contentGlobal.classList.add('hidden');
                    tabList.classList.add('border-blue-500', 'text-blue-600');
                    tabList.classList.remove('border-transparent', 'text-gray-500');
                    tabGlobal.classList.remove('border-blue-500', 'text-blue-600');
                    tabGlobal.classList.add('border-transparent', 'text-gray-500');
                } else {
                    contentList.classList.add('hidden');
                    contentGlobal.classList.remove('hidden');
                    tabGlobal.classList.add('border-blue-500', 'text-blue-600');
                    tabGlobal.classList.remove('border-transparent', 'text-gray-500');
                    tabList.classList.remove('border-blue-500', 'text-blue-600');
                    tabList.classList.add('border-transparent', 'text-gray-500');
                }
            }

            // Global Sortir JavaScript
            const gradesData = @json($grades);
            let selectedGrade = null;

            document.getElementById('grade_company_id')?.addEventListener('change', function() {
                const gradeId = parseInt(this.value);
                const selectedOption = this.options[this.selectedIndex];
                const parentName = selectedOption?.dataset?.parent || '';

                // Reset UI
                document.getElementById('stockInfo')?.classList.add('hidden');
                document.getElementById('actionSection')?.classList.add('hidden');
                document.getElementById('formSection')?.classList.add('hidden');
                document.getElementById('btnMasukStok')?.classList.remove('hidden');
                document.getElementById('btnPenjualan')?.classList.remove('hidden');
                document.getElementById('aluNote')?.classList.add('hidden');
                document.getElementById('destinationDiv')?.classList.remove('hidden');

                if (!gradeId) return;

                selectedGrade = gradesData.find(g => g.id === gradeId);
                if (!selectedGrade) return;

                document.getElementById('gradeName').textContent = selectedGrade.name;
                document.getElementById('globalStock').textContent = number_format(selectedGrade.global_stock, 0);
                document.getElementById('isAlu').value = (parentName === 'ALU') ? '1' : '0';
                document.getElementById('stockInfo')?.classList.remove('hidden');

                if (parentName === 'ALU') {
                    document.getElementById('btnPenjualan')?.classList.add('hidden');
                    document.getElementById('aluNote')?.classList.remove('hidden');
                    selectAction('masuk_stok');
                } else {
                    document.getElementById('actionSection')?.classList.remove('hidden');
                }
            });

            function selectAction(action) {
                document.getElementById('actionType').value = action;
                document.getElementById('actionSection')?.classList.add('hidden');
                document.getElementById('formSection')?.classList.remove('hidden');

                if (action === 'penjualan') {
                    document.getElementById('destinationDiv')?.classList.add('hidden');
                    document.getElementById('destination').value = 'jual';
                } else {
                    document.getElementById('destinationDiv')?.classList.remove('hidden');
                }

                document.getElementById('weight_grams')?.focus();
            }

            document.getElementById('btnMasukStok')?.addEventListener('click', () => selectAction('masuk_stok'));
            document.getElementById('btnPenjualan')?.addEventListener('click', () => selectAction('penjualan'));

            document.getElementById('btnCancel')?.addEventListener('click', function() {
                document.getElementById('formSection')?.classList.add('hidden');
                document.getElementById('actionSection')?.classList.remove('hidden');
                document.getElementById('weight_grams').value = '';
            });

            function number_format(num) {
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }

            document.getElementById('globalSortForm')?.addEventListener('submit', function(e) {
                const weight = parseFloat(document.getElementById('weight_grams')?.value);
                const stock = parseFloat(selectedGrade?.global_stock || 0);
                const isAlu = document.getElementById('isAlu')?.value === '1';

                if (!weight || weight <= 0) {
                    e.preventDefault();
                    alert('Berat input harus lebih dari 0');
                    return;
                }

                if (weight > stock) {
                    e.preventDefault();
                    alert('Berat input tidak boleh melebihi stock global (' + number_format(stock) + ' gram)');
                    return;
                }

                if (!isAlu && !document.getElementById('destination')?.value && document.getElementById('actionType')?.value === 'masuk_stok') {
                    e.preventDefault();
                    alert('Silakan pilih tujuan sortir');
                    return;
                }
            });
        </script>
    @endpush
@endsection