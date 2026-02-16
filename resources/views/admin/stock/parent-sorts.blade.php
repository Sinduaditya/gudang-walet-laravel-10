@extends('layouts.app')

@section('title', 'Tracking Sortir - ' . $parentGrade->name)

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Bahan Sortir: {{ $parentGrade->name }}</h1>
                    <p class="text-gray-600 text-sm mt-1">Daftar bahan sortir untuk parent company ini.</p>
                </div>
                <a href="{{ route('tracking-stock.get.grade.company') }}"
                    class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
            </div>

            <!-- Global Stock Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    {{-- Total Stock --}}
                    <div class="flex items-center gap-4">
                        <div class="p-4 bg-orange-50 rounded-full">
                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm font-medium uppercase tracking-wider">Total Stok (Grades +
                                Sortir)</p>
                            <h3 class="text-3xl font-extrabold text-gray-900 mt-1">
                                {{ number_format(($globalStock ?? 0) + ($sortStock ?? 0), 0, ',', '.') }} <span
                                    class="text-lg text-gray-500 font-medium">gram</span>
                            </h3>
                        </div>
                    </div>

                    {{-- Breakdown --}}
                    <div class="flex gap-8 border-t md:border-t-0 md:border-l border-gray-100 pt-4 md:pt-0 md:pl-8">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Grades</p>
                            <p class="text-lg font-bold text-gray-700">{{ number_format($globalStock ?? 0, 0, ',', '.') }}
                                <span class="text-sm font-normal text-gray-500">gr</span></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Sortir</p>
                            <p class="text-lg font-bold text-orange-600">{{ number_format($sortStock ?? 0, 0, ',', '.') }}
                                <span class="text-sm font-normal text-orange-400">gr</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    No
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tanggal Sortir
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Grade Company (Source)
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Berat (Gram)
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Deskripsi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($sortMaterials as $index => $item)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $sortMaterials->firstItem() + $index }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $item->sort_date ? $item->sort_date->format('d M Y') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                        {{ $item->gradeCompany->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($item->weight, 0, ',', '.') }} gr
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ Str::limit($item->description, 50) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                        Tidak ada data bahan sortir.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($sortMaterials->hasPages())
                    <div class="bg-white px-6 py-4 border-t border-gray-200 flex justify-center">
                        {{ $sortMaterials->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection