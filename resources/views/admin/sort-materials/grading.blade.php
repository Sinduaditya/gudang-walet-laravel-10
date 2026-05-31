@extends('layouts.app')

@section('title', 'Grading Internal (Pecah Stok)')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- Header --}}
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Grading Internal (Pecah Stok)</h1>
                    <p class="mt-1 text-sm text-gray-600">Pecah bahan mentah level Parent menjadi beberapa hasil jadi level Detail Grade</p>
                </div>
                <a href="{{ route('sort-materials.index') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </a>
            </div>

            @if(session('error'))
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('sort-materials.grading.store') }}" method="POST" id="gradingForm" class="space-y-6">
                @csrf

                {{-- Card 1: Sumber Stok Mentah --}}
                <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-2 h-6 bg-purple-600 rounded-full"></span>
                        1. Sumber Stok Mentah (Bahan Asal)
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- Tanggal Proses --}}
                        <div>
                            <label class="block font-semibold text-gray-700 mb-2" for="process_date">
                                Tanggal Proses <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="process_date" id="process_date" 
                                value="{{ old('process_date', date('Y-m-d')) }}" required
                                class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>

                        {{-- Parent Grade Asal --}}
                        <div>
                            <label class="block font-semibold text-gray-700 mb-2" for="source_parent_grade_company_id">
                                Parent Grade Asal <span class="text-red-500">*</span>
                            </label>
                            <select name="source_parent_grade_company_id" id="source_parent_grade_company_id" required
                                class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="" data-stock="0">-- Pilih Parent Asal --</option>
                                @foreach ($parentGradeCompanies as $pg)
                                    <option value="{{ $pg->id }}" data-stock="{{ $pg->stock }}"
                                        {{ old('source_parent_grade_company_id') == $pg->id ? 'selected' : '' }}>
                                        {{ $pg->name }} (Stok Sortir: {{ number_format($pg->stock, 2, ',', '.') }} gr)
                                    </option>
                                @endforeach
                            </select>
                            <div class="mt-2 text-sm text-gray-500">
                                Sisa Stok Mentah: <span id="available-stock-badge" class="font-bold text-purple-600">0,00 gr</span>
                            </div>
                        </div>

                        {{-- Berat Yang Diproses --}}
                        <div>
                            <label class="block font-semibold text-gray-700 mb-2" for="total_weight">
                                Total Berat Diproses (gram) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0.01" name="total_weight" id="total_weight" 
                                value="{{ old('total_weight') }}" required
                                placeholder="Masukkan berat yang akan dipecah"
                                class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                    </div>
                </div>

                {{-- Card 2: Hasil Pecahan / Grading --}}
                <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span class="w-2 h-6 bg-green-500 rounded-full"></span>
                            2. Hasil Pecahan Grading (Bahan Tujuan)
                        </h2>
                        
                        <button type="button" id="btnAddRow"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-lg shadow transition duration-150">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            Tambah Hasil
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="targetsTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-5/12">Parent Grade Tujuan <span class="text-red-500">*</span></th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-4/12">Detail Grade <span class="text-gray-400 font-normal text-2xs">(Opsional)</span></th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-2/12">Berat Hasil (gr) <span class="text-red-500">*</span></th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase w-1/12">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white" id="rowsContainer">
                                {{-- Baris dinamis akan di-inject di sini menggunakan Javascript --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Live Calculator Footer --}}
                    <div class="mt-6 pt-6 border-t border-gray-200 bg-gray-50 rounded-xl p-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                        <div class="p-3 bg-white rounded-lg shadow-sm border border-gray-200">
                            <div class="text-xs font-medium text-gray-500 uppercase">Target Proses</div>
                            <div class="mt-1 text-lg font-bold text-gray-900" id="calc-processed">0,00 gr</div>
                        </div>
                        <div class="p-3 bg-white rounded-lg shadow-sm border border-gray-200">
                            <div class="text-xs font-medium text-gray-500 uppercase">Total Hasil Pecahan</div>
                            <div class="mt-1 text-lg font-bold text-gray-900" id="calc-result">0,00 gr</div>
                        </div>
                        <div class="p-3 bg-white rounded-lg shadow-sm border border-gray-200" id="calc-diff-container">
                            <div class="text-xs font-medium text-gray-500 uppercase">Selisih (Wajib Pas)</div>
                            <div class="mt-1 text-lg font-bold" id="calc-diff">0,00 gr</div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center gap-3 pt-4">
                    <a href="{{ route('sort-materials.index') }}"
                        class="flex-1 inline-flex items-center justify-center px-4 py-3.5 border border-gray-300 text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-all">
                        Batal
                    </a>
                    <button type="submit" id="btnSubmit" disabled
                        class="flex-1 inline-flex items-center justify-center px-4 py-3.5 bg-purple-300 text-white text-sm font-semibold rounded-lg cursor-not-allowed transition-all">
                        Simpan Aktivitas Grading
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('gradingForm');
                const sourceSelect = document.getElementById('source_parent_grade_company_id');
                const totalWeightInput = document.getElementById('total_weight');
                const availableStockBadge = document.getElementById('available-stock-badge');
                
                const rowsContainer = document.getElementById('rowsContainer');
                const btnAddRow = document.getElementById('btnAddRow');
                const btnSubmit = document.getElementById('btnSubmit');

                const calcProcessed = document.getElementById('calc-processed');
                const calcResult = document.getElementById('calc-result');
                const calcDiff = document.getElementById('calc-diff');
                const calcDiffContainer = document.getElementById('calc-diff-container');

                // Data Master dari Blade untuk filter Detail Grade
                const allParentGrades = @json($allParentGrades);
                const gradeCompanies = @json($gradeCompanies);

                let rowCounter = 0;

                // Update Display Sisa Stok Asal
                function updateAvailableStock() {
                    const selectedOpt = sourceSelect.options[sourceSelect.selectedIndex];
                    const stock = parseFloat(selectedOpt.getAttribute('data-stock') || 0);
                    availableStockBadge.textContent = formatDecimal(stock) + ' gr';
                    totalWeightInput.setAttribute('max', stock);
                    calculateTotals();
                }

                sourceSelect.addEventListener('change', updateAvailableStock);
                totalWeightInput.addEventListener('input', calculateTotals);

                // Tambah Baris Dinamis
                function addTargetRow() {
                    const rowIndex = rowCounter++;

                    const rowHtml = `
                        <tr class="hover:bg-gray-50 transition-colors" id="row-${rowIndex}">
                            <td class="px-4 py-3">
                                <select name="targets[${rowIndex}][parent_grade_company_id]" required
                                    class="w-full text-sm border-gray-300 rounded-md shadow-sm p-2.5 focus:ring-purple-500 focus:border-purple-500 parent-select"
                                    data-row-id="${rowIndex}">
                                    <option value="">-- Pilih Parent --</option>
                                    ${allParentGrades.map(pg => `<option value="${pg.id}">${pg.name}</option>`).join('')}
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <select name="targets[${rowIndex}][grade_company_id]"
                                    class="w-full text-sm border-gray-300 rounded-md shadow-sm p-2.5 focus:ring-purple-500 focus:border-purple-500 grade-select"
                                    id="grade-select-${rowIndex}">
                                    <option value="">-- Semua Detail Grade --</option>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" step="0.01" min="0.01" name="targets[${rowIndex}][weight]" required
                                    placeholder="0.00"
                                    class="w-full text-sm border-gray-300 rounded-md shadow-sm p-2.5 focus:ring-purple-500 focus:border-purple-500 weight-input">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button type="button" class="btn-remove-row text-red-500 hover:text-red-700 transition"
                                    data-row-id="${rowIndex}">
                                    <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    `;

                    rowsContainer.insertAdjacentHTML('beforeend', rowHtml);

                    const newRow = document.getElementById(`row-${rowIndex}`);
                    
                    // Event handler: Filter Grade Company berdasarkan Parent Grade pilihan
                    const newParentSelect = newRow.querySelector('.parent-select');
                    const newGradeSelect = newRow.querySelector('.grade-select');
                    
                    newParentSelect.addEventListener('change', function() {
                        const parentId = this.value;
                        newGradeSelect.innerHTML = '<option value="">-- Semua Detail Grade --</option>';

                        if (parentId) {
                            const filteredGrades = gradeCompanies.filter(g => g.parent_grade_company_id == parentId);
                            filteredGrades.forEach(g => {
                                const opt = document.createElement('option');
                                opt.value = g.id;
                                opt.textContent = g.name;
                                newGradeSelect.appendChild(opt);
                            });
                        }
                        calculateTotals();
                    });

                    // Event handler untuk hitung live ketika berat di baris diubah
                    const newWeightInput = newRow.querySelector('.weight-input');
                    newWeightInput.addEventListener('input', calculateTotals);

                    // Event handler untuk hapus baris
                    const newBtnRemove = newRow.querySelector('.btn-remove-row');
                    newBtnRemove.addEventListener('click', function() {
                        const rId = this.getAttribute('data-row-id');
                        document.getElementById(`row-${rId}`).remove();
                        calculateTotals();
                    });

                    calculateTotals();
                }

                // Kalkulasi Live dan Validasi
                function calculateTotals() {
                    const selectedOpt = sourceSelect.options[sourceSelect.selectedIndex];
                    const availableStock = parseFloat(selectedOpt.getAttribute('data-stock') || 0);

                    const totalWeight = parseFloat(totalWeightInput.value || 0);
                    calcProcessed.textContent = formatDecimal(totalWeight) + ' gr';

                    // Hitung total berat pecahan dari baris dinamis
                    let targetsSum = 0;
                    const weightInputs = document.querySelectorAll('.weight-input');
                    weightInputs.forEach(input => {
                        targetsSum += parseFloat(input.value || 0);
                    });

                    calcResult.textContent = formatDecimal(targetsSum) + ' gr';

                    // Hitung selisih
                    const diff = totalWeight - targetsSum;
                    calcDiff.textContent = formatDecimal(Math.abs(diff)) + ' gr';

                    // Format warna & pesan penyeimbang
                    calcDiffContainer.className = "p-3 rounded-lg shadow-sm border text-center";
                    
                    // Gating validasi tombol submit
                    const isPerfectMatch = Math.abs(diff) < 0.009 && totalWeight > 0;
                    const isWithinStock = totalWeight <= availableStock && totalWeight > 0;
                    const hasTargets = weightInputs.length > 0;

                    if (isPerfectMatch && isWithinStock && hasTargets) {
                        calcDiffContainer.classList.add("bg-green-50", "border-green-300", "text-green-800");
                        calcDiff.textContent = "PAS (0,00 gr)";
                        
                        btnSubmit.removeAttribute('disabled');
                        btnSubmit.className = "flex-1 inline-flex items-center justify-center px-4 py-3.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-lg shadow-md hover:shadow-lg transition-all";
                    } else {
                        calcDiffContainer.classList.add("bg-red-50", "border-red-300", "text-red-800");
                        
                        btnSubmit.setAttribute('disabled', 'disabled');
                        btnSubmit.className = "flex-1 inline-flex items-center justify-center px-4 py-3.5 bg-purple-300 text-white text-sm font-semibold rounded-lg cursor-not-allowed transition-all";
                        
                        if (totalWeight > availableStock) {
                            calcDiff.textContent = "Stok Melebihi Batas!";
                        } else if (diff > 0) {
                            calcDiff.textContent = `Kurang ${formatDecimal(diff)} gr`;
                        } else if (diff < 0) {
                            calcDiff.textContent = `Lebih ${formatDecimal(Math.abs(diff))} gr`;
                        }
                    }
                }

                function formatDecimal(val) {
                    return val.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }

                // Inisialisasi: Mulai dengan 1 baris kosong
                btnAddRow.addEventListener('click', addTargetRow);
                addTargetRow();
                updateAvailableStock();
            });
        </script>
    @endpush
@endsection
