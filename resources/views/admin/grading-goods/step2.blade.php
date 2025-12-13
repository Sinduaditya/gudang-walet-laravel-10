@extends('layouts.app')
@section('title', 'Input Grading - Step 2')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Input Grading - Step 2</h1>
                    <p class="mt-1 text-sm text-gray-600">Lengkapi hasil grading untuk item yang dipilih (Multiple Grade).
                    </p>
                </div>
                <a href="{{ route('grading-goods.step1') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                    Kembali
                </a>
            </div>

            <!-- Progress Steps -->
            <div class="mb-8 bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center justify-between max-w-xl mx-auto">
                    <div class="flex flex-col items-center flex-1">
                        <div
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-green-100 text-green-600 font-semibold text-sm">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-green-600">Pilih Item</span>
                    </div>
                    <div class="flex-1 h-0.5 bg-blue-200 mx-2 sm:mx-4 -mt-6"></div>
                    <div class="flex flex-col items-center flex-1">
                        <div
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-500 text-white font-semibold text-sm shadow-sm">
                            2</div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-blue-600">Lengkapi Data</span>
                    </div>
                </div>
            </div>

            <!-- Item Info -->
            <div class="bg-gray-50 shadow-sm border rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Item Asal</h3>
                <dl class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tgl Grading</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $sortingResult->grading_date ? \Carbon\Carbon::parse($sortingResult->grading_date)->format('d/m/Y') : '-' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Grade Supplier</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-medium">
                            {{ optional($sortingResult->receiptItem->gradeSupplier)->name ?? 'N/A' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Supplier</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ optional($sortingResult->receiptItem->purchaseReceipt->supplier)->name ?? 'N/A' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Berat Awal</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-bold text-blue-600">
                            {{ number_format($sortingResult->receiptItem->warehouse_weight_grams ?? 0) }} gram
                        </dd>
                    </div>
                </dl>
            </div>
            <form method="POST" action="{{ route('grading-goods.store.step2', ['id' => $sortingResult->id]) }}"
                id="gradingForm">
                @csrf

                <!-- Grading Results -->
                <div class="bg-white shadow-sm border rounded-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Hasil Grading</h3>
                            <p class="text-sm text-gray-600">Tambahkan satu atau lebih grade hasil dari grading</p>
                        </div>
                        <button type="button" onclick="addNewGrade()"
                            class="bg-blue-500 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            + Tambah Grade
                        </button>
                    </div>

                    <div id="gradesContainer">
                        <!-- Existing grades or default first grade -->
                        @if (old('grades'))
                            @foreach (old('grades') as $index => $grade)
                                <div class="grade-row border border-gray-200 rounded-lg p-4 mb-4"
                                    data-index="{{ $index }}">
                                    @include('admin.grading-goods.partials.grade-row', [
                                        'index' => $index,
                                        'grade' => $grade,
                                    ])
                                </div>
                            @endforeach
                        @else
                            <div class="grade-row border border-gray-200 rounded-lg p-4 mb-4" data-index="0">
                                @include('admin.grading-goods.partials.grade-row', [
                                    'index' => 0,
                                    'grade' => [],
                                ])
                            </div>
                        @endif
                    </div>

                    <!-- Total Weight Display -->
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Total Berat Hasil Grading:</span>
                            <span id="totalWeightDisplay" class="text-lg font-bold text-blue-600">0 gram</span>
                        </div>
                        <div class="mt-2 text-xs text-gray-500">
                            Berat asal: <span
                                class="font-medium">{{ number_format($sortingResult->receiptItem->warehouse_weight_grams ?? 0) }}
                                gram</span>
                            | Selisih: <span id="weightDifferenceDisplay" class="font-medium">0 gram</span>
                        </div>

                    </div>


                    @error('grades')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror


                    <!-- Global Notes -->
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <label for="global_notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Catatan Grading (Opsional)
                        </label>
                        <textarea name="global_notes" id="global_notes" rows="3"
                            placeholder="Catatan keseluruhan tentang proses grading ini..."
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('global_notes') }}</textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-600">
                                <span class="font-medium" id="gradeCountDisplay">1</span> grade hasil grading
                            </div>
                            <button type="submit"
                                class="inline-flex items-center px-6 py-2.5 bg-blue-500 text-white rounded-md hover:bg-blue-700 font-medium">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Simpan Hasil Grading
                            </button>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>

    @push('scripts')
        <script>
            let gradeIndex = {{ old('grades') ? count(old('grades')) : 1 }};
            const originalWeight = {{ $sortingResult->receiptItem->warehouse_weight_grams ?? 0 }};

            function addNewGrade() {
                const container = document.getElementById('gradesContainer');
                const newGradeHtml = `
            <div class="grade-row border border-gray-200 rounded-lg p-4 mb-4" data-index="${gradeIndex}">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-medium text-sm text-gray-700">Grade ${gradeIndex + 1}</h4>
                    <button type="button" onclick="removeGrade(this)" 
                        class="text-red-600 hover:text-red-800 text-sm">
                        Hapus
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Grade Company Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Grade Perusahaan</label>
                        <input type="text" name="grades[${gradeIndex}][grade_company_name]" required
                            placeholder="Contoh: A, B, C, Super"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            list="grade-company-options">
                    </div>

                    <!-- Weight -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Berat (gram)</label>
                        <input type="number" step="0.01" name="grades[${gradeIndex}][weight_grams]" required
                            class="grade-weight w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onchange="calculateTotalWeight()">
                    </div>

                    <!-- Quantity -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Item</label>
                        <input type="number" name="grades[${gradeIndex}][quantity]" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Notes for this grade -->
                <div class="mt-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Grade Ini</label>
                    <input type="text" name="grades[${gradeIndex}][notes]"
                        placeholder="Catatan khusus untuk grade ini (opsional)"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

        <div class="mt-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Barang Keluar</label>
            <select name="grades[${gradeIndex}][outgoing_type]" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Pilih Jenis Keluar</option>
                <option value="penjualan_langsung">Penjualan Langsung</option>
                <option value="internal">Internal</option>
                <option value="external">External</option>
            </select>
        </div>
        
        <div class="mt-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Grade</label>
            <select name="grades[${gradeIndex}][category_grade]" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Pilih Kategori</option>
                <option value="IDM A">IDM A</option>
                <option value="IDM B">IDM B</option>
            </select>
        </div>
            </div>
        `;

                container.insertAdjacentHTML('beforeend', newGradeHtml);
                gradeIndex++;
                updateGradeNumbers();
                calculateTotalWeight();
            }

            function removeGrade(button) {
                if (document.querySelectorAll('.grade-row').length <= 1) {
                    alert('Harus ada minimal 1 grade hasil');
                    return;
                }

                button.closest('.grade-row').remove();
                updateGradeNumbers();
                calculateTotalWeight();
            }

            function updateGradeNumbers() {
                const grades = document.querySelectorAll('.grade-row');
                const gradeCount = grades.length;

                grades.forEach((grade, index) => {
                    const title = grade.querySelector('h4');
                    title.textContent = `Grade ${index + 1}`;
                });

                document.getElementById('gradeCountDisplay').textContent = gradeCount;
            }

            function calculateTotalWeight() {
                const weightInputs = document.querySelectorAll('.grade-weight');
                let totalWeight = 0;

                weightInputs.forEach(input => {
                    const weight = parseFloat(input.value) || 0;
                    totalWeight += weight;
                });

                const difference = totalWeight - originalWeight;

                document.getElementById('totalWeightDisplay').textContent = totalWeight.toLocaleString('id-ID') + ' gram';

                const differenceDisplay = document.getElementById('weightDifferenceDisplay');
                let differenceText = '';
                let colorClass = '';

                if (difference < 0) {
                    differenceText = `${difference.toLocaleString('id-ID')} gram (susut)`;
                    colorClass = 'text-red-600';
                } else if (difference > 0) {
                    differenceText = `+${difference.toLocaleString('id-ID')} gram (bertambah)`;
                    colorClass = 'text-green-600';
                } else {
                    differenceText = '0 gram (sama)';
                    colorClass = 'text-gray-600';
                }

                differenceDisplay.textContent = differenceText;
                differenceDisplay.className = `font-medium ${colorClass}`;
            }

            // Initial calculation
            document.addEventListener('DOMContentLoaded', function() {
                updateGradeNumbers();
                calculateTotalWeight();

                // Add event listeners to existing weight inputs
                document.querySelectorAll('.grade-weight').forEach(input => {
                    input.addEventListener('input', calculateTotalWeight);
                });
            });
        </script>
    @endpush
@endsection
