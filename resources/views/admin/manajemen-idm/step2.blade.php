@extends('layouts.app')

@section('title', 'Tambah IDM - Step 2')


@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Tambah IDM Step 2</h1>
                    <p class="mt-1 text-sm text-gray-600">Isi grade IDM (wajib) + berat per bin output.</p>
                </div>
                <a href="{{ route('manajemen-idm.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                    Kembali
                </a>
            </div>

            <!-- Progress Steps -->
            <div class="mb-8 bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center justify-between max-w-2xl mx-auto">
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 flex items-center justify-center rounded-full bg-green-100 text-green-600 font-semibold text-sm">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-green-600">Pilih Item</span>
                    </div>
                    <div class="flex-1 h-0.5 bg-blue-200 mx-2 sm:mx-4 -mt-6"></div>
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-500 text-white font-semibold text-sm shadow-sm">
                            2
                        </div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-blue-600">Isi Output</span>
                    </div>
                    <div class="flex-1 h-0.5 bg-gray-200 mx-2 sm:mx-4 -mt-6"></div>
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-400 font-semibold text-sm">
                            3
                        </div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-gray-400">Konfirmasi</span>
                    </div>
                </div>
            </div>

            <!-- Item Info -->
            <div class="bg-gray-50 shadow-sm border rounded-lg p-6 mb-6">
                <dl class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Grade Input</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ $firstItem->gradeCompany->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Kategori IDM</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($firstItem->category_grade ?? '') == 'IDM A' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $firstItem->category_grade ?? '-' }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Supplier</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ optional($firstItem->receiptItem?->purchaseReceipt)->supplier->name ?? '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tanggal Grading</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $firstItem->grading_date ? \Carbon\Carbon::parse($firstItem->grading_date)->format('d/m/Y') : '-' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <form action="{{ route('manajemen-idm.store-step2') }}" method="POST" id="step2Form">
                @csrf
                @foreach ($itemIds as $id)
                    <input type="hidden" name="item_ids[]" value="{{ $id }}">
                @endforeach
                <input type="hidden" name="grade_company_id" value="{{ $firstItem->grade_company_id }}">

                <div class="bg-white shadow-sm border rounded-lg p-6 max-w-2xl">
                    <div class="space-y-6">

                        <!-- Berat Awal -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <label class="block text-sm font-medium text-gray-700">Berat Awal</label>
                                <input type="number" id="total_weight" value="{{ $totalWeight }}" readonly
                                    class="block w-full rounded-md border-gray-300 bg-gray-100 focus:outline-none text-base py-2.5 px-4 col-span-2">
                            </div>
                        </div>

                        <!-- Berat IDM (wajib) — langsung ke parent IDM -->
                        <div class="border border-blue-200 rounded-lg p-4 bg-blue-50">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <label class="block text-sm font-medium text-gray-700">
                                    Berat IDM <span class="text-red-500">*</span>
                                </label>
                                <input type="number" step="0.01" name="details[IDM][weight]" id="weight_idm"
                                    placeholder="0.00" required min="0.01" value="{{ old('details.IDM.weight') }}"
                                    class="block w-full rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4 col-span-2 weight-input">
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Output langsung ke grade parent IDM</p>
                        </div>

                        <!-- Berat KAKIAN (opsional) -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <label class="block text-sm font-medium text-gray-700">Berat Kakian</label>
                                <input type="number" step="0.01" name="details[KAKIAN][weight]" id="weight_kakian"
                                    placeholder="0.00" min="0" value="{{ old('details.KAKIAN.weight', 0) }}"
                                    class="block w-full rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4 col-span-2 weight-input">
                            </div>
                        </div>

                        <!-- Berat PERUTAN (opsional) -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <label class="block text-sm font-medium text-gray-700">Berat Perutan</label>
                                <input type="number" step="0.01" name="details[PERUTAN][weight]" id="weight_perutan"
                                    placeholder="0.00" min="0" value="{{ old('details.PERUTAN.weight', 0) }}"
                                    class="block w-full rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4 col-span-2 weight-input">
                            </div>
                        </div>

                        <!-- Berat ALU (opsional) -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <label class="block text-sm font-medium text-gray-700">Berat Alu/Afkir</label>
                                <input type="number" step="0.01" name="details[ALU][weight]" id="weight_alu"
                                    placeholder="0.00" min="0" value="{{ old('details.ALU.weight', 0) }}"
                                    class="block w-full rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4 col-span-2 weight-input">
                            </div>
                        </div>

                        <!-- Susut (auto) -->
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <label class="block text-sm font-medium text-gray-700">Susut (otomatis)</label>
                                <div class="relative col-span-2">
                                    <input type="number" step="0.01" id="shrinkage" placeholder="0.00" readonly
                                        class="block w-full rounded-md border-gray-300 bg-gray-100 pr-10 focus:outline-none text-base py-2.5 px-4">
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500 sm:text-sm">gr</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Error: melebihi berat awal -->
                        <div id="weight-error" class="hidden bg-red-50 border border-red-300 rounded-lg p-3 text-sm text-red-700">
                            Total berat output melebihi berat awal. Kurangi salah satu berat.
                        </div>

                        <!-- Submit -->
                        <div class="pt-4">
                            <button type="button" id="submitBtn" onclick="showConfirmationModal()"
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                Lanjut ke Konfirmasi
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity" onclick="closeConfirmationModal()"></div>

            <div class="relative inline-block w-full max-w-md overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="w-full">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Konfirmasi Output IDM</h3>
                            <div class="mt-4 text-left">
                                <div class="bg-gray-50 rounded-lg p-4 space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Berat Awal:</span>
                                        <span class="font-medium text-gray-900" id="modal-total-weight"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">IDM:</span>
                                        <span class="font-medium text-gray-900" id="modal-weight-idm"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Kakian:</span>
                                        <span class="font-medium text-gray-900" id="modal-weight-kakian"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Perutan:</span>
                                        <span class="font-medium text-gray-900" id="modal-weight-perutan"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Alu/Afkir:</span>
                                        <span class="font-medium text-gray-900" id="modal-weight-alu"></span>
                                    </div>
                                    <div class="flex justify-between pt-2 border-t border-gray-200 font-semibold">
                                        <span class="text-gray-700">Susut:</span>
                                        <span class="text-red-600" id="modal-shrinkage"></span>
                                    </div>
                                </div>
                                <p class="mt-4 text-sm text-gray-500">Pastikan data sudah benar sebelum menyimpan.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-row justify-between gap-3">
                    <button type="button" onclick="closeConfirmationModal()"
                        class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                    <button type="button" onclick="document.getElementById('step2Form').submit()"
                        class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:w-auto sm:text-sm">
                        Simpan Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const totalWeightInput = document.getElementById('total_weight');
                const shrinkageInput   = document.getElementById('shrinkage');
                const submitBtn        = document.getElementById('submitBtn');
                const weightError      = document.getElementById('weight-error');

                function calculate() {
                    const totalWeight = parseFloat(totalWeightInput.value) || 0;
                    const wIdm        = parseFloat(document.getElementById('weight_idm').value) || 0;
                    const wKakian     = parseFloat(document.getElementById('weight_kakian').value) || 0;
                    const wPerutan    = parseFloat(document.getElementById('weight_perutan').value) || 0;
                    const wAlu        = parseFloat(document.getElementById('weight_alu').value) || 0;
                    const shrinkage   = totalWeight - wIdm - wKakian - wPerutan - wAlu;
                    shrinkageInput.value = shrinkage.toFixed(2);

                    if (shrinkage < 0) {
                        weightError.classList.remove('hidden');
                        submitBtn.disabled = true;
                    } else {
                        weightError.classList.add('hidden');
                        submitBtn.disabled = false;
                    }
                }

                document.querySelectorAll('.weight-input').forEach(el => el.addEventListener('input', calculate));
                calculate();

                window.showConfirmationModal = function () {
                    if (submitBtn.disabled) return;

                    document.getElementById('modal-total-weight').textContent = (totalWeightInput.value || 0) + ' gr';
                    document.getElementById('modal-weight-idm').textContent   = (document.getElementById('weight_idm').value || 0) + ' gr';
                    document.getElementById('modal-weight-kakian').textContent   = (document.getElementById('weight_kakian').value || 0) + ' gr';
                    document.getElementById('modal-weight-perutan').textContent  = (document.getElementById('weight_perutan').value || 0) + ' gr';
                    document.getElementById('modal-weight-alu').textContent      = (document.getElementById('weight_alu').value || 0) + ' gr';
                    document.getElementById('modal-shrinkage').textContent       = (shrinkageInput.value || 0) + ' gr';
                    document.getElementById('confirmationModal').classList.remove('hidden');
                };

                window.closeConfirmationModal = function () {
                    document.getElementById('confirmationModal').classList.add('hidden');
                };
            });
        </script>
    @endpush
@endsection
