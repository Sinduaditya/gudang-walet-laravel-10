@extends('layouts.app')

@section('title', 'Edit Transfer IDM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h4 class="text-xl font-semibold text-gray-800">Edit Transfer IDM</h4>
            <div class="text-sm text-gray-500">Code: {{ $transfer->transfer_code }}</div>
        </div>
        <div class="p-6">
            <form action="{{ route('barang.keluar.transfer-idm.update', $transfer->id) }}" method="POST" id="editForm">
                @csrf
                @method('PUT')
                
                <div class="mb-6">
                    <div class="max-w-md">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Transfer</label>
                        <input type="date" name="transfer_date" class="w-full h-10 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 text-sm" required value="{{ $transfer->transfer_date }}">
                    </div>
                </div>

                <!-- Hidden Inputs for Items -->
                <div id="hidden-inputs">
                    @foreach($transfer->details as $index => $detail)
                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $detail->idm_detail_id }}" class="item-input" data-row-id="{{ $index }}">
                    @endforeach
                </div>

                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full divide-y divide-gray-200 border" id="itemsTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade IDM</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Supplier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Berat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($transfer->details as $index => $detail)
                                <tr class="item-row" id="row-{{ $index }}" 
                                    data-price="{{ $detail->total_price }}" 
                                    data-name="{{ strtolower($detail->grade_idm_name) }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 loop-index">{{ $index + 1 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $detail->grade_idm_name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $detail->grade_idm_name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $detail->idmDetail->idmManagement->supplier->name ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $detail->weight }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp {{ number_format($detail->total_price, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button type="button" class="text-red-600 hover:text-red-900" onclick="deleteRow({{ $index }})">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end mb-6">
                    <div class="w-full md:w-1/3">
                        <div class="bg-gray-50 rounded-lg p-5 border border-gray-200">
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-sm text-gray-600">Rata Rata Harga IDM</span>
                                <strong class="text-gray-800" id="display-avg-idm">Rp {{ number_format($transfer->average_idm_price, 0, ',', '.') }}</strong>
                                <input type="hidden" name="average_idm_price" id="input-avg-idm" value="{{ $transfer->average_idm_price }}">
                            </div>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-sm text-gray-600">Total Harga Selain IDM</span>
                                <strong class="text-gray-800" id="display-total-non">Rp {{ number_format($transfer->total_non_idm_price, 0, ',', '.') }}</strong>
                                <input type="hidden" name="total_non_idm_price" id="input-total-non" value="{{ $transfer->total_non_idm_price }}">
                            </div>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-sm text-gray-600">Total Harga IDM</span>
                                <strong class="text-gray-800" id="display-total-idm">Rp {{ number_format($transfer->total_idm_price, 0, ',', '.') }}</strong>
                                <input type="hidden" name="total_idm_price" id="input-total-idm" value="{{ $transfer->total_idm_price }}">
                            </div>
                            <hr class="my-3 border-gray-300">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-800">Total Harga</span>
                                <strong class="text-lg text-blue-600" id="display-total-all">Rp {{ number_format($transfer->price_transfer, 0, ',', '.') }}</strong>
                                <input type="hidden" name="total_price" id="input-total-all" value="{{ $transfer->price_transfer }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="{{ route('barang.keluar.transfer-idm.index') }}" class="h-10 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 flex items-center justify-center">Kembali</a>
                    <button type="submit" class="h-10 px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 flex items-center justify-center">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function formatMoney(number) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(number));
    }

    function deleteRow(index) {
        // Remove row
        const row = document.getElementById('row-' + index);
        if (row) row.remove();

        // Remove hidden input
        const input = document.querySelector(`input[name="items[${index}][id]"]`);
        if (input) input.remove();

        // Renumber rows (optional for visuals, but good for UX)
        document.querySelectorAll('.loop-index').forEach((cell, idx) => {
            cell.innerText = idx + 1;
        });

        recalculate();
    }

    function recalculate() {
        let totalIdm = 0;
        let countIdm = 0;
        let totalNonIdm = 0;

        const rows = document.querySelectorAll('.item-row');
        rows.forEach(row => {
            const price = parseFloat(row.dataset.price);
            const name = row.dataset.name;
            
            // Logic mimicking controller: non-idm if perutan/kakian
            // Note: PHP uses stripos, JS uses includes/indexOf
            if (name.includes('perutan') || name.includes('kakian')) {
                totalNonIdm += price;
            } else {
                totalIdm += price;
                countIdm++;
            }
        });

        const avgIdm = countIdm > 0 ? totalIdm / countIdm : 0;
        const totalAll = avgIdm + totalNonIdm;

        // Update displays
        document.getElementById('display-avg-idm').innerText = formatMoney(avgIdm);
        document.getElementById('display-total-non').innerText = formatMoney(totalNonIdm);
        document.getElementById('display-total-idm').innerText = formatMoney(totalIdm);
        document.getElementById('display-total-all').innerText = formatMoney(totalAll);

        // Update inputs
        document.getElementById('input-avg-idm').value = avgIdm;
        document.getElementById('input-total-non').value = totalNonIdm;
        document.getElementById('input-total-idm').value = totalIdm;
        document.getElementById('input-total-all').value = totalAll;
    }
</script>
@endsection
