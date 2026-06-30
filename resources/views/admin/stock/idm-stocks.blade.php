@extends('layouts.app')

@section('title', 'Tracking Stok - Manajemen IDM')

@section('content')
    @php
        // Definisi warna & icon per bin. Setiap bin punya style beda supaya visual
        // jelas membedakan KAKIAN/PERUTAN/ALU/AFKIR (output) vs IDM A/B (input).
        $binStyles = [
            'IDM'      => ['bg' => 'bg-blue-50',    'text' => 'text-blue-700',    'badge' => 'bg-blue-100 text-blue-800 border-blue-200',   'role' => 'Output', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            'IDM A'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'badge' => 'bg-rose-100 text-rose-800 border-rose-200',   'role' => 'Input',  'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
            'IDM B'    => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'badge' => 'bg-amber-100 text-amber-800 border-amber-200', 'role' => 'Input',  'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
            'KAKIAN'   => ['bg' => 'bg-green-50',   'text' => 'text-green-700',   'badge' => 'bg-green-100 text-green-800 border-green-200', 'role' => 'Output', 'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
            'PERUTAN'  => ['bg' => 'bg-orange-50',  'text' => 'text-orange-700',  'badge' => 'bg-orange-100 text-orange-800 border-orange-200', 'role' => 'Output', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
            'ALU/AFKIR'=> ['bg' => 'bg-purple-50',  'text' => 'text-purple-700', 'badge' => 'bg-purple-100 text-purple-800 border-purple-200', 'role' => 'Output', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
        ];
        // Urutan tampilan: input dulu (IDM A, IDM B), lalu output (IDM, KAKIAN, PERUTAN, ALU/AFKIR)
        $orderedNames = ['IDM A', 'IDM B', 'IDM', 'KAKIAN', 'PERUTAN', 'ALU/AFKIR'];
        $byName = $grades->keyBy('name');
    @endphp

    <div class="py-8 px-4" style="background-color: #f8f9fa;">
        <div class="max-w-7xl mx-auto">

            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Tracking Stok — Manajemen IDM</h1>
                    <p class="text-gray-600">Stok hasil proses Manajemen IDM. Setiap bin punya warna berbeda untuk memudahkan identifikasi.</p>
                </div>
                <a href="{{ route('tracking-stock.get.grade.company') }}"
                    class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Index
                </a>
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wider">Total Diproses</p>
                    <h3 class="text-2xl font-extrabold text-red-600 mt-1">
                        {{ number_format(abs($totalIdmOut), 0, ',', '.') }} <span class="text-sm text-gray-500 font-medium">gram</span>
                    </h3>
                    <p class="text-xs text-gray-400 mt-1">Total berat yang diproses di Manajemen IDM (dari grade asal seperti IDM A / IDM B).</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wider">Total Dihasilkan</p>
                    <h3 class="text-2xl font-extrabold text-green-600 mt-1">
                        {{ number_format($totalIdmIn, 0, ',', '.') }} <span class="text-sm text-gray-500 font-medium">gram</span>
                    </h3>
                    <p class="text-xs text-gray-400 mt-1">Total berat hasil yang tersedia untuk dijual atau dikirim keluar.</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wider">Total Susut</p>
                    <h3 class="text-2xl font-extrabold text-orange-600 mt-1">
                        {{ number_format($totalSusut, 0, ',', '.') }} <span class="text-sm text-gray-500 font-medium">gram</span>
                    </h3>
                    <p class="text-xs text-gray-400 mt-1">Selisih yang hilang saat proses (penyusutan / shrinkage).</p>
                </div>
            </div>

            {{-- Section: Output Grades --}}
            <div class="mb-6">
                <div class="flex items-center mb-3">
                    <h2 class="text-lg font-bold text-gray-800">Stok Hasil Proses</h2>
                    <span class="ml-3 text-xs text-gray-500">Stok yang tersedia untuk dijual atau dikirim keluar</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach(['IDM', 'KAKIAN', 'PERUTAN', 'ALU/AFKIR'] as $name)
                        @php $g = $byName->get($name); $style = $binStyles[$name]; @endphp
                        <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 overflow-hidden group {{ $g ? '' : 'opacity-50' }}">
                            <div class="p-5">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="w-12 h-12 rounded-lg {{ $style['bg'] }} flex items-center justify-center {{ $style['text'] }}">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $style['icon'] }}" />
                                            </svg>
                                    </div>
                                    <span class="{{ $style['badge'] }} text-xs font-semibold px-2.5 py-0.5 rounded border">
                                        {{ $style['role'] }}
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold uppercase text-gray-800 mb-1 group-hover:{{ $style['text'] }} transition-colors">
                                    {{ $g ? $g->name : $name }}
                                </h3>
                                <p class="text-xs text-gray-500 mb-3">
                                    @if($g) Stok hasil Manajemen IDM @else <span class="text-red-600">Belum di-seed</span> @endif
                                </p>
                                <div class="border-t border-gray-100 pt-3">
                                    <p class="text-xs text-gray-500 mb-1">Stok Tersedia</p>
                                    <p class="text-xl font-extrabold {{ ($g && $g->idm_stock >= 0) ? 'text-green-700' : 'text-gray-400' }}">
                                        {{ $g ? number_format($g->idm_stock, 0, ',', '.') : '—' }} @if($g)<span class="text-xs text-gray-500 font-medium">gr</span>@endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-6 text-xs text-gray-500">
                <p><strong>Catatan:</strong> Halaman ini hanya menampilkan efek Manajemen IDM. Untuk melihat total stok per grade (termasuk dari Grading, transfer, penjualan), buka menu Tracking Stok per parent.</p>
            </div>

            {{-- Recent Manajemen IDM Records, dikelompokkan per parent (input grade) --}}
            <div class="mt-8">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Riwayat Proses IDM</h2>
                        <p class="text-xs text-gray-500 mt-1">
                            @if($totalMgmtCount > 5)
                                Menampilkan <span class="font-semibold text-gray-700">5</span> dari <span class="font-semibold text-gray-700">{{ $totalMgmtCount }}</span> proses terbaru
                            @else
                                {{ $totalMgmtCount }} proses total
                            @endif
                            — dikelompokkan per grade asal.
                        </p>
                    </div>
                    @if($totalMgmtCount > 5)
                        <a href="{{ route('manajemen-idm.index') }}"
                            class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors">
                            Lihat semua ({{ $totalMgmtCount }})
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @endif
                </div>

                @php
                    // Group records by input grade name. Setiap record ManajemenIDM bisa saja
                    // punya grade input apa saja yang ada di dropdown Grading (mis. "IDM A",
                    // "IDM A W2", "IDM B MK", dll) — tidak hardcode ke 2 nilai saja.
                    $groupedByInput = $recentRecords->groupBy(function ($r) {
                        return optional($r->gradeCompany)->name ?? 'Unknown';
                    })->sortKeys();
                @endphp

                @forelse($groupedByInput as $groupName => $records)
                        @php
                            // Pakai style rose untuk semua input grade IDM-A-* (prefix),
                            // amber untuk IDM-B-*, gray untuk yang lain (seharusnya tidak ada, defensive only).
                            if (str_starts_with($groupName, 'IDM A')) {
                                $inputStyle = ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'badge' => 'bg-rose-100 text-rose-800 border-rose-200', 'icon' => $binStyles['IDM A']['icon']];
                            } elseif (str_starts_with($groupName, 'IDM B')) {
                                $inputStyle = ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'badge' => 'bg-amber-100 text-amber-800 border-amber-200', 'icon' => $binStyles['IDM B']['icon']];
                            } else {
                                $inputStyle = ['bg' => 'bg-gray-50', 'text' => 'text-gray-700', 'badge' => 'bg-gray-100 text-gray-800 border-gray-200', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'];
                            }
                        @endphp
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-4">
                            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg {{ $inputStyle['bg'] }} flex items-center justify-center {{ $inputStyle['text'] }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $inputStyle['icon'] }}" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-gray-800">Grade Asal: <span class="uppercase">{{ $groupName }}</span></h3>
                                        <p class="text-xs text-gray-500">{{ $records->count() }} proses</p>
                                    </div>
                                </div>
                                <span class="text-xs text-gray-500">Total Berat Awal: {{ number_format($records->sum('initial_weight'), 0, ',', '.') }} gr</span>
                            </div>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                                        <th class="px-6 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Berat Awal</th>
                                        <th class="px-6 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Susut</th>
                                        <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Output</th>
                                        <th class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($records as $rec)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">
                                                {{ $rec->grading_date ? \Carbon\Carbon::parse($rec->grading_date)->format('d/m/Y H:i') : '—' }}
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">
                                                {{ optional($rec->supplier)->name ?? '—' }}
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                                {{ number_format($rec->initial_weight, 0, ',', '.') }} gr
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap text-sm text-right">
                                                <span class="font-bold {{ $rec->shrinkage > 0 ? 'text-orange-600' : 'text-green-600' }}">
                                                    {{ number_format($rec->shrinkage, 0, ',', '.') }} gr
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 text-sm text-gray-700">
                                                @if($rec->details && $rec->details->count() > 0)
                                                    @foreach($rec->details as $d)
                                                        @if($d->weight > 0)
                                                            <span class="inline-block mr-2 mb-1 px-2 py-0.5 rounded text-xs
                                                                @switch($d->grade_idm_name)
                                                                    @case('IDM') bg-blue-100 text-blue-800 @break
                                                                    @case('KAKIAN') bg-green-100 text-green-800 @break
                                                                    @case('PERUTAN') bg-orange-100 text-orange-800 @break
                                                                    @case('ALU') bg-purple-100 text-purple-800 @break
                                                                    @default bg-gray-100 text-gray-800
                                                                @endswitch
                                                            ">
                                                                {{ $d->grade_idm_name }}: {{ number_format($d->weight, 0, ',', '.') }}gr
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <span class="text-gray-400 text-xs italic">—</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap text-center text-sm">
                                                <a href="{{ route('manajemen-idm.show', $rec->id) }}"
                                                    class="inline-flex items-center px-2.5 py-1 bg-blue-50 text-blue-700 rounded-md hover:bg-blue-100 transition-colors text-xs font-medium">
                                                    Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                @empty
                    <div class="bg-white rounded-xl border border-dashed border-gray-300 p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-2 text-gray-500 text-sm">Belum ada proses IDM. <a href="{{ route('manajemen-idm.create') }}" class="text-blue-600 hover:underline">Buat sekarang</a>.</p>
                    </div>
                @endforelse
            </div>
            </div>

        </div>
    </div>
@endsection