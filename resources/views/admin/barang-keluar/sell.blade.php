@extends('layouts.app')

@section('title', 'Penjualan Langsung')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header with Back Button and History Tab Toggle --}}
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Penjualan Langsung</h1>
                    <p class="mt-1 text-sm text-gray-600">Catat penjualan barang dari Stok Grading atau Stok Sortir Bahan ke customer</p>
                </div>

                <div class="flex items-center gap-3">
                    {{-- Tab Toggle Button --}}
                    <button type="button" onclick="toggleHistoryTab()" id="historyToggleBtn"
                        class="inline-flex items-center px-4 py-2 border border-blue-300 text-sm font-medium rounded-lg text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span id="historyToggleText">Daftar Penjualan Langsung</span>
                    </button>

                    {{-- Back Button --}}
                    <a href="{{ route('barang.keluar.index') }}"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Kembali
                    </a>
                </div>
            </div>

            {{-- Source Selection Tabs (Form Section) --}}
            <div id="sourceTabSelector" class="mb-6 flex gap-2 border-b border-gray-200">
                <button type="button" onclick="switchFormTab('grading')" id="formTabBtnGrading"
                    class="py-2.5 px-4 font-semibold text-sm border-b-2 border-blue-600 text-blue-600 transition-all focus:outline-none">
                    Stok Hasil Grading
                </button>
                <button type="button" onclick="switchFormTab('sortir')" id="formTabBtnSortir"
                    class="py-2.5 px-4 font-semibold text-sm text-gray-500 hover:text-gray-700 transition-all border-b-2 border-transparent focus:outline-none">
                    Stok Hasil Sortir Bahan
                </button>
            </div>

            {{-- Alert Messages --}}
            @if (session('success'))
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-md shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('error'))
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

            {{-- Tab Content Container --}}
            <div class="space-y-8">

                {{-- Form Tab (Default Active) --}}
                <div id="formTab" class="tab-content">
                    
                    {{-- 1. FORM PENJUALAN GRADING --}}
                    <div id="formGrading" class="bg-white rounded-xl shadow-md border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Form Penjualan (Stok Grading)</h2>
                            <p class="text-sm text-gray-500 mt-1">Lengkapi data penjualan barang dari batch hasil grading</p>
                        </div>

                        <form action="{{ route('barang.keluar.sell.store') }}" method="POST" class="p-6">
                            @csrf

                            <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Supplier Filter for Grade --}}
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2">
                                            Filter Supplier <span class="text-gray-400 font-normal text-xs">(Opsional)</span>
                                        </label>
                                        <select id="filter_supplier_id" 
                                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                            <option value="">-- Semua Supplier --</option>
                                            @foreach($suppliers as $s)
                                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Grade Select --}}
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2">
                                            Grade Perusahaan <span class="text-red-500">*</span>
                                        </label>

                                        <select name="grade_company_id" id="grade_company_id" required
                                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">-- Pilih Grade --</option>
                                            @foreach($gradesWithStock as $g)
                                                <option value="{{ $g['id'] }}" 
                                                    data-stock="{{ $g['batch_stock_grams'] }}"
                                                    data-supplier-id="{{ $g['supplier_id'] }}"
                                                    {{ old('grade_company_id') == $g['id'] ? 'selected' : '' }}>
                                                    {{ $g['name'] }} - {{ $g['supplier_name'] }} - {{ $g['grading_date'] }} (Batch: {{ number_format($g['batch_stock_grams'], 0, ',', '.') }} gr)
                                                </option>
                                            @endforeach
                                        </select>

                                        {{-- Stock hint --}}
                                        <p id="grade-stock-hint" class="mt-2 text-sm text-gray-500">
                                            Stok tersedia: <span id="grade-stock-value" class="font-semibold">-</span>
                                        </p>

                                        @error('grade_company_id')
                                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Hidden Location (default Gudang Utama) --}}
                                <input type="hidden" name="location_id" value="{{ $defaultLocation->id ?? 1 }}">

                                {{-- Weight & Date --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2">
                                            Berat Penjualan (gram) <span class="text-red-500">*</span>
                                        </label>
                                        <div class="flex gap-2">
                                            <input type="number" name="weight_grams" id="weight_grams"
                                                step="0.01" min="0.01" required
                                                value="{{ old('weight_grams') }}"
                                                class="flex-1 border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                placeholder="Masukkan berat dalam gram">
                                            <button type="button" onclick="checkStock()" id="btnCheckStock"
                                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap">
                                                Cek Stok
                                            </button>
                                        </div>
                                        <p id="stock-check-result" class="mt-2 text-sm hidden"></p>
                                        @error('weight_grams')
                                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2">Tanggal Penjualan</label>
                                        <input type="date" name="transaction_date"
                                            value="{{ old('transaction_date', date('Y-m-d')) }}"
                                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        @error('transaction_date')
                                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Notes --}}
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Catatan
                                        <span class="text-gray-400 font-normal text-xs">(Opsional)</span>
                                    </label>
                                    <textarea name="notes" rows="3"
                                        placeholder="Tambahkan catatan atau keterangan penjualan..."
                                        class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Form Actions --}}
                            <div class="flex items-center gap-3 pt-6 border-t border-gray-200 mt-6">
                                <button type="reset"
                                    class="flex-1 inline-flex items-center justify-center px-4 py-3 border-2 border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all">
                                    Reset
                                </button>
                                <button type="submit"
                                    class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-lg">
                                    Simpan Penjualan Grading
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- 2. FORM PENJUALAN SORTIR --}}
                    <div id="formSortir" class="bg-white rounded-xl shadow-md border border-gray-200 hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Form Penjualan (Stok Sortir Bahan)</h2>
                            <p class="text-sm text-gray-500 mt-1">Lengkapi data penjualan barang langsung dari hasil sortir bahan</p>
                        </div>

                        <form action="{{ route('barang.keluar.sell.sortir.store') }}" method="POST" class="p-6">
                            @csrf

                            <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Parent Grade Select --}}
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2">
                                            Parent Grade Perusahaan <span class="text-red-500">*</span>
                                        </label>

                                        <select name="parent_grade_company_id" id="sort_parent_grade_company_id" required
                                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">-- Pilih Parent Grade --</option>
                                            @foreach($sortStocks as $pg)
                                                <option value="{{ $pg['id'] }}" 
                                                    data-stock="{{ $pg['stock'] }}"
                                                    {{ old('parent_grade_company_id') == $pg['id'] ? 'selected' : '' }}>
                                                    {{ $pg['name'] }} (Stok Mentah: {{ number_format($pg['stock'], 2, ',', '.') }} gr)
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('parent_grade_company_id')
                                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Optional Child Grade --}}
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2">
                                            Detail Grade Company <span class="text-gray-400 font-normal text-xs">(Opsional - Kosongkan jika jual dari Parent)</span>
                                        </label>
                                        <select name="grade_company_id" id="sort_grade_company_id"
                                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                            <option value="">-- Jual Langsung dari Parent --</option>
                                            @foreach($sortGradesWithStock as $child)
                                                <option value="{{ $child['id'] }}" 
                                                    data-parent-id="{{ $child['parent_grade_company_id'] }}"
                                                    data-stock="{{ $child['stock'] }}">
                                                    {{ $child['name'] }} (Stok Sortir: {{ number_format($child['stock'], 2, ',', '.') }} gr)
                                                </option>
                                            @endforeach
                                        </select>

                                        {{-- Stock hint --}}
                                        <p id="sort-stock-hint" class="mt-2 text-sm text-gray-500">
                                            Stok sortir grade: <span id="sort-stock-value" class="font-semibold text-green-600">-</span>
                                        </p>
                                    </div>
                                </div>

                                {{-- Weight & Date --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2">
                                            Berat Penjualan (gram) <span class="text-red-500">*</span>
                                        </label>
                                        <div class="flex gap-2">
                                            <input type="number" name="weight" id="sort_weight"
                                                step="0.01" min="0.01" required
                                                value="{{ old('weight') }}"
                                                class="flex-1 border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                placeholder="Masukkan berat dalam gram">
                                            <button type="button" onclick="checkSortStock()" id="btnCheckSortStock"
                                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap">
                                                Cek Stok
                                            </button>
                                        </div>
                                        <p id="sort-check-result" class="mt-2 text-sm hidden"></p>
                                        @error('weight')
                                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block font-semibold text-gray-700 mb-2">Tanggal Penjualan</label>
                                        <input type="date" name="sale_date"
                                            value="{{ old('sale_date', date('Y-m-d')) }}"
                                            class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        @error('sale_date')
                                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Notes --}}
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Catatan Penjualan Sortir
                                        <span class="text-gray-400 font-normal text-xs">(Opsional)</span>
                                    </label>
                                    <textarea name="notes" rows="3"
                                        placeholder="Keterangan penjualan sortir bahan..."
                                        class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Form Actions --}}
                            <div class="flex items-center gap-3 pt-6 border-t border-gray-200 mt-6">
                                <button type="reset"
                                    class="flex-1 inline-flex items-center justify-center px-4 py-3 border-2 border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 transition-all">
                                    Reset
                                </button>
                                <button type="submit"
                                    class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-purple-800 transition-all duration-200 shadow-lg">
                                    Simpan Penjualan Sortir
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- History Tab (Hidden by default) --}}
                <div id="historyTab" class="tab-content hidden">

                    {{-- History Tab Headers --}}
                    <div class="mb-4 flex gap-2 border-b border-gray-200 bg-white p-2 rounded-t-xl">
                        <button type="button" onclick="switchHistoryTab('grading')" id="historyTabBtnGrading"
                            class="py-2 px-4 font-semibold text-sm border-b-2 border-blue-600 text-blue-600 transition-all focus:outline-none">
                            Riwayat Penjualan Grading
                        </button>
                        <button type="button" onclick="switchHistoryTab('sortir')" id="historyTabBtnSortir"
                            class="py-2 px-4 font-semibold text-sm text-gray-500 hover:text-gray-700 transition-all border-b-2 border-transparent focus:outline-none">
                            Riwayat Penjualan Sortir Bahan
                        </button>
                    </div>

                    {{-- 1. HISTORY PENJUALAN GRADING --}}
                    <div id="historyGrading" class="bg-white rounded-xl shadow-md border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <form action="{{ route('barang.keluar.sell.form') }}" method="GET"
                                    class="flex flex-wrap items-end gap-4">
                                    <input type="hidden" name="active_tab" value="grading">
                                    @if (request('page'))
                                        <input type="hidden" name="page" value="{{ request('page') }}">
                                    @endif

                                    <div>
                                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                                        <input type="date" name="start_date" id="start_date"
                                            value="{{ request('start_date') }}"
                                            class="w-full md:w-auto text-sm border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>

                                    <div>
                                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                                        <input type="date" name="end_date" id="end_date"
                                            value="{{ request('end_date') }}"
                                            class="w-full md:w-auto text-sm border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>

                                    <div>
                                        <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                                        <select name="supplier_id" id="supplier_id" class="w-full md:w-auto text-sm border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">Semua Supplier</option>
                                            @foreach($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                    {{ $supplier->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="grade_company_id" class="block text-sm font-medium text-gray-700 mb-1">Grade</label>
                                        <select name="grade_company_id" class="w-full md:w-auto text-sm border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">Semua Grade</option>
                                            @foreach($grades as $grade)
                                                <option value="{{ $grade->id }}" {{ request('grade_company_id') == $grade->id ? 'selected' : '' }}>
                                                    {{ $grade->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="flex gap-2">
                                        <button type="submit"
                                            class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                            Filter
                                        </button>

                                        <a href="{{ route('barang.keluar.sell.export', request()->query()) }}"
                                            class="flex items-center gap-1 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition-colors">
                                            Export Excel
                                        </a>

                                        @if (request('start_date') || request('end_date') || request('supplier_id') || request('grade_company_id'))
                                            <a href="{{ route('barang.keluar.sell.form', ['active_tab' => 'grading']) }}"
                                                class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition-colors">
                                                Reset
                                            </a>
                                        @endif
                                    </div>
                                </form>

                                <button onclick="toggleHistoryTab()"
                                    class="text-sm text-gray-600 hover:text-gray-800 flex items-center gap-1 px-3 py-1.5 hover:bg-gray-100 rounded transition md:ml-auto">
                                    Tutup
                                </button>
                            </div>

                            {{-- Summary Section --}}
                            @if(isset($summary) && $summary->count() > 0)
                                <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-100">
                                    <h4 class="text-sm font-semibold text-blue-800 mb-2">Total Stok Terjual per Grade (Sesuai Filter)</h4>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        @foreach($summary as $gradeName => $totalWeight)
                                            <div class="bg-white p-3 rounded shadow-sm">
                                                <div class="text-xs text-gray-500">{{ $gradeName }}</div>
                                                <div class="text-lg font-bold text-blue-600">
                                                    {{ number_format($totalWeight, 0, ',', '.') }} <span class="text-xs font-normal text-gray-500">gr</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Grade</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Supplier</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Lokasi</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Stok Berkurang</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Referensi</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($penjualanTransactions as $tx)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ \Carbon\Carbon::parse($tx->transaction_date)->format('d/m/Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $tx->gradeCompany->name ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $tx->sortingResult->receiptItem->purchaseReceipt->supplier->name ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $tx->location->name ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-blue-600 text-right">
                                                {{ number_format(abs($tx->quantity_change_grams), 2) }} gr
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-mono text-gray-600 bg-gray-50">
                                                #{{ $tx->id }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <form action="{{ route('barang.keluar.sell.destroy', $tx->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi penjualan ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Hapus">
                                                        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                                <p class="text-base text-gray-600">Belum ada riwayat penjualan grading.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($penjualanTransactions->hasPages())
                            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                                {{ $penjualanTransactions->links() }}
                            </div>
                        @endif
                    </div>

                    {{-- 2. HISTORY PENJUALAN SORTIR --}}
                    <div id="historySortir" class="bg-white rounded-xl shadow-md border border-gray-200 hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <form action="{{ route('barang.keluar.sell.form') }}" method="GET"
                                    class="flex flex-wrap items-end gap-4">
                                    <input type="hidden" name="active_tab" value="sortir">

                                    <div>
                                        <label for="sort_start_date" class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                                        <input type="date" name="sort_start_date" id="sort_start_date"
                                            value="{{ request('sort_start_date') }}"
                                            class="w-full md:w-auto text-sm border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>

                                    <div>
                                        <label for="sort_end_date" class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                                        <input type="date" name="sort_end_date" id="sort_end_date"
                                            value="{{ request('sort_end_date') }}"
                                            class="w-full md:w-auto text-sm border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>

                                    <div>
                                        <label for="sort_parent_grade_id" class="block text-sm font-medium text-gray-700 mb-1">Parent Grade</label>
                                        <select name="sort_parent_grade_id" id="sort_parent_grade_id" class="w-full md:w-auto text-sm border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">Semua Parent Grade</option>
                                            @foreach($parentGrades as $pg)
                                                <option value="{{ $pg->id }}" {{ request('sort_parent_grade_id') == $pg->id ? 'selected' : '' }}>
                                                    {{ $pg->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="flex gap-2">
                                        <button type="submit"
                                            class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700 focus:outline-none transition-colors">
                                            Filter
                                        </button>

                                        <a href="{{ route('barang.keluar.sell.sortir.export', request()->query()) }}"
                                            class="flex items-center gap-1 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition-colors">
                                            Export Excel
                                        </a>

                                        @if (request('sort_start_date') || request('sort_end_date') || request('sort_parent_grade_id'))
                                            <a href="{{ route('barang.keluar.sell.form', ['active_tab' => 'sortir']) }}"
                                                class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition-colors">
                                                Reset
                                            </a>
                                        @endif
                                    </div>
                                </form>

                                <button onclick="toggleHistoryTab()"
                                    class="text-sm text-gray-600 hover:text-gray-800 flex items-center gap-1 px-3 py-1.5 hover:bg-gray-100 rounded transition md:ml-auto">
                                    Tutup
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Parent Grade</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Grade Company</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Catatan / Deskripsi</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Berat Keluar</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Referensi ID</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($sortSaleTransactions as $tx)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $tx->sale_date ? $tx->sale_date->format('d/m/Y') : $tx->created_at->format('d/m/Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                {{ $tx->parentGradeCompany->name ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {{ $tx->gradeCompany->name ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="{{ $tx->notes }}">
                                                {{ $tx->notes ?? 'Tidak ada catatan' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-purple-600 text-right">
                                                {{ number_format($tx->weight, 2) }} gr
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-mono text-gray-500 bg-gray-50">
                                                #{{ $tx->id }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <form action="{{ route('barang.keluar.sell.sortir.destroy', $tx->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus penjualan sortir bahan ini? Stok sortir akan dikembalikan.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Hapus Penjualan Sortir">
                                                        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                                <p class="text-base text-gray-600">Belum ada riwayat penjualan dari sortir bahan.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($sortSaleTransactions->hasPages())
                            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                                {{ $sortSaleTransactions->links() }}
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Active Form Tab State: 'grading' or 'sortir'
            let currentFormTab = 'grading';
            let currentHistoryTab = 'grading';

            // Toggle Form Tab (Stok Grading vs Stok Sortir)
            function switchFormTab(tab) {
                currentFormTab = tab;
                const formGrading = document.getElementById('formGrading');
                const formSortir = document.getElementById('formSortir');
                const btnGrading = document.getElementById('formTabBtnGrading');
                const btnSortir = document.getElementById('formTabBtnSortir');

                if (tab === 'grading') {
                    formGrading.classList.remove('hidden');
                    formSortir.classList.add('hidden');
                    
                    btnGrading.className = "py-2.5 px-4 font-semibold text-sm border-b-2 border-blue-600 text-blue-600 transition-all focus:outline-none";
                    btnSortir.className = "py-2.5 px-4 font-semibold text-sm text-gray-500 hover:text-gray-700 transition-all border-b-2 border-transparent focus:outline-none";
                } else {
                    formGrading.classList.add('hidden');
                    formSortir.classList.remove('hidden');

                    btnGrading.className = "py-2.5 px-4 font-semibold text-sm text-gray-500 hover:text-gray-700 transition-all border-b-2 border-transparent focus:outline-none";
                    btnSortir.className = "py-2.5 px-4 font-semibold text-sm border-b-2 border-purple-600 text-purple-600 transition-all focus:outline-none";
                }
            }

            // Toggle History Tab (Penjualan Grading vs Penjualan Sortir)
            function switchHistoryTab(tab) {
                currentHistoryTab = tab;
                const histGrading = document.getElementById('historyGrading');
                const histSortir = document.getElementById('historySortir');
                const btnGrading = document.getElementById('historyTabBtnGrading');
                const btnSortir = document.getElementById('historyTabBtnSortir');

                if (tab === 'grading') {
                    histGrading.classList.remove('hidden');
                    histSortir.classList.add('hidden');

                    btnGrading.className = "py-2 px-4 font-semibold text-sm border-b-2 border-blue-600 text-blue-600 transition-all focus:outline-none";
                    btnSortir.className = "py-2 px-4 font-semibold text-sm text-gray-500 hover:text-gray-700 transition-all border-b-2 border-transparent focus:outline-none";
                } else {
                    histGrading.classList.add('hidden');
                    histSortir.classList.remove('hidden');

                    btnGrading.className = "py-2 px-4 font-semibold text-sm text-gray-500 hover:text-gray-700 transition-all border-b-2 border-transparent focus:outline-none";
                    btnSortir.className = "py-2 px-4 font-semibold text-sm border-b-2 border-purple-600 text-purple-600 transition-all focus:outline-none";
                }
            }

            // Toggle History Tab Function (Full Section Toggle)
            function toggleHistoryTab() {
                const formTab = document.getElementById('formTab');
                const historyTab = document.getElementById('historyTab');
                const toggleBtn = document.getElementById('historyToggleBtn');
                const toggleText = document.getElementById('historyToggleText');
                const sourceSelector = document.getElementById('sourceTabSelector');

                if (historyTab.classList.contains('hidden')) {
                    // Show history, hide form
                    formTab.classList.add('hidden');
                    sourceSelector.classList.add('hidden');
                    historyTab.classList.remove('hidden');
                    toggleText.textContent = 'Kembali ke Form';
                    toggleBtn.classList.remove('bg-blue-50', 'text-blue-700', 'border-blue-300');
                    toggleBtn.classList.add('bg-gray-100', 'text-gray-700', 'border-gray-300');
                } else {
                    // Show form, hide history
                    historyTab.classList.add('hidden');
                    formTab.classList.remove('hidden');
                    sourceSelector.classList.remove('hidden');
                    toggleText.textContent = 'Daftar Penjualan Langsung';
                    toggleBtn.classList.remove('bg-gray-100', 'text-gray-700', 'border-gray-300');
                    toggleBtn.classList.add('bg-blue-50', 'text-blue-700', 'border-blue-300');
                }
            }

            // ────────────────────────────────────────────────────────
            // JS LOGIC FOR GRADING
            // ────────────────────────────────────────────────────────
            const gradeSelect = document.getElementById('grade_company_id');
            const gradeStockValue = document.getElementById('grade-stock-value');
            const supplierFilter = document.getElementById('filter_supplier_id');

            supplierFilter.addEventListener('change', function() {
                const selectedSupplierId = this.value;
                const options = gradeSelect.querySelectorAll('option');
                
                gradeSelect.value = "";
                gradeStockValue.textContent = '-';
                gradeStockValue.classList.remove('text-green-600', 'text-red-600');
                document.getElementById('stock-check-result').classList.add('hidden');

                options.forEach(option => {
                    if (option.value === "") return;
                    const gradeSupplierId = option.dataset.supplierId;
                    if (!selectedSupplierId || gradeSupplierId == selectedSupplierId) {
                        option.style.display = '';
                        option.disabled = false;
                    } else {
                        option.style.display = 'none';
                        option.disabled = true;
                    }
                });
            });

            gradeSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value) {
                    const stock = selectedOption.dataset.stock || 0;
                    gradeStockValue.textContent = new Intl.NumberFormat('id-ID').format(stock) + ' gr';
                    gradeStockValue.classList.remove('text-red-600');
                    gradeStockValue.classList.add('text-green-600');
                } else {
                    gradeStockValue.textContent = '-';
                    gradeStockValue.classList.remove('text-green-600', 'text-red-600');
                }
            });

            function fetchStockInfo(gradeId) {
                fetch(`{{ route('barang.keluar.sell.stock_check') }}?grade_company_id=${gradeId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.ok) {
                            gradeStockValue.textContent = new Intl.NumberFormat('id-ID').format(data.available_grams) + ' gr';
                            gradeStockValue.classList.remove('text-red-600');
                            gradeStockValue.classList.add('text-green-600');
                        } else {
                            gradeStockValue.textContent = 'Error';
                        }
                    })
                    .catch(() => {
                        gradeStockValue.textContent = 'Error cek stok';
                    });
            }

            function checkStock() {
                const gradeId = document.getElementById('grade_company_id').value;
                const weight = parseFloat(document.getElementById('weight_grams').value || 0);
                const resultEl = document.getElementById('stock-check-result');

                if (!gradeId) {
                    showStockResult('Pilih grade terlebih dahulu.', 'error');
                    return;
                }
                if (weight <= 0) {
                    showStockResult('Masukkan berat yang valid.', 'error');
                    return;
                }

                showStockResult('Mengecek stok...', 'info');

                fetch(`{{ route('barang.keluar.sell.stock_check') }}?grade_company_id=${gradeId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.ok) {
                            showStockResult('Gagal mengecek stok.', 'error');
                            return;
                        }

                        const available = parseFloat(data.available_grams);
                        if (available >= weight) {
                            showStockResult(`✓ Stok mencukupi! Tersedia ${new Intl.NumberFormat('id-ID').format(available)} gram.`, 'success');
                        } else {
                            showStockResult(`⚠ Stok tidak mencukupi! Hanya tersedia ${new Intl.NumberFormat('id-ID').format(available)} gram.`, 'error');
                        }
                    })
                    .catch(() => {
                        showStockResult('Gagal mengecek stok.', 'error');
                    });
            }

            function showStockResult(message, type) {
                const resultEl = document.getElementById('stock-check-result');
                resultEl.classList.remove('hidden', 'text-red-600', 'text-green-600', 'text-blue-600');
                if (type === 'success') resultEl.classList.add('text-green-600');
                else if (type === 'error') resultEl.classList.add('text-red-600');
                else resultEl.classList.add('text-blue-600');
                resultEl.textContent = message;
            }

            // ────────────────────────────────────────────────────────
            // JS LOGIC FOR SORTIR BAHAN
            // ────────────────────────────────────────────────────────
            const sortParentSelect = document.getElementById('sort_parent_grade_company_id');
            const sortStockValue = document.getElementById('sort-stock-value');
            const sortGradeSelect = document.getElementById('sort_grade_company_id');

            function updateSortStockDisplay() {
                const parentOpt = sortParentSelect.options[sortParentSelect.selectedIndex];
                const gradeOpt = sortGradeSelect.options[sortGradeSelect.selectedIndex];
                
                if (gradeOpt && gradeOpt.value) {
                    const stock = parseFloat(gradeOpt.dataset.stock || 0);
                    sortStockValue.textContent = new Intl.NumberFormat('id-ID').format(stock) + ' gr (Detail Grade)';
                    sortStockValue.className = "font-semibold text-purple-600";
                } else if (parentOpt && parentOpt.value) {
                    const stock = parseFloat(parentOpt.getAttribute('data-stock') || 0);
                    sortStockValue.textContent = new Intl.NumberFormat('id-ID').format(stock) + ' gr (Total Parent)';
                    sortStockValue.className = "font-semibold text-blue-600";
                } else {
                    sortStockValue.textContent = '-';
                    sortStockValue.className = "font-semibold text-gray-500";
                }
                document.getElementById('sort-check-result').classList.add('hidden');
            }

            // Dynamic filter for child grade selection based on selected parent
            sortParentSelect.addEventListener('change', function() {
                const selectedParentId = this.value;
                const options = sortGradeSelect.querySelectorAll('option');

                // Reset child grade
                sortGradeSelect.value = "";
                
                options.forEach(option => {
                    if (option.value === "") return;
                    const optionParentId = option.dataset.parentId;
                    if (!selectedParentId || optionParentId == selectedParentId) {
                        option.style.display = '';
                        option.disabled = false;
                    } else {
                        option.style.display = 'none';
                        option.disabled = true;
                    }
                });

                updateSortStockDisplay();
            });

            // Display stock when specific child grade is selected
            sortGradeSelect.addEventListener('change', updateSortStockDisplay);

            function checkSortStock() {
                const parentId = sortParentSelect.value;
                const gradeId = sortGradeSelect.value;
                const weight = parseFloat(document.getElementById('sort_weight').value || 0);
                const resultEl = document.getElementById('sort-check-result');

                resultEl.classList.remove('hidden', 'text-red-600', 'text-green-600');

                if (!parentId) {
                    resultEl.textContent = 'Pilih parent grade terlebih dahulu.';
                    resultEl.classList.add('text-red-600');
                    return;
                }
                if (weight <= 0) {
                    resultEl.textContent = 'Masukkan berat yang valid.';
                    resultEl.classList.add('text-red-600');
                    return;
                }

                let available = 0;
                let targetName = '';

                if (gradeId) {
                    const selectedOption = sortGradeSelect.options[sortGradeSelect.selectedIndex];
                    available = parseFloat(selectedOption.dataset.stock || 0);
                    targetName = 'grade ini';
                } else {
                    const selectedParentOption = sortParentSelect.options[sortParentSelect.selectedIndex];
                    available = parseFloat(selectedParentOption.getAttribute('data-stock') || 0);
                    targetName = 'parent grade ini';
                }

                if (available >= weight) {
                    resultEl.textContent = `✓ Stok mencukupi! Tersedia ${new Intl.NumberFormat('id-ID').format(available)} gram untuk ${targetName}.`;
                    resultEl.classList.add('text-green-600');
                } else {
                    resultEl.textContent = `⚠ Stok tidak mencukupi! Hanya tersedia ${new Intl.NumberFormat('id-ID').format(available)} gram untuk ${targetName}.`;
                    resultEl.classList.add('text-red-600');
                }
            }

            // Initialization on DOM Loaded
            document.addEventListener('DOMContentLoaded', function() {
                const urlParams = new URLSearchParams(window.location.search);
                
                // Read active tab
                const activeTab = urlParams.get('active_tab') || 'grading';
                
                if (activeTab === 'sortir') {
                    switchFormTab('sortir');
                    switchHistoryTab('sortir');
                }

                if (urlParams.has('page') || urlParams.has('start_date') || urlParams.has('end_date') || urlParams.has('sort_start_date') || urlParams.has('sort_end_date')) {
                    toggleHistoryTab();
                }

                const selectedGrade = gradeSelect.value;
                if (selectedGrade) {
                    fetchStockInfo(selectedGrade);
                }
            });

            // Resets
            document.querySelectorAll('button[type="reset"]').forEach(btn => {
                btn.addEventListener('click', function() {
                    setTimeout(() => {
                        gradeStockValue.textContent = '-';
                        sortStockValue.textContent = '-';
                        document.getElementById('stock-check-result').classList.add('hidden');
                        document.getElementById('sort-check-result').classList.add('hidden');
                    }, 10);
                });
            });
        </script>
    @endpush
@endsection
