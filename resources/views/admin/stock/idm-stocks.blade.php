@extends('layouts.app')

@section('title', 'Tracking Stok — Manajemen IDM')

@section('content')
    @php
        $binStyles = [
            'IDM'       => ['bg' => 'bg-blue-50',    'text' => 'text-blue-700',    'badge' => 'bg-blue-100 text-blue-800 border-blue-200',    'role' => 'Output', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            'IDM A'     => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'badge' => 'bg-rose-100 text-rose-800 border-rose-200',    'role' => 'Input',  'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
            'IDM B'     => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'badge' => 'bg-amber-100 text-amber-800 border-amber-200',   'role' => 'Input',  'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
            'KAKIAN'    => ['bg' => 'bg-green-50',   'text' => 'text-green-700',   'badge' => 'bg-green-100 text-green-800 border-green-200',   'role' => 'Output', 'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
            'PERUTAN'   => ['bg' => 'bg-orange-50',  'text' => 'text-orange-700',  'badge' => 'bg-orange-100 text-orange-800 border-orange-200', 'role' => 'Output', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
            'ALU/AFKIR' => ['bg' => 'bg-purple-50',  'text' => 'text-purple-700',  'badge' => 'bg-purple-100 text-purple-800 border-purple-200', 'role' => 'Output', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
        ];
        $byName = $grades->keyBy('name');
    @endphp

    <div class="bg-gray-50 min-h-screen py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">

            {{-- Header --}}
            <div class="flex items-start justify-between mb-6">
                <div>
                    <nav class="text-xs text-gray-500 mb-2">
                        <a href="{{ route('tracking-stock.get.grade.company') }}" class="hover:text-gray-700">Tracking Stok</a>
                        <span class="mx-1.5 text-gray-300">/</span>
                        <span class="text-gray-700 font-medium">Manajemen IDM</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900">Manajemen IDM — Regrading</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Stok hasil proses regrading. Input: IDM A, IDM B &rarr; Output: IDM, KAKIAN, PERUTAN, ALU/AFKIR.
                    </p>
                </div>
                <a href="{{ route('tracking-stock.get.grade.company') }}"
                    class="flex-shrink-0 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    Kembali
                </a>
            </div>

            {{-- Summary Stats --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-6 overflow-hidden">
                <div class="grid grid-cols-3 divide-x divide-gray-200">
                    <div class="p-5">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total Diproses</p>
                        <p class="text-2xl font-bold text-red-600">
                            {{ number_format(abs($totalIdmOut), 0, ',', '.') }}
                            <span class="text-sm font-normal text-gray-500 ml-1">gram</span>
                        </p>
                        <p class="text-xs text-gray-400 mt-1">Berat grade asal (IDM A / IDM B) yang dikurangi saat regrading</p>
                    </div>
                    <div class="p-5">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total Dihasilkan</p>
                        <p class="text-2xl font-bold text-green-600">
                            {{ number_format($totalIdmIn, 0, ',', '.') }}
                            <span class="text-sm font-normal text-gray-500 ml-1">gram</span>
                        </p>
                        <p class="text-xs text-gray-400 mt-1">Berat output (IDM + KAKIAN + PERUTAN + ALU/AFKIR) yang ditambahkan</p>
                    </div>
                    <div class="p-5">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Susut Regrading</p>
                        <p class="text-2xl font-bold text-orange-600">
                            {{ number_format($totalSusut, 0, ',', '.') }}
                            <span class="text-sm font-normal text-gray-500 ml-1">gram</span>
                        </p>
                        <p class="text-xs text-gray-400 mt-1">Selisih penyusutan selama proses (Diproses &minus; Dihasilkan)</p>
                    </div>
                </div>
            </div>

            {{-- Output Grade Cards --}}
            <div class="mb-8">
                <div class="mb-3">
                    <h2 class="text-base font-bold text-gray-800">Stok Hasil Proses</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Stok dari kategori IDM saja (IDM_REGRADING_IN/OUT). Tidak termasuk transaksi grading, transfer, penjualan.
                    </p>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach(['IDM', 'KAKIAN', 'PERUTAN', 'ALU/AFKIR'] as $name)
                        @php $g = $byName->get($name); $style = $binStyles[$name]; @endphp
                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden {{ $g ? '' : 'opacity-50' }}">
                            <div class="px-4 pt-4 pb-3">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-9 h-9 rounded-lg {{ $style['bg'] }} flex items-center justify-center {{ $style['text'] }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $style['icon'] }}" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded border {{ $style['badge'] }}">
                                        {{ $style['role'] }}
                                    </span>
                                </div>
                                <p class="text-sm font-bold uppercase text-gray-800">{{ $name }}</p>
                                <p class="text-xs text-gray-400 mb-2">
                                    @if($g) Stok hasil regrading IDM @else Belum di-seed @endif
                                </p>
                                <p class="text-xl font-extrabold {{ ($g && $g->idm_stock > 0) ? 'text-green-700' : ($g ? 'text-gray-500' : 'text-gray-300') }}">
                                    {{ $g ? number_format($g->idm_stock, 0, ',', '.') : '—' }}
                                    @if($g) <span class="text-xs font-normal text-gray-400">gr</span> @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Note --}}
            <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-8 text-xs text-amber-800">
                Halaman ini hanya menampilkan efek Manajemen IDM. Untuk total stok per grade (termasuk grading, transfer, penjualan), buka menu <a href="{{ route('tracking-stock.get.grade.company') }}" class="font-semibold underline">Tracking Stok per Parent</a>.
            </div>

            {{-- Recent Records --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-gray-800">Riwayat Proses IDM</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            @if($totalMgmtCount > 5)
                                5 dari {{ $totalMgmtCount }} proses terbaru, dikelompokkan per grade asal.
                            @else
                                {{ $totalMgmtCount }} proses, dikelompokkan per grade asal.
                            @endif
                        </p>
                    </div>
                    @if($totalMgmtCount > 5)
                        <a href="{{ route('manajemen-idm.index') }}"
                            class="text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors">
                            Lihat semua ({{ $totalMgmtCount }})
                            <svg class="inline-block w-4 h-4 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @endif
                </div>

                @php
                    $groupedByInput = $recentRecords->groupBy(function ($r) {
                        return optional($r->gradeCompany)->name ?? 'Unknown';
                    })->sortKeys();
                @endphp

                @forelse($groupedByInput as $groupName => $records)
                    @php
                        if (str_starts_with($groupName, 'IDM A')) {
                            $inputStyle = $binStyles['IDM A'];
                        } elseif (str_starts_with($groupName, 'IDM B')) {
                            $inputStyle = $binStyles['IDM B'];
                        } else {
                            $inputStyle = ['bg' => 'bg-gray-50', 'text' => 'text-gray-700', 'badge' => 'bg-gray-100 text-gray-800 border-gray-200', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'];
                        }
                    @endphp

                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden mb-4">
                        <div class="px-6 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg {{ $inputStyle['bg'] }} flex items-center justify-center {{ $inputStyle['text'] }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $inputStyle['icon'] }}" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-sm font-bold text-gray-800 uppercase">{{ $groupName }}</span>
                                    <span class="ml-2 text-xs text-gray-400">{{ $records->count() }} proses</span>
                                </div>
                            </div>
                            <span class="text-xs text-gray-500">
                                Total awal: <span class="font-semibold text-gray-700">{{ number_format($records->sum('initial_weight'), 0, ',', '.') }} gr</span>
                            </span>
                        </div>
                        <div class="overflow-x-auto">
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
                                                        <span class="inline-block mr-1.5 mb-1 px-2 py-0.5 rounded text-xs border
                                                            @switch($d->grade_idm_name)
                                                                @case('IDM') bg-blue-50 text-blue-800 border-blue-100 @break
                                                                @case('KAKIAN') bg-green-50 text-green-800 border-green-100 @break
                                                                @case('PERUTAN') bg-orange-50 text-orange-800 border-orange-100 @break
                                                                @case('ALU') bg-purple-50 text-purple-800 border-purple-100 @break
                                                                @default bg-gray-50 text-gray-800 border-gray-200
                                                            @endswitch
                                                        ">
                                                            {{ $d->grade_idm_name }}: {{ number_format($d->weight, 0, ',', '.') }} gr
                                                        </span>
                                                    @endif
                                                @endforeach
                                            @else
                                                <span class="text-gray-400 text-xs italic">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-center">
                                            <a href="{{ route('manajemen-idm.show', $rec->id) }}"
                                                class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-100 rounded text-xs font-medium hover:bg-blue-100 transition-colors">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="bg-white border border-dashed border-gray-300 rounded-lg p-12 text-center">
                        <p class="text-sm text-gray-500">
                            Belum ada proses IDM.
                            <a href="{{ route('manajemen-idm.create') }}" class="text-blue-600 hover:underline">Buat sekarang</a>.
                        </p>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
@endsection
