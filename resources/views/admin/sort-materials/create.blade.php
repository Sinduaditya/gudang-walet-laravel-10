@extends('layouts.app')

@section('title', 'Tambah Sortir Bahan')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- Header --}}
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Tambah Sortir Bahan</h1>
                    <p class="mt-1 text-sm text-gray-600">Catat input barang masuk ke dalam Stok Hasil Sortir Bahan</p>
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

            {{-- Main Form Card --}}
            <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                <form action="{{ route('sort-materials.store') }}" method="POST" id="sortForm" class="p-6 space-y-6">
                    @csrf

                    {{-- Tanggal --}}
                    <div>
                        <label class="block font-semibold text-gray-700 mb-2" for="sort_date">
                            Tanggal Sortir <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="sort_date" id="sort_date" 
                            value="{{ old('sort_date', date('Y-m-d')) }}" required
                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('sort_date')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Parent Grade --}}
                    <div>
                        <label class="block font-semibold text-gray-700 mb-2" for="parent_grade_company_id">
                            Parent Grade <span class="text-red-500">*</span>
                        </label>
                        <select name="parent_grade_company_id" id="parent_grade_company_id" required
                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- Pilih Parent Grade --</option>
                            @foreach ($parentGradeCompanies as $pg)
                                <option value="{{ $pg->id }}" data-name="{{ $pg->name }}" {{ old('parent_grade_company_id') == $pg->id ? 'selected' : '' }}>
                                    {{ $pg->name }} (Stok sortir saat ini: {{ number_format($pg->stock, 0, ',', '.') }} gr)
                                </option>
                            @endforeach
                        </select>
                        @error('parent_grade_company_id')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Grade Company (Detail) --}}
                    <div>
                        <label class="block font-semibold text-gray-700 mb-2" for="grade_company_id">
                            Detail Grade Company <span class="text-gray-400 font-normal text-xs">(Opsional)</span>
                        </label>
                        <select name="grade_company_id" id="grade_company_id"
                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- Pilih Detail Grade (Pilih Parent Dahulu) --</option>
                            @foreach($gradeCompanies as $gc)
                                <option value="{{ $gc->id }}" data-parent-id="{{ $gc->parent_grade_company_id }}" {{ old('grade_company_id') == $gc->id ? 'selected' : '' }}>
                                    {{ $gc->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('grade_company_id')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Destination Select (Dynamically visible for ALU & AA2 AF JUAL) --}}
                    <div id="destination_container" class="hidden">
                        <label class="block font-semibold text-gray-700 mb-2" for="destination">
                            Tujuan Sortir (Destination) <span class="text-red-500">*</span>
                        </label>
                        <select name="destination" id="destination"
                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- Pilih Tujuan --</option>
                        </select>
                        @error('destination')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Berat --}}
                    <div>
                        <label class="block font-semibold text-gray-700 mb-2" for="weight">
                            Berat Masuk (gram) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" name="weight" id="weight" 
                            value="{{ old('weight') }}" required
                            placeholder="Masukkan berat dalam gram"
                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('weight')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Deskripsi --}}
                    <div>
                        <label class="block font-semibold text-gray-700 mb-2" for="description">
                            Deskripsi / Catatan <span class="text-gray-400 font-normal text-xs">(Opsional)</span>
                        </label>
                        <textarea name="description" id="description" rows="3"
                            placeholder="Tambahkan deskripsi atau catatan tambahan..."
                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Form Actions --}}
                    <div class="flex items-center gap-3 pt-6 border-t border-gray-200">
                        <a href="{{ route('sort-materials.index') }}"
                            class="flex-1 inline-flex items-center justify-center px-4 py-3 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-all">
                            Batal
                        </a>
                        <button type="submit"
                            class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 transition-all duration-200 shadow-lg">
                            Simpan Data Sortir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const parentSelect = document.getElementById('parent_grade_company_id');
                const gradeSelect = document.getElementById('grade_company_id');
                const destContainer = document.getElementById('destination_container');
                const destSelect = document.getElementById('destination');
                
                // Backup all grade options
                const gradeOptions = Array.from(gradeSelect.options);

                function filterGrades() {
                    const selectedParentOpt = parentSelect.options[parentSelect.selectedIndex];
                    const selectedParentId = parentSelect.value;
                    const parentName = selectedParentOpt ? selectedParentOpt.dataset.name : '';
                    
                    // Clear select
                    gradeSelect.innerHTML = '';
                    
                    // Add default option
                    const defaultOpt = gradeOptions[0];
                    gradeSelect.appendChild(defaultOpt);

                    if (!selectedParentId) {
                        defaultOpt.text = '-- Pilih Detail Grade (Pilih Parent Dahulu) --';
                        destContainer.classList.add('hidden');
                        destSelect.removeAttribute('required');
                        return;
                    }

                    defaultOpt.text = '-- Semua Detail Grade --';

                    // Filter and append valid options
                    gradeOptions.forEach(opt => {
                        if (!opt.value) return;
                        if (opt.dataset.parentId == selectedParentId) {
                            gradeSelect.appendChild(opt);
                        }
                    });

                    // Handle destination dropdown visibility
                    if (parentName === 'ALU') {
                        destContainer.classList.remove('hidden');
                        destSelect.setAttribute('required', 'required');
                        
                        destSelect.innerHTML = `
                            <option value="">-- Pilih Tujuan --</option>
                            <option value="mangkok" ${ "{{ old('destination') }}" === 'mangkok' ? 'selected' : '' }>Mangkok</option>
                            <option value="idm" ${ "{{ old('destination') }}" === 'idm' ? 'selected' : '' }>IDM</option>
                            <option value="aa" ${ "{{ old('destination') }}" === 'aa' ? 'selected' : '' }>AA</option>
                            <option value="af" ${ "{{ old('destination') }}" === 'af' ? 'selected' : '' }>Lempeng (AF)</option>
                        `;
                    } else if (parentName === 'AA2 AF JUAL') {
                        destContainer.classList.remove('hidden');
                        destSelect.setAttribute('required', 'required');
                        
                        destSelect.innerHTML = `
                            <option value="">-- Pilih Tujuan --</option>
                            <option value="idm" ${ "{{ old('destination') }}" === 'idm' ? 'selected' : '' }>IDM</option>
                        `;
                    } else {
                        destContainer.classList.add('hidden');
                        destSelect.removeAttribute('required');
                        destSelect.innerHTML = '<option value="">-- Pilih Tujuan --</option>';
                    }
                }

                // Filter on change
                parentSelect.addEventListener('change', filterGrades);

                // Initial filter if already selected
                if (parentSelect.value) {
                    filterGrades();
                    // Restore previously selected grade if exists
                    const oldGradeId = "{{ old('grade_company_id') }}";
                    if (oldGradeId) {
                        gradeSelect.value = oldGradeId;
                    }
                    const oldDest = "{{ old('destination') }}";
                    if (oldDest) {
                        destSelect.value = oldDest;
                    }
                }
            });
        </script>
    @endpush
@endsection