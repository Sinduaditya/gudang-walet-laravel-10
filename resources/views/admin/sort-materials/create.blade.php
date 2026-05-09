@extends('layouts.app')

@section('title', 'Tambah Sortir Bahan')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">Tambah Sortir Bahan</h1>
                <a href="{{ route('sort-materials.index') }}"
                    class="text-sm text-gray-600 hover:text-gray-800 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </a>
            </div>

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                <form action="{{ route('sort-materials.store') }}" method="POST" id="sortForm">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="sort_date">
                            Tanggal
                        </label>
                        <input type="date" name="sort_date" id="sort_date" value="{{ old('sort_date', date('Y-m-d')) }}"
                            required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="parent_grade_company_id">
                            Parent Grade Company
                        </label>
                        <select name="parent_grade_company_id" id="parent_grade_company_id" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Pilih Parent Grade</option>
                            @foreach ($parentGradeCompanies as $pg)
                                <option value="{{ $pg->id }}" {{ old('parent_grade_company_id') == $pg->id ? 'selected' : '' }}>
                                    {{ $pg->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Normal Fields (Non-ALU) -->
                    <div id="normalFields">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="grade_company_id">
                                Grade Company (Optional)
                            </label>
                            <select name="grade_company_id" id="grade_company_id"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">-- Pilih Grade Company --</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="weight">
                                Berat (Gram)
                            </label>
                            <input type="number" step="0.01" name="weight" id="weight" value="{{ old('weight') }}" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                                Deskripsi
                            </label>
                            <textarea name="description" id="description" rows="3"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">{{ old('description') }}</textarea>
                        </div>
                    </div>

                    <!-- ALU Fields (Hidden by default) -->
                    <div id="aluFields" class="hidden">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="grade_company_id_alu">
                                Grade Company
                            </label>
                            <select name="grade_company_id" id="grade_company_id_alu" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">-- Pilih Grade --</option>
                                @foreach($aluGrades as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->name }} (Stock: {{ number_format($grade->global_stock, 0) }} gram)</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="stockInfoAlu" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded">
                            <p class="text-sm text-gray-600">Global Stock: <span id="aluGlobalStock" class="font-bold text-blue-600">0</span> gram</p>
                        </div>

                        <!-- Action Buttons for ALU -->
                        <div id="actionSection" class="mb-4">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Pilih Aksi</h3>
                            <div class="flex gap-3">
                                <button type="button" id="btnMasukStok" onclick="selectAluAction('masuk_stok')"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium transition duration-200">
                                    MASUK STOK
                                </button>
                                <button type="button" id="btnPenjualan" onclick="selectAluAction('penjualan')"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium transition duration-200">
                                    PENJUALAN LANGSUNG
                                </button>
                            </div>
                        </div>

                        <!-- Form Fields (shown after action selection) -->
                        <div id="formSection" class="hidden">
                            <input type="hidden" name="alu_action" id="aluAction" value="">

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
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="weight_alu">
                                    Berat Input (gram)
                                </label>
                                <input type="number" step="0.01" name="weight" id="weight_alu" value="{{ old('weight') }}" required
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                    placeholder="Masukkan berat dalam gram">
                            </div>
                        </div>

                        <div class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded">
                            <p class="text-xs text-gray-600">
                                <strong>Note:</strong> ALU 100% masuk stok gudang. AF/Indomie P: sebagian masuk stok, sisa dijual otomatis.
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-3 justify-end mt-6">
                        <a href="{{ route('sort-materials.index') }}"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-medium transition duration-200">
                            Batal
                        </a>
                        <button type="submit" id="btnSubmit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium transition duration-200">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const gradeCompanies = @json($gradeCompanies);
            const parentGradeNames = @json($parentGradeNames);
            const aluGrades = @json($aluGrades);
            const destinations = @json($destinations);
            const oldParentId = "{{ old('parent_grade_company_id') }}";
            const oldGradeId = "{{ old('grade_company_id') }}";

            function toggleFormFields() {
                const selectedId = document.getElementById('parent_grade_company_id').value;
                const parentName = parentGradeNames[selectedId] || '';
                const isAlu = parentName === 'ALU';

                const normalFields = document.getElementById('normalFields');
                const aluFields = document.getElementById('aluFields');
                const normalGradeSelect = document.getElementById('grade_company_id');
                const aluGradeSelect = document.getElementById('grade_company_id_alu');
                const normalWeight = document.getElementById('weight');
                const aluWeight = document.getElementById('weight_alu');

                if (isAlu) {
                    normalFields.classList.add('hidden');
                    normalFields.querySelectorAll('input, select').forEach(el => el.disabled = true);
                    aluFields.classList.remove('hidden');
                    aluFields.querySelectorAll('input, select').forEach(el => el.disabled = false);
                    normalGradeSelect.removeAttribute('required');
                    normalWeight.removeAttribute('required');
                    aluGradeSelect.setAttribute('required', 'required');
                    aluWeight.setAttribute('required', 'required');
                } else {
                    normalFields.classList.remove('hidden');
                    normalFields.querySelectorAll('input, select').forEach(el => el.disabled = false);
                    aluFields.classList.add('hidden');
                    aluFields.querySelectorAll('input, select').forEach(el => el.disabled = true);
                    normalGradeSelect.setAttribute('required', 'required');
                    normalWeight.setAttribute('required', 'required');
                    aluGradeSelect.removeAttribute('required');
                    aluWeight.removeAttribute('required');
                }
            }

            function selectAluAction(action) {
                document.getElementById('aluAction').value = action;
                document.getElementById('actionSection').classList.add('hidden');
                document.getElementById('formSection').classList.remove('hidden');

                if (action === 'penjualan') {
                    document.getElementById('destinationDiv').classList.add('hidden');
                } else {
                    document.getElementById('destinationDiv').classList.remove('hidden');
                }

                document.getElementById('weight_alu').focus();
            }

            function updateGradeCompanyDropdown(parentId, selectedGradeId = null) {
                const gradeSelect = document.getElementById('grade_company_id');
                gradeSelect.innerHTML = '<option value="">-- Pilih Grade Company --</option>';

                if (!parentId) return;

                const filteredGrades = gradeCompanies.filter(g => g.parent_grade_company_id == parentId);

                filteredGrades.forEach(g => {
                    const option = document.createElement('option');
                    option.value = g.id;
                    option.textContent = g.name;
                    if (selectedGradeId && g.id == selectedGradeId) {
                        option.selected = true;
                    }
                    gradeSelect.appendChild(option);
                });
            }

            function updateAluStock() {
                const gradeId = parseInt(document.getElementById('grade_company_id_alu').value);
                const grade = aluGrades.find(g => g.id === gradeId);
                document.getElementById('aluGlobalStock').textContent = grade ? number_format(grade.global_stock, 0) : '0';
            }

            function number_format(num) {
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }

            document.addEventListener('DOMContentLoaded', function () {
                if (oldParentId) {
                    updateGradeCompanyDropdown(oldParentId, oldGradeId);
                }
                toggleFormFields();
                updateAluStock();
            });

            document.getElementById('parent_grade_company_id').addEventListener('change', function () {
                updateGradeCompanyDropdown(this.value);
                toggleFormFields();
            });

            document.getElementById('grade_company_id_alu').addEventListener('change', function () {
                updateAluStock();
            });

            document.getElementById('sortForm').addEventListener('submit', function(e) {
                console.log('Form submitting...');
                const selectedId = document.getElementById('parent_grade_company_id').value;
                const parentName = parentGradeNames[selectedId] || '';
                console.log('Parent:', parentName);

                if (parentName === 'ALU') {
                    const action = document.getElementById('aluAction').value;
                    console.log('ALU action:', action);

                    // For now, skip validation - just log
                    if (!action) {
                        alert('Silakan pilih aksi terlebih dahulu');
                        e.preventDefault();
                        return;
                    }
                }

                console.log('Form will submit');
                // return; // Uncomment to test only
            });
        </script>
    @endpush
@endsection