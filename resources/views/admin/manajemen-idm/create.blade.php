@extends('layouts.app')

@section('title', 'Tambah IDM Step 1')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Tambah IDM Step 1</h1>
                    <p class="mt-1 text-sm text-gray-600">Pilih item dari stok yang tersedia untuk estimasi IDM.</p>
                </div>
                <a href="{{ route('manajemen-idm.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                    Kembali
                </a>
            </div>

            <!-- Progress Steps -->
            <div class="mb-8 bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center justify-between max-w-2xl mx-auto">
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-500 text-white font-semibold text-sm shadow-sm">
                            1
                        </div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-blue-600">Pilih Item</span>
                    </div>
                    <div class="flex-1 h-0.5 bg-gray-200 mx-2 sm:mx-4 -mt-6"></div>
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-400 font-semibold text-sm">
                            2
                        </div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-gray-400">Lengkapi Data</span>
                    </div>
                    <div class="flex-1 h-0.5 bg-gray-200 mx-2 sm:mx-4 -mt-6"></div>
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-400 font-semibold text-sm">
                            3
                        </div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-gray-400">Konfirmasi</span>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex space-x-4 mb-6">
                <a href="{{ route('manajemen-idm.create', array_merge(request()->except('category'), ['category' => 'IDM A'])) }}" 
                   class="px-6 py-2 border rounded-md font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 {{ $category == 'IDM A' ? 'bg-blue-600 text-white border-transparent' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                    IDM A
                </a>
                <a href="{{ route('manajemen-idm.create', array_merge(request()->except('category'), ['category' => 'IDM B'])) }}" 
                   class="px-6 py-2 border rounded-md font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 {{ $category == 'IDM B' ? 'bg-blue-600 text-white border-transparent' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                    IDM B
                </a>
            </div>

            <!-- Form -->
            <form action="{{ route('manajemen-idm.store') }}" method="POST">
                @csrf
                
                <div class="bg-white shadow-sm border rounded-lg">
                    <!-- Filters Section -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            <!-- Filter From -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Filter From</label>
                                <input type="date" name="from_date" value="{{ request('from_date') }}"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    onchange="applyFilters(this)">
                            </div>
        
                            <!-- Filter To -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                                <input type="date" name="to_date" value="{{ request('to_date') }}"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    onchange="applyFilters(this)">
                            </div>
        
                            <!-- Filter Supplier -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                                <select name="supplier_id"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    onchange="applyFilters(this)">
                                    <option value="">Semua Supplier</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
        
                            <!-- Filter Barang -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Barang</label>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Barang..."
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    oninput="debounceSubmit(this.form)">
                            </div>
                        </div>
                    </div>

                    <!-- Grid Cards -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            @forelse ($items as $item)
                                <div class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-md transition-shadow cursor-pointer relative group" onclick="toggleCheckbox(this)">
                                    <div class="absolute top-4 right-4">
                                        <input type="checkbox" name="selected_items[]" value="{{ $item->id }}" class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded pointer-events-none">
                                    </div>
                                    <div class="flex flex-col h-full justify-center items-center text-center space-y-2">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $item->gradeCompany->name ?? 'Unknown Grade' }}</h3>
                                        <p class="text-sm text-gray-500">{{ $item->receiptItem->purchaseReceipt->supplier->name ?? 'Unknown Supplier' }}</p>
                                        <div class="mt-2 text-xs text-gray-400">
                                            {{ number_format($item->weight_grams, 0, ',', '.') }} gr
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-full text-center py-12 text-gray-500">
                                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium">Tidak ada barang yang tersedia.</p>
                                    <p class="text-xs mt-1">Coba ubah filter pencarian Anda.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Footer / Submit Button -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg flex justify-end">
                        <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Lanjut
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Pagination -->
            @if($items->hasPages())
                <div class="mt-6">
                    {{ $items->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        // Debounce search input
        let timeout = null;
        function debounceSubmit(form) {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                // We need to submit as GET to filter, but the form is POST for store.
                // So we'll create a temporary GET request for filtering.
                const url = new URL("{{ route('manajemen-idm.create') }}");
                const formData = new FormData(form);
                for (const [key, value] of formData.entries()) {
                    if (key !== '_token' && key !== 'selected_items[]') {
                        url.searchParams.append(key, value);
                    }
                }
                window.location.href = url.toString();
            }, 500);
        }

        // Handle filter changes (select/date)
        // Note: The onchange="this.form.submit()" in HTML will try to POST to store.
        // We need to intercept this or change the HTML to call a JS function that redirects.
        // Let's update the HTML inputs to call a JS function instead of direct submit.
        
        function applyFilters(input) {
             const form = input.form;
             const url = new URL("{{ route('manajemen-idm.create') }}");
             const formData = new FormData(form);
             for (const [key, value] of formData.entries()) {
                 if (key !== '_token' && key !== 'selected_items[]') {
                     url.searchParams.append(key, value);
                 }
             }
             window.location.href = url.toString();
        }

        // Toggle checkbox on card click
        function toggleCheckbox(element) {
            const checkbox = element.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                element.classList.add('ring-2', 'ring-indigo-500', 'bg-indigo-50');
            } else {
                element.classList.remove('ring-2', 'ring-indigo-500', 'bg-indigo-50');
            }
        }
    </script>
    @endpush
@endsection
