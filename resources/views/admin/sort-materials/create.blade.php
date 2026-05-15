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
                                Grade Company
                            </label>
                            <select name="grade_company_id" id="grade_company_id"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">-- Pilih Grade Company --</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1" id="normalGradeStock"></p>
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

                    <!-- AA2 AF JUAL Fields (Hidden by default) -->
                    <div id="afJualFields" class="hidden">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="grade_company_id_af_jual">
                                Grade Company
                            </label>
                            <select name="grade_company_id" id="grade_company_id_af_jual" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">-- Pilih Grade --</option>
                                @foreach($afJualGrades as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->name }} (Stock: {{ number_format($grade->global_stock, 0) }} gram)</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="stockInfoAfJual" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded">
                            <p class="text-sm text-gray-600">Global Stock: <span id="afJualGlobalStock" class="font-bold text-blue-600">0</span> gram</p>
                        </div>

                        <!-- Action Buttons for AA2 AF JUAL -->
                        <div id="afJualActionSection" class="mb-4">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Pilih Aksi</h3>
                            <div class="flex gap-3">
                                <button type="button" id="btnAfJualMasukStok" onclick="selectAfJualAction('masuk_stok')"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium transition duration-200">
                                    MASUK STOK
                                </button>
                            </div>
                        </div>

                        <!-- Form Fields (shown after action selection) -->
                        <div id="afJualFormSection" class="hidden">
                            <input type="hidden" name="af_jual_action" id="afJualAction" value="">

                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="destination_af_jual">
                                    Tujuan Sortir
                                </label>
                                <select name="destination" id="destination_af_jual"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="">-- Pilih Tujuan --</option>
                                    <option value="idm">IDM</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="weight_af_jual">
                                    Berat Input (gram)
                                </label>
                                <input type="number" step="0.01" name="weight" id="weight_af_jual" value="{{ old('weight') }}" required
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                    placeholder="Masukkan berat dalam gram">
                            </div>
                        </div>

                        <div class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded">
                            <p class="text-xs text-gray-600">
                                <strong>Note:</strong> AA2 AF JUAL 100% masuk ke IDM.
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
            const afJualGrades = @json($afJualGrades);
            const normalGradesWithStock = @json($normalGradesWithStock);
            const oldParentId = "{{ old('parent_grade_company_id') }}";
            const oldGradeId = "{{ old('grade_company_id') }}";

            const DESTINATIONS = {
                'mangkok': 'Mangkok',
                'idm': 'IDM',
                'aa': 'AA',
                'af': 'Lempeng',
            };

            function toggleFormFields() {
                const selectedId = document.getElementById('parent_grade_company_id').value;
                const parentName = parentGradeNames[selectedId] || '';
                const isAlu = parentName === 'ALU';
                const isAfJual = parentName === 'AA2 AF JUAL';

                const normalFields = document.getElementById('normalFields');
                const aluFields = document.getElementById('aluFields');
                const afJualFields = document.getElementById('afJualFields');
                const normalGradeSelect = document.getElementById('grade_company_id');
                const aluGradeSelect = document.getElementById('grade_company_id_alu');
                const afJualGradeSelect = document.getElementById('grade_company_id_af_jual');
                const normalWeight = document.getElementById('weight');
                const aluWeight = document.getElementById('weight_alu');
                const afJualWeight = document.getElementById('weight_af_jual');

                // Hide all special fields first
                normalFields.classList.add('hidden');
                aluFields.classList.add('hidden');
                afJualFields.classList.add('hidden');

                // Reset disabled states
                normalFields.querySelectorAll('input, select').forEach(el => el.disabled = true);
                aluFields.querySelectorAll('input, select').forEach(el => el.disabled = true);
                afJualFields.querySelectorAll('input, select').forEach(el => el.disabled = true);

                normalGradeSelect.removeAttribute('required');
                normalWeight.removeAttribute('required');
                aluGradeSelect.removeAttribute('required');
                aluWeight.removeAttribute('required');
                afJualGradeSelect.removeAttribute('required');
                afJualWeight.removeAttribute('required');

                if (isAlu) {
                    normalFields.classList.add('hidden');
                    normalFields.querySelectorAll('input, select').forEach(el => el.disabled = true);
                    aluFields.classList.remove('hidden');
                    aluFields.querySelectorAll('input, select').forEach(el => el.disabled = false);
                    normalGradeSelect.removeAttribute('required');
                    normalWeight.removeAttribute('required');
                    aluGradeSelect.setAttribute('required', 'required');
                    aluWeight.setAttribute('required', 'required');
                } else if (isAfJual) {
                    normalFields.classList.add('hidden');
                    normalFields.querySelectorAll('input, select').forEach(el => el.disabled = true);
                    afJualFields.classList.remove('hidden');
                    afJualFields.querySelectorAll('input, select').forEach(el => el.disabled = false);
                    normalGradeSelect.removeAttribute('required');
                    normalWeight.removeAttribute('required');
                    afJualGradeSelect.setAttribute('required', 'required');
                    afJualWeight.setAttribute('required', 'required');
                } else {
                    normalFields.classList.remove('hidden');
                    normalFields.querySelectorAll('input, select').forEach(el => el.disabled = false);
                    aluFields.classList.add('hidden');
                    aluFields.querySelectorAll('input, select').forEach(el => el.disabled = true);
                    afJualFields.classList.add('hidden');
                    afJualFields.querySelectorAll('input, select').forEach(el => el.disabled = true);
                    normalGradeSelect.setAttribute('required', 'required');
                    normalWeight.setAttribute('required', 'required');
                }
            }

            function selectAluAction(action) {
                document.getElementById('aluAction').value = action;
                document.getElementById('actionSection').classList.add('hidden');
                document.getElementById('formSection').classList.remove('hidden');
                updateDestinationDropdown('alu');
                document.getElementById('weight_alu').focus();
            }

            function selectAfJualAction(action) {
                document.getElementById('afJualAction').value = action;
                document.getElementById('afJualActionSection').classList.add('hidden');
                document.getElementById('afJualFormSection').classList.remove('hidden');
                document.getElementById('weight_af_jual').focus();
            }

            function updateDestinationDropdown(type) {
                let dropdown;
                if (type === 'alu') {
                    dropdown = document.getElementById('destination');
                } else {
                    return; // AF JUAL only has IDM, no need to update
                }

                dropdown.innerHTML = '<option value="">-- Pilih Tujuan --</option>';
                for (const [key, label] of Object.entries(DESTINATIONS)) {
                    const option = document.createElement('option');
                    option.value = key;
                    option.textContent = label;
                    dropdown.appendChild(option);
                }
            }

            function updateGradeCompanyDropdown(parentId, selectedGradeId = null) {
                const gradeSelect = document.getElementById('grade_company_id');
                const stockInfo = document.getElementById('normalGradeStock');
                gradeSelect.innerHTML = '<option value="">-- Pilih Grade Company --</option>';
                stockInfo.textContent = '';

                if (!parentId) return;

                const parentName = parentGradeNames[parentId] || '';
                const isGlobalSort = parentName === 'ALU' || parentName === 'AA2 AF JUAL';

                if (isGlobalSort) {
                    // For ALU/AA2 AF JUAL, use the filtered global stock grades
                    const globalGrades = parentName === 'ALU' ? aluGrades : afJualGrades;
                    globalGrades.forEach(g => {
                        const option = document.createElement('option');
                        option.value = g.id;
                        option.textContent = g.name + ' (Stock: ' + number_format(g.global_stock, 0) + ' gr)';
                        if (selectedGradeId && g.id == selectedGradeId) {
                            option.selected = true;
                        }
                        gradeSelect.appendChild(option);
                    });
                    if (globalGrades.length > 0) {
                        stockInfo.textContent = 'Terdapat ' + globalGrades.length + ' grade dengan stock';
                    }
                } else {
                    // For non-ALU/AA2 AF JUAL, show all grades (stock is at parent level)
                    const allGrades = gradeCompanies.filter(g => g.parent_grade_company_id == parentId);

                    allGrades.forEach(g => {
                        const option = document.createElement('option');
                        option.value = g.id;
                        option.textContent = g.name;
                        if (selectedGradeId && g.id == selectedGradeId) {
                            option.selected = true;
                        }
                        gradeSelect.appendChild(option);
                    });

                    stockInfo.textContent = allGrades.length + ' grade tersedia';
                }
            }

            function updateAluStock() {
                const gradeId = parseInt(document.getElementById('grade_company_id_alu').value);
                const grade = aluGrades.find(g => g.id === gradeId);
                document.getElementById('aluGlobalStock').textContent = grade ? number_format(grade.global_stock, 0) : '0';
            }

            function updateAfJualStock() {
                const gradeId = parseInt(document.getElementById('grade_company_id_af_jual').value);
                const grade = afJualGrades.find(g => g.id === gradeId);
                document.getElementById('afJualGlobalStock').textContent = grade ? number_format(grade.global_stock, 0) : '0';
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
                updateAfJualStock();
            });

            document.getElementById('parent_grade_company_id').addEventListener('change', function () {
                updateGradeCompanyDropdown(this.value);
                toggleFormFields();
            });

            document.getElementById('grade_company_id_alu').addEventListener('change', function () {
                updateAluStock();
            });

            document.getElementById('grade_company_id_af_jual').addEventListener('change', function () {
                updateAfJualStock();
            });

            document.getElementById('sortForm').addEventListener('submit', function(e) {
                console.log('Form submitting...');
                const selectedId = document.getElementById('parent_grade_company_id').value;
                const parentName = parentGradeNames[selectedId] || '';
                console.log('Parent:', parentName);

                if (parentName === 'ALU') {
                    const action = document.getElementById('aluAction').value;
                    console.log('ALU action:', action);

                    if (!action) {
                        alert('Silakan pilih aksi terlebih dahulu');
                        e.preventDefault();
                        return;
                    }
                } else if (parentName === 'AA2 AF JUAL') {
                    const action = document.getElementById('afJualAction').value;
                    console.log('AF JUAL action:', action);

                    if (!action) {
                        alert('Silakan pilih aksi terlebih dahulu');
                        e.preventDefault();
                        return;
                    }
                }

                console.log('Form will submit');
            });
        </script>
    @endpush
@endsection