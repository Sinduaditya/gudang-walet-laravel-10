@extends('layouts.app')

@section('title', 'Sortir — ' . $parentGrade->name)

@section('content')
<div class="bg-gray-50 min-h-screen py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">

        {{-- Header --}}
        <div class="flex items-start justify-between mb-6">
            <div>
                <nav class="text-xs text-gray-500 mb-2">
                    <a href="{{ route('tracking-stock.get.grade.company') }}" class="hover:text-gray-700">Tracking Stok</a>
                    <span class="mx-1.5 text-gray-300">/</span>
                    <span class="text-gray-700 font-medium">{{ $parentGrade->name }} — Sortir</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-900">Bahan Sortir: {{ $parentGrade->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">Daftar bahan yang masuk dan keluar proses sortir.</p>
            </div>
            <a href="{{ route('tracking-stock.get.grade.company') }}"
                class="flex-shrink-0 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Kembali
            </a>
        </div>

        {{-- Summary Bar --}}
        <div class="bg-white border border-gray-200 rounded-lg p-5 mb-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center gap-6">

                {{-- Total --}}
                <div class="flex-1">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                        Total Stok (Grades + Sortir)
                    </p>
                    <p class="text-3xl font-bold text-gray-900">
                        {{ number_format(($globalStock ?? 0) + ($sortStock ?? 0), 0, ',', '.') }}
                        <span class="text-base font-normal text-gray-500 ml-1">gram</span>
                    </p>
                </div>

                {{-- Breakdown --}}
                <div class="flex gap-6 sm:border-l sm:border-gray-200 sm:pl-8">
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Dari Grades</p>
                        <p class="text-lg font-bold text-gray-800">
                            {{ number_format($globalStock ?? 0, 0, ',', '.') }}
                            <span class="text-sm font-normal text-gray-500">gr</span>
                        </p>
                    </div>
                    <div class="border-l border-gray-200 pl-6">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Total Sortir</p>
                        <p class="text-lg font-bold text-orange-600">
                            {{ number_format($sortStock ?? 0, 0, ',', '.') }}
                            <span class="text-sm font-normal text-orange-400">gr</span>
                        </p>
                        <div class="flex gap-2 mt-1">
                            <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 text-xs font-semibold border border-blue-100">
                                {{ number_format($sortParentStock ?? 0, 0, ',', '.') }} gr Mentah
                            </span>
                            <span class="px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 text-xs font-semibold border border-purple-100">
                                {{ number_format($sortChildStock ?? 0, 0, ',', '.') }} gr Child
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Riwayat Proses Sortir</h3>
                <span class="text-xs text-gray-500">{{ $sortMaterials->total() }} total</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sumber Grade</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Berat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tujuan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($sortMaterials as $index => $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $sortMaterials->firstItem() + $index }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $item->sort_date ? $item->sort_date->format('d M Y') : '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($item->gradeCompany)
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500 mr-1.5"></span>
                                        Child: {{ $item->gradeCompany->name }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-1.5"></span>
                                        Mentah (Parent)
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 text-right">
                                {{ number_format($item->weight, 0, ',', '.') }}
                                <span class="font-normal text-xs text-gray-500 ml-1">gr</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ Str::limit($item->description, 50) ?: '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($item->destination)
                                    <span class="px-2 py-1 bg-blue-50 text-blue-700 border border-blue-100 rounded text-xs font-medium">
                                        {{ ucfirst($item->destination) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                Tidak ada data bahan sortir.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($sortMaterials->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $sortMaterials->appends(request()->query())->links() }}
            </div>
            @endif
        </div>

    </div>
</div>
@endsection
