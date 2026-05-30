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

        {{-- Banner peringatan jika ada item terkunci --}}
        @if(!empty($lockedIds))
        <div class="mb-5 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <div>
                <p class="font-semibold">{{ count($lockedIds) }} grade terkunci karena sudah ada transaksi aktif.</p>
                <p class="mt-0.5 text-amber-700">Untuk mengubah jenis keluar grade tersebut, hapus transaksi (penjualan / transfer) yang terkait terlebih dahulu.</p>
            </div>
        </div>
        @endif

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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($sortingResults as $item)
                                @php $isLocked = in_array($item->id, $lockedIds ?? []); @endphp
                                <tr class="{{ $isLocked ? 'bg-amber-50 hover:bg-amber-50' : 'hover:bg-gray-50' }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $isLocked ? 'text-gray-500' : 'text-gray-900' }}">
                                        {{ $item->gradeCompany->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono {{ $isLocked ? 'text-gray-400' : 'text-gray-700' }}">
                                        {{ number_format($item->weight_grams, 0, ',', '.') }} gr
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono {{ $isLocked ? 'text-gray-400' : 'text-gray-700' }}">
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
                                        @if($isLocked)
                                            {{-- Locked: kirim nilai lama via hidden input, tampilkan select disabled --}}
                                            <input type="hidden" name="outgoing_types[{{ $item->id }}]" value="{{ $item->outgoing_type ?? '' }}">
                                            <select disabled
                                                    class="block w-full rounded-md border-gray-200 bg-gray-100 shadow-sm sm:text-sm py-2 px-3 border text-gray-400 cursor-not-allowed">
                                                <option value="">-- Pilih Jenis Keluar --</option>
                                                <option value="penjualan_langsung" {{ $item->outgoing_type === 'penjualan_langsung' ? 'selected' : '' }}>Penjualan Langsung</option>
                                                <option value="internal" {{ $item->outgoing_type === 'internal' ? 'selected' : '' }}>Internal</option>
                                                <option value="external" {{ $item->outgoing_type === 'external' ? 'selected' : '' }}>External</option>
                                            </select>
                                            <p class="text-[11px] text-amber-600 mt-1.5 font-medium flex items-center gap-1">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                                Terkunci — sudah ada transaksi aktif
                                            </p>
                                        @else
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
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($isLocked)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd"/></svg>
                                                Terkunci
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                                Dapat diubah
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
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
