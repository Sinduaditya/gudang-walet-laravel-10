@extends('layouts.app')

@section('title', 'Edit IDM')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Edit IDM</h1>
                    <p class="mt-1 text-sm text-gray-600">Edit data estimasi IDM.</p>
                </div>
                <a href="{{ route('manajemen-idm.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                    Kembali
                </a>
            </div>

            <!-- Item Info -->
            <div class="bg-gray-50 shadow-sm border rounded-lg p-6 mb-6">
                <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Grade</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-semibold flex items-center gap-2">
                            {{ $idmManagement->gradeCompany->name ?? '-' }}
                            @php
                                $category = $idmManagement->sourceItems->first()->category_grade ?? '-';
                            @endphp
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

                <div class="bg-white shadow-sm border rounded-lg p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Left Column: Inputs -->
                        <div class="lg:col-span-2 space-y-6">

                            <!-- Berat & Harga Awal -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-center">
                                    <label class="block text-sm font-medium text-gray-700">Berat & Harga Awal</label>
                                    <input type="number" name="total_weight" id="total_weight" value="{{ $idmManagement->initial_weight }}" readonly placeholder="Berat"
                                        class="block w-full rounded-md border-gray-300 bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4">
                                    <input type="number" name="initial_price" id="initial_price" value="{{ $idmManagement->initial_price }}" placeholder="Harga Awal" required
                                        class="block w-full rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4 calc-input">
                                    <input type="text" id="total_initial" placeholder="Total" readonly
                                        class="block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-base py-2.5 px-4">
                                </div>
                            </div>

                            @php
                                $perutan = $idmManagement->details->where('grade_idm_name', 'perutan')->first();
                                $kakian = $idmManagement->details->where('grade_idm_name', 'kakian')->first();
                                $idm = $idmManagement->details->where('grade_idm_name', 'idm')->first();
                            @endphp

                            <!-- Berat Perut -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-center">
                                    <label class="block text-sm font-medium text-gray-700">Berat Perut</label>
                                    <input type="number" step="0.01" name="details[perutan][weight]" id="weight_perutan" value="{{ $perutan->weight ?? 0 }}" placeholder="Berat" required
                                        class="block w-full rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4 calc-input">
                                    <input type="number" step="0.01" name="details[perutan][price]" id="price_perutan" value="{{ $perutan->price ?? 0 }}" placeholder="Harga" required
                                        class="block w-full rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4 calc-input">
                                    <input type="text" id="total_perutan" placeholder="Total" readonly
                                        class="block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-base py-2.5 px-4">
                                </div>
                            </div>

                            <!-- Berat Kakian -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-center">
                                    <label class="block text-sm font-medium text-gray-700">Berat Kakian</label>
                                    <input type="number" step="0.01" name="details[kakian][weight]" id="weight_kakian" value="{{ $kakian->weight ?? 0 }}" placeholder="Berat" required
                                        class="block w-full rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4 calc-input">
                                    <input type="number" step="0.01" name="details[kakian][price]" id="price_kakian" value="{{ $kakian->price ?? 0 }}" placeholder="Harga" required
                                        class="block w-full rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4 calc-input">
                                    <input type="text" id="total_kakian" placeholder="Total" readonly
                                        class="block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-base py-2.5 px-4">
                                </div>
                            </div>

                            <!-- Berat IDM -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-center">
                                    <label class="block text-sm font-medium text-gray-700">Berat IDM</label>
                                    <input type="number" step="0.01" name="details[idm][weight]" id="weight_idm" value="{{ $idm->weight ?? 0 }}" placeholder="Berat" required
                                        class="block w-full rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4 calc-input">
                                    <input type="number" step="0.01" name="details[idm][price]" id="price_idm" value="{{ $idm->price ?? 0 }}" placeholder="Harga" required
                                        class="block w-full rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4 calc-input">
                                    <input type="text" id="total_idm" placeholder="Total" readonly
                                        class="block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-base py-2.5 px-4">
                                </div>
                            </div>

                            <!-- Susut -->
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-center">
                                    <label class="block text-sm font-medium text-gray-700">Susut (Shrinkage)</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="number" step="0.01" name="shrinkage" id="shrinkage" value="{{ $idmManagement->shrinkage }}" placeholder="0" required readonly
                                            class="block w-full rounded-md border-gray-300 bg-gray-100 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500 text-base py-2.5 px-4">
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                            <span class="text-gray-500 sm:text-sm">gr</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                             <!-- Total Harga Jual -->
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center pt-4 border-t">
                                <label class="block text-sm font-bold text-gray-900">Total Harga Jual</label>
                                <div>
                                    <input type="text" id="total_selling_price_display" readonly
                                        class="block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm font-bold text-gray-900 sm:text-sm">
                                </div>
                            </div>

                        </div>

                        <!-- Right Column: Summary -->
                        <div class="bg-gray-50 rounded-xl p-6 h-fit border border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Ringkasan</h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Kekurangan</label>
                                    <div class="mt-1 text-lg font-semibold text-red-600" id="shortage_display">Rp 0</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Rekomendasi Kenaikan</label>
                                    <div class="mt-1 text-lg font-semibold text-blue-600" id="recommendation_display">Rp 0</div>
                                </div>

                                <div class="pt-4 border-t border-gray-200">
                                    <label class="block text-sm font-medium text-gray-900">Estimasi Harga Jual IDM (per Gram)</label>
                                    <div class="relative rounded-md shadow-sm mt-1">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <span class="text-gray-500 sm:text-sm">Rp</span>
                                        </div>
                                        <input type="hidden" name="estimated_selling_price" id="estimated_selling_price" value="{{ $idmManagement->estimated_selling_price }}">
                                        <input type="text" id="estimated_selling_price_display" readonly
                                            class="block w-full rounded-md border-gray-300 pl-10 focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg font-bold text-green-600 bg-gray-100">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8">
                                <button type="button" onclick="showConfirmationModal()" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity" aria-hidden="true" onclick="closeConfirmationModal()"></div>

            <div class="relative inline-block w-full max-w-md overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="w-full">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Konfirmasi Perubahan IDM
                            </h3>
                            <div class="mt-4 text-left">
                                <div class="bg-gray-50 rounded-lg p-4 space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Berat Awal:</span>
                                        <span class="font-medium text-gray-900" id="modal-total-weight"></span>
                                    </div>

                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Berat Perut:</span>
                                        <span class="font-medium text-gray-900" id="modal-weight-perutan"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Berat Kakian:</span>
                                        <span class="font-medium text-gray-900" id="modal-weight-kakian"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Berat IDM:</span>
                                        <span class="font-medium text-gray-900" id="modal-weight-idm"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Susut:</span>
                                        <span class="font-medium text-red-600" id="modal-shrinkage"></span>
                                    </div>
                                    <div class="pt-2 border-t border-gray-200 flex justify-between font-semibold">
                                        <span class="text-gray-700">Estimasi Harga Jual IDM:</span>
                                        <span class="text-green-600" id="modal-total-price"></span>
                                    </div>
                                </div>
                                <p class="mt-4 text-sm text-gray-500">
                                    Pastikan data sudah benar sebelum menyimpan.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-row justify-between gap-3">
                    <button type="button" onclick="closeConfirmationModal()" class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                    <button type="button" onclick="submitForm()" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.calc-input');
            const totalWeightInput = document.getElementById('total_weight');
            const initialPriceInput = document.getElementById('initial_price');
            const shrinkageInput = document.getElementById('shrinkage');
            const totalSellingPriceDisplay = document.getElementById('total_selling_price_display');
            const shortageDisplay = document.getElementById('shortage_display');
            const recommendationDisplay = document.getElementById('recommendation_display');
            const estimatedPriceInput = document.getElementById('estimated_selling_price');
            const estimatedPriceDisplay = document.getElementById('estimated_selling_price_display');

            function formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(amount);
            }

            function calculate() {
                const totalWeight = parseFloat(totalWeightInput.value) || 0;
                const initialPrice = parseFloat(initialPriceInput.value) || 0;

                // Update Initial Total
                document.getElementById('total_initial').value = formatCurrency(totalWeight * initialPrice);

                const wPerut = parseFloat(document.getElementById('weight_perutan').value) || 0;
                const pPerut = parseFloat(document.getElementById('price_perutan').value) || 0;

                const wKakian = parseFloat(document.getElementById('weight_kakian').value) || 0;
                const pKakian = parseFloat(document.getElementById('price_kakian').value) || 0;

                const wIdm = parseFloat(document.getElementById('weight_idm').value) || 0;
                const pIdm = parseFloat(document.getElementById('price_idm').value) || 0;

                // Update Row Totals
                document.getElementById('total_perutan').value = formatCurrency(wPerut * pPerut);
                document.getElementById('total_kakian').value = formatCurrency(wKakian * pKakian);
                document.getElementById('total_idm').value = formatCurrency(wIdm * pIdm);

                // Calculate Shrinkage
                const currentTotalWeight = wPerut + wKakian + wIdm;
                const shrinkage = totalWeight - currentTotalWeight;
                shrinkageInput.value = shrinkage.toFixed(2);

                // Calculate Total Selling Price
                const totalSellingPrice = (wPerut * pPerut) + (wKakian * pKakian) + (wIdm * pIdm);
                totalSellingPriceDisplay.value = formatCurrency(totalSellingPrice);
                // estimatedPriceInput.value = totalSellingPrice; // Don't overwrite yet
                // estimatedPriceDisplay.textContent = formatCurrency(totalSellingPrice);

                // Calculate Shortage (Kekurangan)
                // Shortage = Total Initial Cost - Total Selling Price
                const totalInitialCost = totalWeight * initialPrice;
                const shortage = Math.max(0, totalInitialCost - totalSellingPrice);

                shortageDisplay.textContent = formatCurrency(shortage);

                // Calculate Recommendation (Kenaikan Harga)
                // Formula: (Kekurangan) / Berat IDM
                let recommendation = 0;
                if (wIdm > 0) {
                    recommendation = shortage / wIdm;
                }
                // Display x 1000 (likely per Kg)
                recommendationDisplay.textContent = formatCurrency(recommendation * 1000);

                // Calculate Estimated IDM Selling Price
                // Formula: (Harga Jual IDM + Kenaikan Harga)
                const estimatedIdmPrice = Math.ceil(pIdm + recommendation);
                estimatedPriceInput.value = estimatedIdmPrice;
                estimatedPriceDisplay.value = formatCurrency(estimatedIdmPrice);
            }

            inputs.forEach(input => {
                input.addEventListener('input', calculate);
            });

            // Modal Functions
            window.showConfirmationModal = function() {
                // Populate Modal Data
                document.getElementById('modal-total-weight').textContent = totalWeightInput.value + ' gr';
                // document.getElementById('modal-initial-price').textContent = formatCurrency(parseFloat(initialPriceInput.value) || 0);
                document.getElementById('modal-weight-perutan').textContent = (document.getElementById('weight_perutan').value || 0) + ' gr';
                document.getElementById('modal-weight-kakian').textContent = (document.getElementById('weight_kakian').value || 0) + ' gr';
                document.getElementById('modal-weight-idm').textContent = (document.getElementById('weight_idm').value || 0) + ' gr';
                document.getElementById('modal-shrinkage').textContent = shrinkageInput.value + ' gr';
                document.getElementById('modal-total-price').textContent = formatCurrency(parseFloat(estimatedPriceInput.value) || 0);

                document.getElementById('confirmationModal').classList.remove('hidden');
            }

            window.closeConfirmationModal = function() {
                document.getElementById('confirmationModal').classList.add('hidden');
            }

            window.submitForm = function() {
                document.getElementById('step2Form').submit();
            }

            // Trigger calculation on load
            calculate();
        });
    </script>
    @endpush
@endsection
