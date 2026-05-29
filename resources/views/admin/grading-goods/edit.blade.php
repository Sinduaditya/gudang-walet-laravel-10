@extends('layouts.app')

@section('title', 'Edit Jenis Barang Keluar Grading')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Jenis Barang Keluar</h1>
                <p class="mt-1 text-sm text-gray-600">Supplier: <span class="font-semibold text-gray-800">{{ $grading->receiptItem->purchaseReceipt->supplier->name ?? '-' }}</span></p>
            </div>
            <a href="{{ route('grading-goods.index') }}"
                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                &larr; Kembali
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <form action="{{ route('grading-goods.update', ['receiptItemId' => $receiptItemId]) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Berat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">Jenis Barang Keluar</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($sortingResults as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $item->gradeCompany->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">
                                        {{ number_format($item->weight_grams, 0, ',', '.') }} gr
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">
                                        {{ number_format($item->quantity, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm" id="category-badge-{{ $item->id }}">
                                        @if($item->category_grade)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                {{ $item->category_grade }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <select name="outgoing_types[{{ $item->id }}]" 
                                                onchange="checkCategoryMutualExclusivity({{ $item->id }}, this, '{{ $item->category_grade ?? '' }}')" 
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                                            <option value="">-- Pilih Jenis Keluar --</option>
                                            <option value="penjualan_langsung" {{ $item->outgoing_type === 'penjualan_langsung' ? 'selected' : '' }}>Penjualan Langsung</option>
                                            <option value="internal" {{ $item->outgoing_type === 'internal' ? 'selected' : '' }}>Internal</option>
                                            <option value="external" {{ $item->outgoing_type === 'external' ? 'selected' : '' }}>External</option>
                                        </select>
                                        
                                        @if($item->category_grade)
                                            <p class="text-[11px] text-orange-600 mt-1.5 font-medium italic transition-colors" id="warning-mut-excl-{{ $item->id }}">
                                                * Memilih jenis keluar akan menghapus kategori {{ $item->category_grade }}
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        Data grade tidak ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button type="submit" 
                        class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function checkCategoryMutualExclusivity(itemId, selectEl, originalCategoryGrade) {
        const warningMsg = document.getElementById(`warning-mut-excl-${itemId}`);
        const badgeContainer = document.getElementById(`category-badge-${itemId}`);
        
        if (selectEl.value !== "") {
            if (badgeContainer) {
                badgeContainer.innerHTML = '<span class="text-gray-400 text-xs line-through italic">- (akan dihapus)</span>';
            }
            if (warningMsg) {
                warningMsg.classList.add('text-red-600', 'font-bold');
                warningMsg.classList.remove('text-orange-600');
                warningMsg.innerText = `* Kategori ${originalCategoryGrade} akan dihapus setelah disimpan`;
            }
        } else {
            if (badgeContainer) {
                if (originalCategoryGrade) {
                    badgeContainer.innerHTML = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">${originalCategoryGrade}</span>`;
                } else {
                    badgeContainer.innerHTML = '<span class="text-gray-400 text-xs">-</span>';
                }
            }
            if (warningMsg) {
                warningMsg.classList.remove('text-red-600', 'font-bold');
                warningMsg.classList.add('text-orange-600');
                warningMsg.innerText = `* Memilih jenis keluar akan menghapus kategori ${originalCategoryGrade}`;
            }
        }
    }
</script>
@endpush
@endsection
