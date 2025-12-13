@extends('layouts.app')

@section('title', 'Edit Data Grading')

@section('content')
    <div class="bg-gray-50 min-h-screen py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Edit Data Grading</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Receipt Item ID: {{ $receiptItem->id }} |
                        Supplier: {{ $receiptItem->purchaseReceipt->supplier->name ?? '-' }} |
                        Grade: {{ $receiptItem->gradeSupplier->name ?? '-' }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('grading-goods.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-md hover:bg-gray-100 text-sm">
                        Kembali
                    </a>
                </div>
            </div>

            <!-- ✅ Info Barang Asal -->
            <div class="bg-white shadow rounded-lg border mb-6 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Informasi Barang Asal</h2>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Supplier</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $receiptItem->purchaseReceipt->supplier->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Grade Supplier</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $receiptItem->gradeSupplier->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Tanggal Kedatangan</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ optional($receiptItem->purchaseReceipt->receipt_date)->format('d/m/Y') ?? '-' }}
                        </p>
                    </div>
                    <!-- ✅ Kolom Berat Nota -->
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Berat Nota Supplier</p>
                        <p class="text-lg font-bold text-orange-600">
                            {{ number_format($receiptItem->supplier_weight_grams ?? 0, 0, ',', '.') }} gr
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Berat Barang di Gudang</p>
                        <p class="text-lg font-bold text-blue-600">
                            {{ number_format($receiptItem->warehouse_weight_grams ?? 0, 0, ',', '.') }} gr
                        </p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('grading-goods.update', $receiptItem->id) }}" id="editForm"
                class="space-y-6">
                @csrf
                @method('PUT')

                <!-- ✅ Global Notes -->
                <div class="bg-white shadow-md border rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Catatan Global</h2>
                    <div>
                        <label for="global_notes" class="block text-sm font-medium text-gray-700">Catatan
                            Keseluruhan</label>
                        <textarea name="global_notes" id="global_notes" rows="3"
                            class="mt-1 block w-full sm:text-sm border rounded-md bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            placeholder="Catatan yang berlaku untuk semua grade hasil...">{{ old('global_notes', $allGradingResults->first()->notes ?? '') }}</textarea>
                    </div>
                </div>

                <!-- ✅ Multiple Grade Forms -->
                <div class="bg-white shadow-md border rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b bg-gray-50">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Edit Grade Hasil
                                ({{ $allGradingResults->count() }} grade)</h2>
                            <button type="button" onclick="addGradeForm()"
                                class="inline-flex items-center px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Grade
                            </button>
                        </div>
                    </div>

                    <div id="grades-container" class="divide-y divide-gray-200">
                        @foreach ($allGradingResults as $index => $result)
                            <div class="grade-form p-6" data-index="{{ $index }}">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-md font-medium text-gray-900">Grade #{{ $index + 1 }}</h3>
                                    @if ($index > 0)
                                        <button type="button" onclick="removeGradeForm(this)"
                                            class="text-red-600 hover:text-red-800 text-sm font-medium">
                                            Hapus Grade
                                        </button>
                                    @endif
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tanggal Grading</label>
                                        <input type="date" name="grades[{{ $index }}][grading_date]"
                                            value="{{ old('grades.' . $index . '.grading_date', \Carbon\Carbon::parse($result->grading_date)->format('Y-m-d')) }}"
                                            class="mt-1 block w-full sm:text-sm border rounded-md bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                                            required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Grade Company</label>
                                        <input type="text" name="grades[{{ $index }}][grade_company_name]"
                                            value="{{ old('grades.' . $index . '.grade_company_name', $result->gradeCompany->name ?? '') }}"
                                            class="mt-1 block w-full sm:text-sm border rounded-md bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                                            placeholder="Nama grade company..." list="gradeCompanies" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Jumlah Item</label>
                                        <input type="number" name="grades[{{ $index }}][quantity]" min="0"
                                            step="1"
                                            value="{{ old('grades.' . $index . '.quantity', $result->quantity ?? 0) }}"
                                            class="mt-1 block w-full sm:text-sm border rounded-md bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                                            required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Berat Hasil (gr)</label>
                                        <input type="number" name="grades[{{ $index }}][weight_grams]"
                                            min="0" step="1"
                                            value="{{ old('grades.' . $index . '.weight_grams', $result->weight_grams ?? 0) }}"
                                            class="mt-1 block w-full sm:text-sm border rounded-md bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                                            required>
                                    </div>

                                    <!-- ✅ Field Jenis Barang Keluar -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Barang
                                            Keluar</label>
                                        <select name="grades[{{ $index }}][outgoing_type]"
                                            class="mt-1 block w-full sm:text-sm border rounded-md bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                                            <option value="">Pilih Jenis Keluar</option>
                                            <option value="penjualan_langsung"
                                                {{ old('grades.' . $index . '.outgoing_type', $result->outgoing_type) == 'penjualan_langsung' ? 'selected' : '' }}>
                                                Penjualan Langsung</option>
                                            <option value="internal"
                                                {{ old('grades.' . $index . '.outgoing_type', $result->outgoing_type) == 'internal' ? 'selected' : '' }}>Internal
                                            </option>
                                            <option value="external"
                                                {{ old('grades.' . $index . '.outgoing_type', $result->outgoing_type) == 'external' ? 'selected' : '' }}>External
                                            </option>
                                        </select>
                                    </div>

                                    <!-- ✅ Field Kategori Grade -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Grade</label>
                                        <select name="grades[{{ $index }}][category_grade]"
                                            class="mt-1 block w-full sm:text-sm border rounded-md bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                                            <option value="">Pilih Kategori</option>
                                            <option value="IDM A"
                                                {{ old('grades.' . $index . '.category_grade', $result->category_grade) == 'IDM A' ? 'selected' : '' }}>IDM A</option>
                                            <option value="IDM B"
                                                {{ old('grades.' . $index . '.category_grade', $result->category_grade) == 'IDM B' ? 'selected' : '' }}>IDM B</option>
                                        </select>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700">Catatan Khusus</label>
                                        <textarea name="grades[{{ $index }}][notes]" rows="2"
                                            class="mt-1 block w-full sm:text-sm border rounded-md bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                                            placeholder="Catatan khusus untuk grade ini...">{{ old('grades.' . $index . '.notes', $result->notes ?? '') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="bg-gray-50 p-4 border-t flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3">
                        <a href="{{ route('grading-goods.index') }}"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-md border border-gray-200 bg-white text-sm text-gray-700 hover:bg-gray-100 w-full sm:w-auto">
                            Batal
                        </a>
                        <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm w-full sm:w-auto">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>

            <datalist id="gradeCompanies">
                @foreach ($allGradeCompanies as $gradeCompany)
                    <option value="{{ $gradeCompany->name }}">
                @endforeach
            </datalist>
        </div>
    </div>

    @push('scripts')
        <script>
            let gradeIndex = {{ $allGradingResults->count() }};

            function addGradeForm() {
                const container = document.getElementById('grades-container');
                const newGradeHtml = `
        <div class="grade-form p-6" data-index="${gradeIndex}">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-md font-medium text-gray-900">Grade #${gradeIndex + 1}</h3>
                <button type="button" onclick="removeGradeForm(this)" 
                        class="text-red-600 hover:text-red-800 text-sm font-medium">
                    Hapus Grade
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tanggal Grading</label>
                    <input type="date" name="grades[${gradeIndex}][grading_date]" 
                           value="${new Date().toISOString().split('T')[0]}"
                           class="mt-1 block w-full sm:text-sm border rounded-md bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Grade Company</label>
                    <input type="text" name="grades[${gradeIndex}][grade_company_name]" 
                           class="mt-1 block w-full sm:text-sm border rounded-md bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                           placeholder="Nama grade company..."
                           list="gradeCompanies"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Jumlah Item</label>
                    <input type="number" name="grades[${gradeIndex}][quantity]" min="0" step="1"
                           class="mt-1 block w-full sm:text-sm border rounded-md bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Berat Hasil (gr)</label>
                    <input type="number" name="grades[${gradeIndex}][weight_grams]" min="0" step="1"
                           class="mt-1 block w-full sm:text-sm border rounded-md bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                           required>
                </div>

                <!-- ✅ Field Jenis Barang Keluar -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Jenis Barang Keluar</label>
                    <select name="grades[${gradeIndex}][outgoing_type]" 
                            class="mt-1 block w-full sm:text-sm border rounded-md bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">Pilih Jenis Keluar</option>
                        <option value="penjualan_langsung">Penjualan Langsung</option>
                        <option value="internal">Internal</option>
                        <option value="external">External</option>
                    </select>
                </div>

                <!-- ✅ Field Kategori Grade -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Kategori Grade</label>
                    <select name="grades[${gradeIndex}][category_grade]" 
                            class="mt-1 block w-full sm:text-sm border rounded-md bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">Pilih Kategori</option>
                        <option value="IDM A">IDM A</option>
                        <option value="IDM B">IDM B</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Catatan Khusus</label>
                    <textarea name="grades[${gradeIndex}][notes]" rows="2"
                              class="mt-1 block w-full sm:text-sm border rounded-md bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                              placeholder="Catatan khusus untuk grade ini..."></textarea>
                </div>
            </div>
        </div>
    `;

                container.insertAdjacentHTML('beforeend', newGradeHtml);
                gradeIndex++;
            }

            function removeGradeForm(button) {
                const gradeForm = button.closest('.grade-form');
                gradeForm.remove();

                // Update grade numbers
                const gradeForms = document.querySelectorAll('.grade-form');
                gradeForms.forEach((form, index) => {
                    const header = form.querySelector('h3');
                    header.textContent = `Grade #${index + 1}`;
                });
            }
        </script>
    @endpush
@endsection