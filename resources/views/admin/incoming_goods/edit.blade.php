@extends('layouts.app')

@section('title', 'Edit Barang Masuk')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Edit Barang Masuk</h1>
                    <p class="mt-1 text-sm text-gray-600">Edit data penerimaan barang dari supplier</p>
                </div>
                <a href="{{ route('incoming-goods.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm">
                    Kembali ke Daftar
                </a>
            </div>
        </div>

        <form action="{{ route('incoming-goods.update', $receipt->id) }}" method="POST" id="editForm">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Basic Info -->
                <div class="lg:col-span-1">
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Penerimaan</h3>
                        
                        <!-- Supplier -->
                        <div class="mb-4">
                            <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                            <select name="supplier_id" id="supplier_id" required
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('supplier_id') border-red-500 @enderror">
                                <option value="">Pilih Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id', $receipt->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('supplier_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Receipt Date -->
                        <div class="mb-4">
                            <label for="receipt_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Kedatangan</label>
                            <input type="date" name="receipt_date" id="receipt_date" required
                                value="{{ old('receipt_date', optional($receipt->receipt_date)->format('Y-m-d')) }}"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('receipt_date') border-red-500 @enderror">
                            @error('receipt_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Unloading Date -->
                        <div class="mb-4">
                            <label for="unloading_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Bongkar</label>
                            <input type="date" name="unloading_date" id="unloading_date" required
                                value="{{ old('unloading_date', optional($receipt->unloading_date)->format('Y-m-d')) }}"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('unloading_date') border-red-500 @enderror">
                            @error('unloading_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                            <textarea name="notes" id="notes" rows="3" 
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('notes') border-red-500 @enderror"
                                placeholder="Catatan tambahan...">{{ old('notes', $receipt->notes) }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="lg:col-span-2">
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Detail Item</h3>
                            <button type="button" onclick="addNewItem()" 
                                class="bg-blue-500 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                Tambah Item
                            </button>
                        </div>

                        <div id="itemsContainer">
                            @foreach($receipt->receiptItems as $index => $item)
                                <div class="item-row border border-gray-200 rounded-lg p-4 mb-4" data-index="{{ $index }}">
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="font-medium text-sm text-gray-700">Item {{ $index + 1 }}</h4>
                                        <button type="button" onclick="removeItem(this)" 
                                            class="text-red-600 hover:text-red-800 text-sm">
                                            Hapus
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <!-- Grade Supplier -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Grade Supplier</label>
                                            <select name="items[{{ $index }}][grade_supplier_id]" required
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option value="">Pilih Grade</option>
                                                @foreach($gradeSuppliers as $grade)
                                                    <option value="{{ $grade->id }}" {{ $item->grade_supplier_id == $grade->id ? 'selected' : '' }}>
                                                        {{ $grade->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Supplier Weight -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Berat Nota (gram)</label>
                                            <input type="number" name="items[{{ $index }}][supplier_weight_grams]" required
                                                value="{{ $item->supplier_weight_grams }}"
                                                class="supplier-weight w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                onchange="calculateDifference(this)">
                                        </div>

                                        <!-- Warehouse Weight -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Berat Timbang (gram)</label>
                                            <input type="number" name="items[{{ $index }}][warehouse_weight_grams]" required
                                                value="{{ $item->warehouse_weight_grams }}"
                                                class="warehouse-weight w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                onchange="calculateDifference(this)">
                                        </div>

                                        <!-- Moisture Percentage -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Kadar Air (%)</label>
                                            <input type="number" name="items[{{ $index }}][moisture_percentage]" step="0.01"
                                                value="{{ $item->moisture_percentage }}"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>

                                    <!-- Difference Display -->
                                    <div class="mt-3">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Selisih</label>
                                        <span class="difference-display text-sm font-medium {{ $item->difference_grams < 0 ? 'text-red-600' : ($item->difference_grams > 0 ? 'text-green-600' : 'text-gray-600') }}">
                                            {{ $item->difference_grams < 0 ? $item->difference_grams . ' (susut)' : ($item->difference_grams > 0 ? '+' . $item->difference_grams . ' (bertambah)' : '0 (sama)') }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @error('items')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('incoming-goods.show', $receipt->id) }}" 
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                    Batal
                </a>
                <button type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    let itemIndex = {{ $receipt->receiptItems->count() }};

    function addNewItem() {
        const container = document.getElementById('itemsContainer');
        const newItemHtml = `
            <div class="item-row border border-gray-200 rounded-lg p-4 mb-4" data-index="${itemIndex}">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-medium text-sm text-gray-700">Item ${itemIndex + 1}</h4>
                    <button type="button" onclick="removeItem(this)" 
                        class="text-red-600 hover:text-red-800 text-sm">
                        Hapus
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Grade Supplier -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Grade Supplier</label>
                        <select name="items[${itemIndex}][grade_supplier_id]" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih Grade</option>
                            @foreach($gradeSuppliers as $grade)
                                <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Supplier Weight -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Berat Nota (gram)</label>
                        <input type="number" name="items[${itemIndex}][supplier_weight_grams]" required
                            class="supplier-weight w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onchange="calculateDifference(this)">
                    </div>

                    <!-- Warehouse Weight -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Berat Timbang (gram)</label>
                        <input type="number" name="items[${itemIndex}][warehouse_weight_grams]" required
                            class="warehouse-weight w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onchange="calculateDifference(this)">
                    </div>

                    <!-- Moisture Percentage -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kadar Air (%)</label>
                        <input type="number" name="items[${itemIndex}][moisture_percentage]" step="0.01"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Difference Display -->
                <div class="mt-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Selisih</label>
                    <span class="difference-display text-sm font-medium text-gray-600">0 (sama)</span>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', newItemHtml);
        itemIndex++;
        updateItemNumbers();
    }

    function removeItem(button) {
        if (document.querySelectorAll('.item-row').length <= 1) {
            alert('Harus ada minimal 1 item');
            return;
        }
        
        button.closest('.item-row').remove();
        updateItemNumbers();
    }

    function updateItemNumbers() {
        const items = document.querySelectorAll('.item-row');
        items.forEach((item, index) => {
            const title = item.querySelector('h4');
            title.textContent = `Item ${index + 1}`;
        });
    }

    function calculateDifference(input) {
        const itemRow = input.closest('.item-row');
        const supplierWeight = parseFloat(itemRow.querySelector('.supplier-weight').value) || 0;
        const warehouseWeight = parseFloat(itemRow.querySelector('.warehouse-weight').value) || 0;
        const difference = warehouseWeight - supplierWeight;
        const displaySpan = itemRow.querySelector('.difference-display');
        
        let percentage = 0;
        let decimal = 0;
        
        if (supplierWeight > 0) {
            decimal = difference / supplierWeight; // ✅ Desimal bisa negatif/positif
            percentage = Math.abs(decimal) * 100; // ✅ Persentase selalu positif
        }
        
        let displayText = '';
        let colorClass = '';
        
        if (difference < 0) {
            displayText = `${formatNumber(difference)} gr (susut)`;
            colorClass = 'text-red-600';
        } else if (difference > 0) {
            displayText = `+${formatNumber(difference)} gr (kelebihan)`;
            colorClass = 'text-green-600';
        } else {
            displayText = '0 gr (sama)';
            colorClass = 'text-gray-600';
        }

        if (percentage !== 0 || decimal !== 0) {
            // ✅ Format decimal: 3 desimal, koma sebagai desimal
            const formattedDecimal = decimal.toFixed(3).replace('.', ',');
            
            // ✅ Format percentage: bulat atau 1 desimal, koma sebagai desimal
            const formattedPercentage = (percentage == Math.floor(percentage))
                ? Math.round(percentage).toString()
                : percentage.toFixed(1).replace('.', ',');
            
            // ✅ FIX: Update threshold ke 5%
            if (percentage > 5) {  // ✅ 5% threshold
                displayText += ` | <span class="text-red-600 font-bold">Rasio: ${formattedDecimal} | ${formattedPercentage}% ⚠️</span>`;
            } else if (percentage > 1) { // 1%
                displayText += ` | <span class="text-orange-600">Rasio: ${formattedDecimal} | ${formattedPercentage}%</span>`;
            } else {
                displayText += ` | <span class="text-green-600">Rasio: ${formattedDecimal} | ${formattedPercentage}%</span>`;
            }
        }
        
        displaySpan.innerHTML = displayText;
        displaySpan.className = `difference-display text-sm font-medium ${colorClass}`;
    }

    // ✅ Helper function untuk format angka Indonesia
    function formatNumber(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }
</script>
@endpush
@endsection