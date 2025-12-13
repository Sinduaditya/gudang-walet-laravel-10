@extends('layouts.app')

@section('title', 'Edit Transfer External')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-green-50 to-emerald-50 py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Edit Transfer External</h1>
                <p class="mt-1 text-sm text-gray-600">Perbarui data transfer ke Jasa Cuci</p>
            </div>

            <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                <form action="{{ route('barang.keluar.external-transfer.update', $transfer->id) }}" method="POST" class="p-6">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        {{-- Grade Selection --}}
                        <div>
                            <label class="block font-semibold text-gray-700 mb-2">
                                Grade <span class="text-red-500">*</span>
                            </label>
                            <select name="grade_company_id" id="grade_company_id" required
                                class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                @foreach($grades as $grade)
                                    <option value="{{ $grade->id }}" {{ $transfer->grade_company_id == $grade->id ? 'selected' : '' }}>
                                        {{ $grade->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-sm text-gray-500">
                                Stok tersedia di Gudang Utama (termasuk transaksi ini): <span class="font-semibold text-green-600">{{ number_format($availableStock, 2) }} gr</span>
                            </p>
                        </div>

                        {{-- Location Selection --}}
                        <div>
                            <label class="block font-semibold text-gray-700 mb-2">
                                Ke Lokasi (Jasa Cuci) <span class="text-red-500">*</span>
                            </label>
                            <select name="to_location_id" id="to_location_id" required
                                class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                @foreach($jasaCuciLocations as $loc)
                                    <option value="{{ $loc->id }}" {{ $transfer->to_location_id == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Weight & Shrinkage --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block font-semibold text-gray-700 mb-2">
                                    Berat Transfer (gram) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="weight_grams" step="0.01" min="0.01" required
                                    value="{{ old('weight_grams', $transfer->weight_grams) }}"
                                    class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 mb-2">
                                    Susut (gram) <span class="text-gray-400 font-normal text-xs">(Opsional)</span>
                                </label>
                                <input type="number" name="susut_grams" step="0.01" min="0"
                                    value="{{ old('susut_grams', $transfer->susut_grams) }}"
                                    class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                        </div>

                        {{-- Date --}}
                        <div>
                            <label class="block font-semibold text-gray-700 mb-2">Tanggal Transfer</label>
                            <input type="date" name="transfer_date"
                                value="{{ old('transfer_date', $transfer->transfer_date) }}"
                                class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label class="block font-semibold text-gray-700 mb-2">Catatan</label>
                            <textarea name="notes" rows="3"
                                class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none">{{ old('notes', $transfer->notes) }}</textarea>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-6 border-t border-gray-200 mt-6">
                        <a href="{{ route('barang.keluar.external-transfer.step1') }}"
                            class="flex-1 inline-flex items-center justify-center px-4 py-3 border-2 border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-all">
                            Batal
                        </a>
                        <button type="submit"
                            class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-600 to-teal-600 text-white font-semibold rounded-lg hover:from-green-700 hover:to-teal-700 focus:ring-4 focus:ring-green-300 transition-all shadow-lg">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
