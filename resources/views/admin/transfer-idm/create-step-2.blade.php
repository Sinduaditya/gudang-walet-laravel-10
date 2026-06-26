@extends('layouts.app')

@section('title', 'Transfer IDM - Step 2')

@section('content')
    <div class="bg-white min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Konfirmasi Transfer</h1>
                    <p class="mt-1 text-sm text-gray-600">Isi detail transfer dan periksa kembali data sebelum menyimpan.</p>
                </div>
                <a href="{{ route('barang.keluar.transfer-idm.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                    Kembali
                </a>
            </div>

            <!-- Progress Indicator -->
            <div class="mb-8 bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center justify-between max-w-xl mx-auto">
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 flex items-center justify-center rounded-full bg-green-100 text-green-600 font-semibold text-sm">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-green-600">Pilih Barang</span>
                    </div>
                    <div class="flex-1 h-0.5 bg-blue-200 mx-2 sm:mx-4 -mt-6"></div>
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-500 text-white font-semibold text-sm shadow-sm">
                            2
                        </div>
                        <span class="mt-2 text-xs sm:text-sm font-medium text-blue-600">Konfirmasi Transfer</span>
                    </div>
                </div>
            </div>

            @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-300 rounded-lg p-4 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('barang.keluar.transfer-idm.store') }}" method="POST">
                @csrf

                <!-- Hidden item IDs -->
                @foreach($items as $item)
                    <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                @endforeach

                <!-- Transfer Details -->
                <div class="bg-white shadow-sm border rounded-lg p-6 mb-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Detail Transfer</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                        <!-- Tanggal Transfer -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tanggal Transfer <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="transfer_date"
                                value="{{ old('transfer_date', $transfer_date ?? date('Y-m-d')) }}"
                                required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('transfer_date') border-red-400 @enderror">
                            @error('transfer_date')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Lokasi Asal -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Lokasi Asal <span class="text-red-500">*</span>
                            </label>
                            <select name="source_location_id" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('source_location_id') border-red-400 @enderror">
                                <option value="">Pilih Lokasi</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}"
                                        {{ old('source_location_id') == $location->id ? 'selected' : ($location->name === 'Gudang Utama' ? 'selected' : '') }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('source_location_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Catatan -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                            <textarea name="notes" rows="1"
                                placeholder="Opsional..."
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Item List Table -->
                <div class="bg-white shadow-sm border rounded-lg p-6 mb-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">
                        Barang yang akan Ditransfer
                        <span class="ml-2 text-sm font-normal text-gray-500">({{ $items->count() }} item)</span>
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">No</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Jenis</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Grade IDM</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Supplier</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Berat Transfer</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($items as $index => $item)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 capitalize">{{ $item->grade_idm_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">{{ $item->grade_idm_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->idmManagement->supplier->name ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center gap-2">
                                                <input type="number"
                                                    name="items[{{ $loop->index }}][weight]"
                                                    value="{{ $item->remaining_weight ?? $item->weight }}"
                                                    step="0.01"
                                                    min="0.01"
                                                    max="{{ $item->remaining_weight ?? $item->weight }}"
                                                    class="w-28 border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">
                                                <span class="text-gray-400 text-xs">/ {{ number_format($item->remaining_weight ?? $item->weight, 2) }} g</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                        </table>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3">
                    <a href="{{ route('barang.keluar.transfer-idm.create') }}"
                        class="px-6 py-3 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-6 py-3 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        Simpan Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
