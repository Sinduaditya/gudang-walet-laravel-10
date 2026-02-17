@extends('layouts.app')

@section('title', 'System Log')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header Section -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">System Log</h1>
                    <p class="mt-1 text-sm text-gray-600">Riwayat data yang telah dihapus</p>
                </div>
            </div>

            <!-- Tabs Section (Buttons) -->
            <div class="mb-6 border-b border-gray-200">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500">
                    <li class="mr-2">
                        <a href="{{ route('system-log.index', ['type' => 'suppliers']) }}"
                            class="inline-block p-4 border-b-2 rounded-t-lg {{ $type == 'suppliers' ? 'text-blue-600 border-blue-600 active' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                            Suppliers
                        </a>
                    </li>
                    <li class="mr-2">
                        <a href="{{ route('system-log.index', ['type' => 'grade_suppliers']) }}"
                            class="inline-block p-4 border-b-2 rounded-t-lg {{ $type == 'grade_suppliers' ? 'text-blue-600 border-blue-600 active' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                            Grade Suppliers
                        </a>
                    </li>
                    <li class="mr-2">
                        <a href="{{ route('system-log.index', ['type' => 'locations']) }}"
                            class="inline-block p-4 border-b-2 rounded-t-lg {{ $type == 'locations' ? 'text-blue-600 border-blue-600 active' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                            Locations
                        </a>
                    </li>
                    <li class="mr-2">
                        <a href="{{ route('system-log.index', ['type' => 'grade_companies']) }}"
                            class="inline-block p-4 border-b-2 rounded-t-lg {{ $type == 'grade_companies' ? 'text-blue-600 border-blue-600 active' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                            Grade Companies
                        </a>
                    </li>
                    <li class="mr-2">
                        <a href="{{ route('system-log.index', ['type' => 'parent_grade_companies']) }}"
                            class="inline-block p-4 border-b-2 rounded-t-lg {{ $type == 'parent_grade_companies' ? 'text-blue-600 border-blue-600 active' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                            Parent Grade Companies
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Filter & Search Section -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                <form action="{{ route('system-log.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
                    <input type="hidden" name="type" value="{{ $type }}">

                    <!-- Search Bar -->
                    <div class="flex-1 min-w-[200px]">
                        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Cari Data
                        </label>
                        <div class="flex gap-2">
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Cari berdasarkan nama..."
                                class="w-full h-10 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <button type="submit"
                                class="h-10 px-4 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium transition duration-200">
                                Cari
                            </button>
                            <a href="{{ route('system-log.index', ['type' => $type]) }}"
                                class="h-10 px-4 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium transition duration-200 text-center whitespace-nowrap shadow-sm flex items-center justify-center">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>

                @if(request('search'))
                    <div class="mt-3 pt-3 border-t border-gray-200 flex flex-wrap gap-2">
                        <span class="text-sm text-gray-600 self-center">Filter aktif:</span>
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Search: "{{ request('search') }}"
                        </span>
                    </div>
                @endif
            </div>

            <!-- Table Section -->
            <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Data Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Deleted By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Deleted At</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($data as $index => $item)
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $data->firstItem() + $index }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <div class="flex flex-col">
                                            <span
                                                class="font-medium text-gray-900">{{ $item->deletedBy->name ?? 'Unknown (' . $item->deleted_by . ')' }}</span>
                                            @if(isset($item->deletedBy->email))
                                                <span class="text-xs text-gray-400">{{ $item->deletedBy->email }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $item->deleted_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Deleted
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <p>Tidak ada data {{ $type }} yang terhapus.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($data->hasPages())
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                        {{ $data->appends(['type' => $type, 'search' => $search])->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection