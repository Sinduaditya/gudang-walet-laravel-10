@extends('layouts.app')

@section('title', 'Bulk Assignment Parent Grade')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

            <!-- Header & Navigation -->
            <div class="mb-8">
                <a href="{{ route('grade-company.index') }}"
                    class="group inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors mb-4">
                    <svg class="w-4 h-4 mr-2 transition-transform group-hover:-translate-x-1" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Grade Company
                </a>
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900">Bulk Assignment Parent Grade</h1>
                </div>
            </div>


            <!-- Search Section -->
            <div class="mb-6">
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <form method="GET" action="{{ route('bulk-assignments.index') }}">
                        <div class="flex flex-col lg:flex-row gap-4">
                            <!-- Search Input -->
                            <div class="flex-1">
                                <label class="flex items-center text-sm text-gray-600 mb-2">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Cari Parent Grade Company
                                </label>
                                <input type="text" name="search" value="{{ request('search') }}"
                                    placeholder="Cari Parent Grade..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            </div>

                            <!-- Buttons Group -->
                            <div class="flex items-end gap-2">
                                <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium transition duration-200 whitespace-nowrap">
                                    Cari
                                </button>
                                <a href="{{ route('bulk-assignments.index') }}"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium transition duration-200 whitespace-nowrap">
                                    Reset
                                </a>
                                <a href="{{ route('bulk-assignments.create') }}"
                                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium transition duration-200 whitespace-nowrap">
                                    Tambah Assignment Baru
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Parent Grade</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Jumlah Grade Assigned</th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($parentGradeCompanies as $index => $item)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $parentGradeCompanies->firstItem() + $index }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $item->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <span
                                            class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ $item->grade_companies_count }} Grades
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                        <div class="flex items-center justify-center space-x-2">
                                            <a href="{{ route('bulk-assignments.show', $item->id) }}"
                                                class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200 transition duration-200">
                                                Detail
                                            </a>
                                            <a href="{{ route('bulk-assignments.edit', $item->id) }}"
                                                class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition duration-200">
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">
                                        Belum ada data Parent Grade Company.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($parentGradeCompanies->hasPages())
                    <div class="bg-white px-6 py-4 border-t border-gray-200">
                        {{ $parentGradeCompanies->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection