@extends('layouts.app')

@section('title', 'Detail Transfer IDM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h4 class="text-xl font-semibold text-gray-800">Detail Transfer IDM</h4>
            <a href="{{ route('barang.keluar.transfer-idm.index') }}" class="h-10 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 flex items-center justify-center">Kembali</a>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kode Transfer</label>
                    <input type="text" class="w-full rounded-md border-gray-300 bg-gray-50 shadow-sm sm:text-sm" value="{{ $transfer->transfer_code }}" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Transfer</label>
                    <input type="text" class="w-full rounded-md border-gray-300 bg-gray-50 shadow-sm sm:text-sm" value="{{ $transfer->transfer_date }}" readonly>
                </div>
            </div>

            <div class="overflow-x-auto mb-6">
                <table class="min-w-full divide-y divide-gray-200 border">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade IDM</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Supplier</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Berat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($transfer->details as $index => $detail)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $detail->grade_idm_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $detail->grade_idm_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $detail->idmDetail->idmManagement->supplier->name ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $detail->weight }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($detail->total_price, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <div class="w-full md:w-1/3">
                    <div class="bg-gray-50 rounded-lg p-5 border border-gray-200">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-sm text-gray-600">Rata Rata Harga IDM</span>
                            <strong class="text-gray-800">{{ number_format($transfer->average_idm_price, 0, ',', '.') }}</strong>
                        </div>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-sm text-gray-600">Total Harga Selain IDM</span>
                            <strong class="text-gray-800">{{ number_format($transfer->total_non_idm_price, 0, ',', '.') }}</strong>
                        </div>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-sm text-gray-600">Total Harga IDM</span>
                            <strong class="text-gray-800">{{ number_format($transfer->total_idm_price, 0, ',', '.') }}</strong>
                        </div>
                        <hr class="my-3 border-gray-300">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-gray-800">Total Harga</span>
                            <strong class="text-lg text-blue-600">{{ number_format($transfer->price_transfer, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
