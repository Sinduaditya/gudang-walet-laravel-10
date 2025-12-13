@extends('layouts.app')

@section('title', 'Tambah Barang Transfer - Step 1')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Tambah Barang Transfer</h1>
                    <p class="mt-1 text-sm text-gray-600">Pilih barang IDM yang akan ditransfer.</p>
                </div>
                <a href="{{ route('barang.keluar.transfer-idm.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali
                </a>
            </div>

            <!-- Progress Indicator matching Grading Goods -->
            <div class="mb-8 bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center justify-between max-w-xl mx-auto">
                    <div class="flex flex-col items-center flex-1">
                        <div
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-500 text-white font-semibold text-sm shadow-sm">
                            1</div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-blue-600">Pilih Barang</span>
                    </div>
                    <div class="flex-1 h-0.5 bg-gray-200 mx-2 sm:mx-4 -mt-6"></div>
                    <div class="flex flex-col items-center flex-1">
                        <div
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-400 font-semibold text-sm">
                            2</div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-gray-400">Konfirmasi Transfer</span>
                    </div>
                </div>
            </div>

            <!-- Main Form Wrapper -->
            <!-- We need a form that handles BOTH the date input for the NEXT step AND the item selection. -->
            <!-- However, filters are GET requests to refresh the page. -->
            <!-- Creating a unified flow:
                 1. The "Transfer Date" is an INPUT for the CREATE action (Step 2).
                 2. Filters are for finding items.
                 We can put "Transfer Date" inside the main POST form.
            -->

            <div class="bg-white shadow-sm border rounded-lg overflow-hidden mb-6">

                <!-- Main Form for Submission -->
                <form action="{{ route('barang.keluar.transfer-idm.step2') }}" method="POST" id="mainForm">
                    @csrf

                    <!-- Top Section: Data Input (Transfer Date) -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="max-w-md">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tanggal Transfer <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="date" name="transfer_date"
                                    value="{{ request('transfer_date', date('Y-m-d')) }}"
                                    class="w-full border border-gray-300 rounded-lg pl-4 pr-10 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all text-sm font-semibold text-gray-900"
                                    required>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Grid Content -->
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                             <h2 class="text-base font-semibold text-gray-900">Pilih Barang</h2>
                             <!-- Place hidden inputs or maintain state if needed, but for now filters are separate -->
                        </div>

                        <!-- Filter Section (GET Form) - Placed visually here but technically a separate form?
                             If we put a form inside a form, HTML breaks.
                             Solution: Put the Filter Form OUTSIDE, but visually integrated or just above.
                             User requested "Align Supplier, Grade Company, Grade IDM".
                             Let's place the Filter Form *above* this "Items Grid" section, or simply outside.
                             Let's restructure:
                             1. Filter Form (GET)
                             2. Main Form (POST)
                        -->
                    </div>
                </form>

                <!-- Filter Section (Independent) -->
                <div class="px-6 pb-6 border-b border-gray-200 bg-gray-50 pt-4">
                    <form action="{{ route('barang.keluar.transfer-idm.create') }}" method="GET">
                        <input type="hidden" name="transfer_date" value="{{ request('transfer_date', date('Y-m-d')) }}">

                        <div class="flex flex-col gap-2">
                             <label class="block text-sm font-medium text-gray-700">Filter Barang</label>
                             <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                                <!-- Supplier -->
                                <div>
                                    <select name="supplier_id"
                                        class="w-full border border-gray-300 rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Supplier</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Grade Company -->
                                <div>
                                    <select name="grade_company_id" id="grade_company_id"
                                        class="w-full border border-gray-300 rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Grade Company</option>
                                        @foreach($gradeCompanies as $gc)
                                            <option value="{{ $gc->id }}"
                                                data-valid-supplier-ids="{{ json_encode($gc->valid_supplier_ids ?? []) }}"
                                                {{ request('grade_company_id') == $gc->id ? 'selected' : '' }}>
                                                {{ $gc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Grade IDM -->
                                <div>
                                    <select name="grade_idm_name"
                                        class="w-full border border-gray-300 rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Grade IDM</option>
                                        @foreach($gradeIdms as $name)
                                            <option value="{{ $name }}" {{ request('grade_idm_name') == $name ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Source Location -->
                                <div>
                                    <select name="source_location_id"
                                        class="w-full border border-gray-300 rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Pilih Lokasi Asal</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" {{ (request('source_location_id') == $location->id || $location->name == 'Gudang Utama') ? 'selected' : '' }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Tipe IDM -->
                                <div>
                                    <select name="idm_type"
                                        class="w-full border border-gray-300 rounded-lg px-2 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Tipe IDM</option>
                                        @foreach($idmTypes as $type)
                                            <option value="{{ $type }}" {{ request('idm_type') == $type ? 'selected' : '' }}>
                                                {{ $type }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Buttons -->
                                <div class="flex items-center gap-2">
                                    <button type="submit"
                                        class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-xs font-medium transition duration-200">
                                        Filter
                                    </button>
                                    <a href="{{ route('barang.keluar.transfer-idm.create') }}"
                                        class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-xs font-medium transition duration-200">
                                        Reset
                                    </a>
                                </div>
                             </div>
                        </div>
                    </form>
                </div>

                <!-- Item Grid (Inside Main Form Context implicitly via 'form="mainForm"' attribute on inputs?
                     No, the grid needs to be INSIDE the <form> tag.
                     But I closed the form tag early above. Let me fix the nesting.
                     Structure:
                     <form id="mainForm">
                        Top Date Input
                     </form>
                     Filter Div (Outside form)
                     <div form="mainForm" ...> Items </div> -> No, div isn't form element.

                     Correct Approach:
                     Outer Container
                        1. Form Part 1 (Date) - Part of Main Form
                        2. Filter Section - GET Form (Separate)
                        3. Form Part 2 (Grid & Submit) - Part of Main Form

                     Since we can't split a FORM tag across a separate DIV easily without JS or messy HTML,
                     We will put the GET form *before* the POST form, OR wrap everything in the POST form and use JS for filtering?
                     No, standard Laravel pattern is GET for filter, POST for action.

                     Better Structure:
                     1. Filter Form (GET) - Includes Supplier/Grade/IDM. "Transfer Date" should generally NOT be here if it's not filtering.
                     2. Main Form (POST) - Includes "Transfer Date" input, and Item Grid.

                     The User asked to "Data Input" at top? No, "Transfer Date" to top.
                     So:
                     [Card]
                       [Filter Section (GET)] -> Supplier, Grade Co, Grade IDM.
                       [Main Form (POST)]
                         [Date Input] matches user request "tgl transfer yg dibawah pindah keatas" (from previous turn, seemingly separate from filters now).
                         [Grid]
                         [Footer]

                     Let's stick to this Separation.
                -->

                <div class="p-6">
                     @if($items->count() > 0)
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            @foreach($items as $item)
                                <label class="cursor-pointer group relative">
                                    <input type="checkbox" name="selected_items[]" value="{{ $item->id }}" form="mainForm" class="peer sr-only">
                                    <div class="border-2 border-gray-200 rounded-xl p-4 transition-all duration-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300 hover:shadow-md h-full flex flex-col items-center text-center">
                                        <div class="absolute top-2 right-2 w-6 h-6 bg-white border-2 border-gray-300 rounded-full flex items-center justify-center transition-all duration-200 peer-checked:bg-blue-600 peer-checked:border-blue-600 shadow-sm">
                                            <svg class="w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 transition-opacity duration-200" fill="currentColor" viewBox="0 0 20 20">
                                                <circle cx="10" cy="10" r="4" />
                                            </svg>
                                        </div>
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                             <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                        </div>
                                        <h3 class="font-bold text-gray-900 text-sm mb-1 leading-tight">{{ $item->grade_idm_name }}</h3>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mb-1">
                                            {{ $item->idmManagement->sourceItems->first()->category_grade ?? '-' }}
                                        </span>
                                        <p class="text-xs text-gray-500 mb-2">{{ $item->idmManagement->supplier->name ?? 'No Supplier' }}</p>
                                        <div class="mt-auto w-full pt-2 border-t border-gray-100 space-y-1">
                                            <div class="flex justify-between text-xs">
                                                <span class="text-gray-500">Berat:</span>
                                                <span class="font-medium text-gray-900">{{ $item->weight }} g</span>
                                            </div>
                                            <div class="flex justify-between text-xs">
                                                <span class="text-gray-500">Harga:</span>
                                                <span class="font-medium text-gray-900">Rp {{ number_format($item->total_price, 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">Tidak ada barang ditemukan</h3>
                            <p class="mt-1 text-sm text-gray-500">Coba ubah filter pencarian anda.</p>
                        </div>
                    @endif
                </div>

                <!-- Footer Action Button -->
                <div class="px-6 py-6 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('barang.keluar.transfer-idm.index') }}"
                           class="flex-1 inline-flex items-center justify-center px-4 py-2.5 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Batal
                        </a>
                        <button type="submit"
                                form="mainForm"
                                class="flex-1 inline-flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-500 hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm transition-all">
                            Lanjut ke Step 2
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Redundant Form close removed since we use id association or move form tag -->

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // JS for Dependent Filtering (Supplier -> Grade Company)
            const supplierSelect = document.querySelector('select[name="supplier_id"]');
            const gradeSelect = document.getElementById('grade_company_id');
            const gradeOptions = gradeSelect.querySelectorAll('option');

            function filterGrades() {
                const selectedSupplierId = supplierSelect.value;
                let hasVisibleOption = false;

                gradeOptions.forEach(option => {
                    if (option.value === "") {
                        option.style.display = ""; // Always show placeholder
                        return;
                    }

                    const validSupplierIds = JSON.parse(option.dataset.validSupplierIds || "[]");

                    if (!selectedSupplierId || validSupplierIds.includes(parseInt(selectedSupplierId))) {
                        option.style.display = "";
                        hasVisibleOption = true;
                    } else {
                        option.style.display = "none";
                        // If current selected value is hidden, deselect it
                        if (gradeSelect.value === option.value) {
                            gradeSelect.value = "";
                        }
                    }
                });
            }

            // Init
            if(supplierSelect && gradeSelect) {
                supplierSelect.addEventListener('change', filterGrades);
                // Run once on load to respect current server-side filter state + js logic
                filterGrades();
            }
        });
    </script>
@endsection
