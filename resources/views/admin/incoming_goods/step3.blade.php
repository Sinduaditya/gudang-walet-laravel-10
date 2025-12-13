{{-- filepath: d:\Learning\Laravel\gudang_walet\resources\views\admin\incoming_goods\step3.blade.php --}}
@extends('layouts.app')

@section('title', 'Input Barang Masuk - Step 3')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center">
                <div class="flex items-center">
                    <span class="w-10 h-10 flex items-center justify-center rounded-full bg-green-600 text-white">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    <span class="ml-2 text-sm font-medium text-gray-900">Pilih Supplier & Grade</span>
                </div>
                <div class="w-24 h-0.5 bg-green-600 mx-4"></div>
                <div class="flex items-center">
                    <span class="w-10 h-10 flex items-center justify-center rounded-full bg-green-600 text-white">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    <span class="ml-2 text-sm font-medium text-gray-900">Input Berat Nota</span>
                </div>
                <div class="w-24 h-0.5 bg-blue-600 mx-4"></div>
                <div class="flex items-center">
                    <span class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-600 text-white font-medium">3</span>
                    <span class="ml-2 text-sm font-medium text-gray-900">Input Berat Gudang</span>
                </div>
            </div>
        </div>

        <!-- Info Supplier -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-blue-900">Input untuk Supplier:</h3>
                    <p class="text-lg font-semibold text-blue-900">{{ $supplier->name }}</p>
                </div>
                <div class="text-right text-sm text-blue-700">
                    <p>Tanggal Kedatangan: {{ \Carbon\Carbon::parse($step1Data['receipt_date'])->format('d/m/Y') }}</p>
                    <p>Tanggal Bongkar: {{ \Carbon\Carbon::parse($step1Data['unloading_date'])->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>

        <form action="{{ route('incoming-goods.store-final') }}" method="POST">
            @csrf

            <!-- Grade Cards -->
            <div class="bg-white rounded-lg shadow-sm border mb-6">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">Input Berat Timbangan Gudang</h2>
                    <p class="text-sm text-gray-500 mt-1">Masukkan hasil timbangan gudang setelah barang dibongkar</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($grades as $grade)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <!-- Grade Header -->
                            <div class="flex items-center mb-4">
                                @if($grade->image_url)
                                    <img src="{{ $grade->image_url }}" 
                                         alt="{{ $grade->name }}" 
                                         class="w-16 h-16 object-cover rounded-lg mr-3">
                                @else
                                    <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $grade->name }}</h3>
                                </div>
                            </div>

                            <!-- Info dari Step 2 (Read Only) -->
                            <div class="bg-gray-50 rounded-md p-3 mb-4 space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Berat Nota:</span>
                                    <span class="font-medium text-gray-900">{{ number_format($step2Data['berat_awal'][$grade->id] ?? 0) }} gr</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Kadar Air:</span>
                                    <span class="font-medium text-gray-900">{{ $step2Data['kadar_air'][$grade->id] ?? 0 }}%</span>
                                </div>
                            </div>

                            <!-- Timbangan Gudang Input -->
                            <div class="mb-3">
                                <label for="berat_akhir_{{ $grade->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                                    Timbangan Gudang (gram) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" 
                                       name="berat_akhir[{{ $grade->id }}]" 
                                       id="berat_akhir_{{ $grade->id }}"
                                       value="{{ old('berat_akhir.' . $grade->id) }}"
                                       required
                                       min="0"
                                       step="1"
                                       placeholder="Masukkan berat"
                                       onkeyup="calculateDifference({{ $grade->id }}, {{ $step2Data['berat_awal'][$grade->id] ?? 0 }})"
                                       oninput="calculateDifference({{ $grade->id }}, {{ $step2Data['berat_awal'][$grade->id] ?? 0 }})"
                                       class="w-full rounded-md py-2 px-3 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                @error('berat_akhir.' . $grade->id)
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- ✅ Selisih & Persentase Display (Auto Calculate) -->
                            <div id="selisih_{{ $grade->id }}" class="hidden">
                                <!-- Selisih Container -->
                                <div class="p-3 rounded-md bg-gray-50 mb-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="font-medium text-gray-600">Selisih:</span>
                                        <span id="selisih_value_{{ $grade->id }}" class="font-semibold"></span>
                                    </div>
                                </div>
                                
                                <!-- Rasio & Persentase Container -->
                                <div class="grid grid-cols-2 gap-2">
                                    <!-- Rasio Desimal -->
                                    <div class="p-2 rounded-md border border-gray-200 text-center">
                                        <div class="text-xs text-gray-500 mb-1">Rasio Desimal</div>
                                        <div id="decimal_value_{{ $grade->id }}" class="font-bold text-lg font-mono"></div>
                                    </div>
                                    
                                    <!-- Persentase -->
                                    <div class="p-2 rounded-md border border-gray-200 text-center">
                                        <div class="text-xs text-gray-500 mb-1">Persentase</div>
                                        <div id="percentage_value_{{ $grade->id }}" class="font-bold text-lg"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-between">
                <a href="{{ route('incoming-goods.step2') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali
                </a>
                <button type="submit"
                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Penerimaan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function calculateDifference(gradeId, beratAwal) {
    console.log('=== DEBUG CALCULATION ===');
    console.log('Grade ID:', gradeId, 'Berat Awal:', beratAwal);
    
    const beratAkhirInput = document.getElementById('berat_akhir_' + gradeId);
    const selisihDiv = document.getElementById('selisih_' + gradeId);
    const selisihSpan = document.getElementById('selisih_value_' + gradeId);
    const decimalSpan = document.getElementById('decimal_value_' + gradeId);  
    const percentageSpan = document.getElementById('percentage_value_' + gradeId);
    
    if (beratAkhirInput && selisihDiv && selisihSpan && decimalSpan && percentageSpan) {
        const beratAkhir = parseFloat(beratAkhirInput.value) || 0;
        
        console.log('Berat Akhir:', beratAkhir);
        
        // ✅ Skip calculation jika input kosong
        if (beratAkhir === 0) {
            selisihDiv.classList.add('hidden');
            return;
        }
        
        const selisih = beratAkhir - beratAwal; // Berat Akhir - Berat Awal
        
        let percentage = 0;
        let decimal = 0;
        
        if (beratAwal > 0) {
            decimal = selisih / beratAwal; // ✅ Rasio desimal (bisa negatif/positif)
            percentage = Math.abs(decimal) * 100; // ✅ Persentase selalu positif
        }
        
        console.log('Selisih:', selisih);
        console.log('Decimal:', decimal);
        console.log('Percentage:', percentage);
        
        // ✅ Format tampilan selisih dengan status Indonesia
        let selisihText = '';
        let selisihClass = '';
        
        if (selisih < 0) {
            selisihText = number_format_id(Math.abs(selisih)) + ' gr (susut)';
            selisihClass = 'text-red-600';
        } else if (selisih > 0) {
            selisihText = '+' + number_format_id(selisih) + ' gr (kelebihan)';
            selisihClass = 'text-green-600';
        } else {
            selisihText = '0 gr (sama)';
            selisihClass = 'text-gray-600';
        }
        
        // ✅ Format tampilan DESIMAL (3 desimal, bisa negatif/positif)
        let decimalText = '';
        let decimalClass = '';
        
        if (decimal === 0) {
            decimalText = '0,000';
            decimalClass = 'text-gray-600';
        } else {
            const formattedDecimal = decimal.toFixed(3).replace('.', ','); // ✅ Koma sebagai desimal
            
            // ✅ Warning jika rasio > 0.05 (2%)
            if (Math.abs(decimal) > 0.02) {
                decimalClass = 'text-red-600 bg-red-50 px-1 py-0.5 rounded';
                decimalText = formattedDecimal + ' ⚠️';
            } else {
                decimalClass = 'text-green-600';
                decimalText = formattedDecimal;
            }
        }
        
        // ✅ Format tampilan PERSENTASE (bulat atau 1 desimal, selalu positif)
        let percentageText = '';
        let percentageClass = '';
        
        if (percentage === 0) {
            percentageText = '0%';
            percentageClass = 'text-gray-600';
        } else {
            // ✅ Jika bulat, tampilkan tanpa desimal. Jika tidak, tampilkan 1 desimal
            const formattedPercentage = percentage % 1 === 0 
                ? Math.round(percentage).toString()
                : percentage.toFixed(1).replace('.', ','); // ✅ Koma sebagai desimal
            
            // ✅ Warning jika persentase > 5%
            if (percentage > 2) {
                percentageClass = 'text-red-600 bg-red-50 px-1 py-0.5 rounded';
                percentageText = formattedPercentage + '% ⚠️';
            } else {
                percentageClass = 'text-green-600';
                percentageText = formattedPercentage + '%';
            }
        }
        
        // ✅ Update tampilan
        selisihSpan.textContent = selisihText;
        selisihSpan.className = selisihClass + ' font-medium';
        
        decimalSpan.textContent = decimalText;
        decimalSpan.className = decimalClass + ' font-mono';
        
        percentageSpan.textContent = percentageText;
        percentageSpan.className = percentageClass + ' font-medium';
        
        // ✅ Show container
        selisihDiv.classList.remove('hidden');
        
        console.log('Final decimal:', decimalText);
        console.log('Final percentage:', percentageText);
        console.log('=== END DEBUG ===');
    }
}

// ✅ Format angka dengan titik sebagai pemisah ribuan (Indonesia)
function number_format_id(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

// ✅ Auto-calculate saat page load jika ada old values
document.addEventListener('DOMContentLoaded', function() {
    @foreach($grades as $grade)
        const input_{{ $grade->id }} = document.getElementById('berat_akhir_{{ $grade->id }}');
        if (input_{{ $grade->id }} && input_{{ $grade->id }}.value) {
            calculateDifference({{ $grade->id }}, {{ $step2Data['berat_awal'][$grade->id] ?? 0 }});
        }
    @endforeach
});
</script>
@endpush
@endsection