@extends('layouts.app')

@section('title', 'Buat Assignment Baru')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">Buat Assignment Baru</h1>
                <a href="{{ route('bulk-assignments.index') }}"
                    class="text-sm text-gray-600 hover:text-gray-800 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </a>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                <form action="{{ route('bulk-assignments.store') }}" method="POST">
                    @csrf

                    <!-- Parent Selection -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="parent_grade_company_id">
                            Pilih Parent Grade Company
                        </label>
                        <select name="parent_grade_company_id" id="parent_grade_company_id" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">-- Pilih Parent Grade --</option>
                            @foreach ($parentGradeCompanies as $pg)
                                <option value="{{ $pg->id }}">{{ $pg->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <hr class="my-6 border-gray-200">

                    <!-- Grade Selection -->
                    <div class="mb-4">
                        <h2 class="text-lg font-medium text-gray-800 mb-4">Pilih Grade Company (Unassigned)</h2>

                        <!-- Search (Client-side) -->
                        <div class="mb-4 flex gap-2">
                            <input type="text" id="searchInput" placeholder="Cari Grade..."
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div class="overflow-x-auto mb-6 max-h-[60vh] overflow-y-auto border border-gray-100 rounded-md">
                        <table class="min-w-full divide-y divide-gray-200" id="gradeTable">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-6 py-3 text-left bg-gray-50">
                                        <input type="checkbox" id="selectAll"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">
                                        Nama Grade</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">
                                        Deskripsi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($gradeCompanies as $grade)
                                    <tr class="hover:bg-gray-50 grade-row">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" name="grade_company_ids[]" value="{{ $grade->id }}"
                                                class="grade-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 grade-name">
                                            {{ $grade->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ Str::limit($grade->description, 50) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                            Tidak ada Grade Company yang belum di-assign.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium transition duration-200">
                            Simpan Assignment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Select All Logic
            document.getElementById('selectAll').addEventListener('change', function () {
                // Only select visible checkboxes
                const rows = document.querySelectorAll('.grade-row:not(.hidden)');
                rows.forEach(row => {
                    const cb = row.querySelector('.grade-checkbox');
                    if (cb) cb.checked = this.checked;
                });
            });

            // Client-side Search Logic
            document.getElementById('searchInput').addEventListener('keyup', function () {
                const searchValue = this.value.toLowerCase();
                const rows = document.querySelectorAll('.grade-row');

                rows.forEach(row => {
                    const name = row.querySelector('.grade-name').textContent.toLowerCase();
                    if (name.includes(searchValue)) {
                        row.classList.remove('hidden');
                    } else {
                        row.classList.add('hidden');
                    }
                });

                // Uncheck header select all if search changes to avoid confusion? 
                // Alternatively, keep it. Let's keep it simple.
            });
        </script>
    @endpush
@endsection