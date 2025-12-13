@extends('layouts.app')

@section('title', 'Edit Penjualan')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Edit Penjualan</h1>
                <p class="mt-1 text-sm text-gray-600">Perbarui data penjualan</p>
            </div>

            <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                <form action="{{ route('barang.keluar.sell.update', $tx->id) }}" method="POST" class="p-6">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        {{-- Grade Display (Read Only) --}}
                        <div>
                            <label class="block font-semibold text-gray-700 mb-2">
                                Grade
                            </label>
                            <input type="text" value="{{ $tx->gradeCompany->name ?? '-' }}" disabled
                                class="w-full border border-gray-300 rounded-lg p-3 bg-gray-100 text-gray-500">
                            <p class="mt-1 text-xs text-gray-500">Grade tidak dapat diubah saat edit.</p>
                        </div>

                        {{-- Location Display (Read Only) --}}
                        <div>
                            <label class="block font-semibold text-gray-700 mb-2">
                                Lokasi
                            </label>
                            <input type="text" value="{{ $tx->location->name ?? '-' }}" disabled
                                class="w-full border border-gray-300 rounded-lg p-3 bg-gray-100 text-gray-500">
                        </div>

                        {{-- Weight --}}
                        <div>
                            <label class="block font-semibold text-gray-700 mb-2">
                                Berat Penjualan (gram) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="weight_grams" step="0.01" min="0.01" required
                                value="{{ old('weight_grams', abs($tx->quantity_change_grams)) }}"
                                class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        {{-- Date --}}
                        <div>
                            <label class="block font-semibold text-gray-700 mb-2">Tanggal Penjualan</label>
                            <input type="date" name="transaction_date" disabled
                                value="{{ \Carbon\Carbon::parse($tx->transaction_date)->format('Y-m-d') }}"
                                class="w-full border border-gray-300 rounded-lg p-3 bg-gray-100 text-gray-500">
                             <p class="mt-1 text-xs text-gray-500">Tanggal tidak dapat diubah saat edit.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-6 border-t border-gray-200 mt-6">
                        <a href="{{ route('barang.keluar.sell.form') }}"
                            class="flex-1 inline-flex items-center justify-center px-4 py-3 border-2 border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-all">
                            Batal
                        </a>
                        <button type="submit"
                            class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-indigo-700 focus:ring-4 focus:ring-blue-300 transition-all shadow-lg">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
