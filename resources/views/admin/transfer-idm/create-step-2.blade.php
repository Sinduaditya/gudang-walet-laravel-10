@extends('layouts.app')

@section('title', 'Transfer IDM - Step 2')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Konfirmasi Transfer</h1>
                    <p class="mt-1 text-sm text-gray-600">Periksa kembali data sebelum menyimpan.</p>
                </div>
                <a href="{{ route('barang.keluar.transfer-idm.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                    Kembali
                </a>
            </div>

            <!-- Progress Indicator matching Grading Goods -->
            <div class="mb-8 bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center justify-between max-w-xl mx-auto">
                    <div class="flex flex-col items-center flex-1">
                        <div
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-green-100 text-green-600 font-semibold text-sm">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-green-600">Pilih Barang</span>
                    </div>
                    <div class="flex-1 h-0.5 bg-blue-200 mx-2 sm:mx-4 -mt-6"></div>
                    <div class="flex flex-col items-center flex-1">
                        <div
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-500 text-white font-semibold text-sm shadow-sm">
                            2</div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-blue-600">Konfirmasi Transfer</span>
                    </div>
                </div>
            </div>

            <form action="{{ route('barang.keluar.transfer-idm.store') }}" method="POST">
                @csrf
                <input type="hidden" name="transfer_date" value="{{ request('transfer_date') }}">
                <input type="hidden" name="source_location_id" value="{{ $source_location_id }}">
                 @foreach($items as $item)
                    <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                @endforeach

                <!-- Item List Table -->
                <div class="bg-white shadow-sm border rounded-lg p-6 mb-6">
                     <h3 class="text-lg font-medium text-gray-900 mb-4">Data yang akan di Transfer</h3>
                     <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">No</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Barang</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Grade IDM</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Supplier</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Berat</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Harga</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($items as $index => $item)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item->grade_idm_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->grade_idm_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->idmManagement->supplier->name ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->weight }} g</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary Section -->
                <div class="flex justify-end">
                    <div class="w-full md:w-1/3">
                        <div class="bg-white shadow-sm border rounded-lg p-6">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">Ringkasan Harga</h4>

                            <dl class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <dt class="text-sm text-gray-600">Total Harga IDM</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ number_format($idmOnlyItems->sum('total_price'), 0, ',', '.') }}</dd>
                                    <input type="hidden" name="total_idm_price" value="{{ $idmOnlyItems->sum('total_price') }}">
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <dt class="text-sm text-gray-600">Rata Rata Harga IDM</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ number_format($averageIdmPrice, 0, ',', '.') }}</dd>
                                    <input type="hidden" name="average_idm_price" value="{{ $averageIdmPrice }}">
                                </div>

                                <div class="flex justify-between items-center">
                                    <dt class="text-sm text-gray-600">Total Harga Selain IDM</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ number_format($totalNonIdmPrice, 0, ',', '.') }}</dd>
                                    <input type="hidden" name="total_non_idm_price" value="{{ $totalNonIdmPrice }}">
                                </div>

                                <div class="pt-3 border-t border-gray-200 flex justify-between items-center">
                                    <dt class="text-base font-bold text-gray-900">Total Harga</dt>
                                    <dd class="text-xl font-bold text-blue-600">{{ number_format($averageIdmPrice + $totalNonIdmPrice, 0, ',', '.') }}</dd>
                                    <input type="hidden" name="total_price" value="{{ $averageIdmPrice + $totalNonIdmPrice }}">
                                </div>
                            </dl>

                            <div class="mt-6">
                                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-3 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    Simpan Transfer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
