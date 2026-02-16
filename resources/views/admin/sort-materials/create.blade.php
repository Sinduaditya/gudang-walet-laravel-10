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

            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                <form action="{{ route('sort-materials.store') }}" method="POST">
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

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="grade_company_id">
                            Grade Company (Optional)
                        </label>
                        <select name="grade_company_id" id="grade_company_id"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">-- Pilih Grade Company --</option>
                            <!-- Options populated by JS -->
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

                    <div class="flex gap-3 justify-end mt-6">
                        <a href="{{ route('sort-materials.index') }}"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-medium transition duration-200">
                            Batal
                        </a>
                        <button type="submit"
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
            // Grade Companies Data from Controller
            const gradeCompanies = @json($gradeCompanies);
            const oldParentId = "{{ old('parent_grade_company_id') }}";
            const oldGradeId = "{{ old('grade_company_id') }}";

            // Initialize dropdowns
            document.addEventListener('DOMContentLoaded', function () {
                if (oldParentId) {
                    updateGradeCompanyDropdown(oldParentId, oldGradeId);
                }
            });

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

            // Event listener for Parent Grade change
            document.getElementById('parent_grade_company_id').addEventListener('change', function () {
                updateGradeCompanyDropdown(this.value);
            });
        </script>
    @endpush
@endsection