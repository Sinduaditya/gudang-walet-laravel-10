@extends('layouts.app')

@section('title', 'Edit Assignment: ' . $parentGradeCompany->name)

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">Edit Assignment: {{ $parentGradeCompany->name }}</h1>
                <a href="{{ route('bulk-assignments.index') }}"
                    class="text-sm text-gray-600 hover:text-gray-800 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column: Assigned Grades (Remove) -->
                <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                    <h2 class="text-lg font-medium text-gray-800 mb-4 text-red-600">Grade yang Sudah Di-assign (Pilih untuk
                        Unassign)</h2>
                    <form action="{{ route('bulk-assignments.update', $parentGradeCompany->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="overflow-y-auto max-h-96 mb-4 border border-gray-100 rounded">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left w-10">
                                            <input type="checkbox" id="selectAllUnassign"
                                                class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                                        </th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nama Grade</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse ($assignedGrades as $grade)
                                        <tr class="hover:bg-red-50">
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <input type="checkbox" name="unassign_ids[]" value="{{ $grade->id }}"
                                                    class="unassign-checkbox rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">{{ $grade->name }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-4 py-2 text-center text-sm text-gray-500">
                                                Tidak ada grade yang di-assign.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 font-medium transition duration-200 text-sm">
                                Unassign Terpilih
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Column: Available Grades (Add) -->
                <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                    <h2 class="text-lg font-medium text-gray-800 mb-4 text-green-600">Grade Tersedia (Pilih untuk Assign)
                    </h2>

                    <!-- Client-side Search Input -->
                    <div class="mb-4 flex gap-2">
                        <input type="text" id="searchInput" placeholder="Cari Grade..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm font-medium transition duration-200">
                    </div>

                    <form action="{{ route('bulk-assignments.update', $parentGradeCompany->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="overflow-y-auto max-h-96 mb-4 border border-gray-100 rounded">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left w-10">
                                            <input type="checkbox" id="selectAllAssign"
                                                class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-300 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                                        </th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nama Grade</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse ($availableGrades as $grade)
                                        <tr class="hover:bg-green-50 assign-row">
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <input type="checkbox" name="assign_ids[]" value="{{ $grade->id }}"
                                                    class="assign-checkbox rounded border-gray-300 text-green-600 shadow-sm focus:border-green-300 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 assign-name">
                                                {{ $grade->name }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-4 py-2 text-center text-sm text-gray-500">
                                                Tidak ada grade tersedia.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mb-4 text-xs text-gray-500">
                            Total {{ $availableGrades->count() }} data.
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-medium transition duration-200 text-sm">
                                Assign Terpilih
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('selectAllUnassign').addEventListener('change', function () {
                document.querySelectorAll('.unassign-checkbox').forEach(cb => cb.checked = this.checked);
            });

            document.getElementById('selectAllAssign').addEventListener('change', function () {
                const rows = document.querySelectorAll('.assign-row:not(.hidden)');
                rows.forEach(row => {
                    const cb = row.querySelector('.assign-checkbox');
                    if (cb) cb.checked = this.checked;
                });
            });

            // Client-side search for Available Grades
            document.getElementById('searchInput').addEventListener('keyup', function () {
                const searchValue = this.value.toLowerCase();
                const rows = document.querySelectorAll('.assign-row');

                rows.forEach(row => {
                    const name = row.querySelector('.assign-name').textContent.toLowerCase();
                    if (name.includes(searchValue)) {
                        row.classList.remove('hidden');
                    } else {
                        row.classList.add('hidden');
                    }
                });
            });
        </script>
    @endpush
@endsection