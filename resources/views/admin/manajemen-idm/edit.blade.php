@extends('layouts.app')

@section('title', 'Edit IDM')


@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Edit IDM</h1>
                    <p class="mt-1 text-sm text-gray-600">Edit data hasil regrading IDM.</p>
                </div>
                <a href="{{ route('manajemen-idm.index', ['page' => $page, 'supplier_id' => $supplier_id, 'grade_company_id' => $grade_company_id, 'category_grade' => $category_grade]) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                    Kembali
                </a>
            </div>

            @if($idmManagement->is_transferred)
                <div class="mb-6 bg-yellow-50 border border-yellow-300 rounded-lg p-4 flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-yellow-800">Data tidak dapat diubah</p>
                        <p class="text-sm text-yellow-700 mt-0.5">Output IDM sudah keluar via transfer/sale. Batalkan transfer/sale terlebih dahulu untuk dapat mengedit data ini.</p>
                    </div>
                </div>
            @endif

            <!-- Item Info -->
            <div class="bg-gray-50 shadow-sm border rounded-lg p-6 mb-6">
                <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Grade Input</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-semibold flex items-center gap-2">
                            {{ $idmManagement->gradeCompany->name ?? '-' }}
                            @php $category = $idmManagement->sourceItems->first()->category_grade ?? '-'; @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $category == 'IDM A' ? 'bg-green-100 text-green-800' : ($category == 'IDM B' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ $category }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Supplier</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $idmManagement->supplier->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tanggal Grading</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $idmManagement->grading_date ? \Carbon\Carbon::parse($idmManagement->grading_date)->format('d/m/Y') : '-' }}</dd>
                    </div>
                </dl>
            </div>

            <form action="{{ route('manajemen-idm.update', $idmManagement->id) }}" method="POST" id="step2Form">
                @csrf
                @method('PUT')
                <input type="hidden" name="page" value="{{ $page }}">
                <input type="hidden" name="supplier_id" value="{{ $supplier_id }}">
                <input type="hidden" name="grade_company_id" value="{{ $grade_company_id }}">
                <input type="hidden" name="category_grade" value="{{ $category_grade }}">

                @php
                    $idmRow     = $idmManagement->details->firstWhere('grade_idm_name', 'IDM');
                    $kakianRow  = $idmManagement->details->firstWhere('grade_idm_name', 'KAKIAN');
                    $perutanRow = $idmManagement->details->firstWhere('grade_idm_name', 'PERUTAN');
                    $aluRow     = $idmManagement->details->firstWhere('grade_idm_name', 'ALU');
                    $disabled   = $idmManagement->is_transferred ? 'disabled' : '';
                @endphp

                <div class="bg-white shadow-sm border rounded-lg p-6 max-w-2xl">
                    <div class="space-y-6">

                        <!-- Berat Awal -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <label class="block text-sm font-medium text-gray-700">Berat Awal</label>
                                <input type="number" id="total_weight" value="{{ $idmManagement->initial_weight }}" readonly
                                    class="block w-full rounded-md border-gray-300 bg-gray-100 focus:outline-none text-base py-2.5 px-4 col-span-2">
                            </div>
                        </div>

                        <!-- IDM (wajib) — langsung ke parent IDM -->
                        <div class="border border-blue-200 rounded-lg p-4 bg-blue-50">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <label class="block text-sm font-medium text-gray-700">Berat IDM <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" name="details[IDM][weight]" id="weight_idm"
                                    value="{{ old('details.IDM.weight', $idmRow->weight ?? 0) }}" placeholder="0.00" required min="0.01" {{ $disabled }}
                                    class="block w-full rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4 col-span-2 weight-input {{ $disabled ? 'bg-gray-100' : '' }}">
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Output langsung ke grade parent IDM</p>
                        </div>

                        <!-- KAKIAN -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <label class="block text-sm font-medium text-gray-700">Berat Kakian</label>
                                <input type="number" step="0.01" name="details[KAKIAN][weight]" id="weight_kakian"
                                    value="{{ old('details.KAKIAN.weight', $kakianRow->weight ?? 0) }}" placeholder="0.00" min="0" {{ $disabled }}
                                    class="block w-full rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4 col-span-2 weight-input {{ $disabled ? 'bg-gray-100' : '' }}">
                            </div>
                        </div>

                        <!-- PERUTAN -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <label class="block text-sm font-medium text-gray-700">Berat Perutan</label>
                                <input type="number" step="0.01" name="details[PERUTAN][weight]" id="weight_perutan"
                                    value="{{ old('details.PERUTAN.weight', $perutanRow->weight ?? 0) }}" placeholder="0.00" min="0" {{ $disabled }}
                                    class="block w-full rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4 col-span-2 weight-input {{ $disabled ? 'bg-gray-100' : '' }}">
                            </div>
                        </div>

                        <!-- ALU -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <label class="block text-sm font-medium text-gray-700">Berat Alu/Afkir</label>
                                <input type="number" step="0.01" name="details[ALU][weight]" id="weight_alu"
                                    value="{{ old('details.ALU.weight', $aluRow->weight ?? 0) }}" placeholder="0.00" min="0" {{ $disabled }}
                                    class="block w-full rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4 col-span-2 weight-input {{ $disabled ? 'bg-gray-100' : '' }}">
                            </div>
                        </div>

                        <!-- Susut (auto) -->
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <label class="block text-sm font-medium text-gray-700">Susut (otomatis)</label>
                                <div class="relative col-span-2">
                                    <input type="number" step="0.01" id="shrinkage" value="{{ $idmManagement->shrinkage }}" readonly
                                        class="block w-full rounded-md border-gray-300 bg-gray-100 pr-10 focus:outline-none text-base py-2.5 px-4">
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500 sm:text-sm">gr</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @unless($idmManagement->is_transferred)
                            <div id="weight-error" class="hidden bg-red-50 border border-red-300 rounded-lg p-3 text-sm text-red-700">
                                Total berat output melebihi berat awal. Kurangi salah satu berat.
                            </div>

                            <div class="pt-4">
                                <button type="button" id="submitBtn" onclick="showConfirmationModal()"
                                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Simpan Perubahan
                                </button>
                            </div>
                        @endunless
                    </div>
                </div>
            </form>
        </div>
    </div>

    @unless($idmManagement->is_transferred)
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
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Konfirmasi Perubahan IDM</h3>
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
                        Simpan Perubahan
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
    @endunless
@endsection
