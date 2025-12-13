@props([
    'title' => 'Riwayat Transaksi',
    'transactions',
    'type' => null, // 'sale', 'internal', 'external'
    'showFilter' => false
])

<div class="bg-white rounded-xl shadow-md border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
                <p class="text-sm text-gray-500 mt-1">Daftar transaksi terbaru</p>
            </div>

            @if($showFilter)
                <form method="GET" class="flex items-center gap-2">
                    <select name="grade_id" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <option value="">Semua Grade</option>
                        {{-- Populate dari controller --}}
                    </select>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                        Filter
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Tanggal
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Grade
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Lokasi
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Berat
                    </th>
                    @if($type === 'internal' || $type === null)
                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Referensi
                    </th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($transactions as $tx)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ \Carbon\Carbon::parse($tx->transaction_date)->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $tx->gradeCompany->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($type === 'external')
                                <div class="flex items-center">
                                    <span class="text-gray-700">{{ $tx->stockTransfer->fromLocation->name ?? '-' }}</span>
                                    <svg class="w-4 h-4 mx-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                    <span class="text-green-700 font-medium">{{ $tx->location->name ?? '-' }}</span>
                                </div>
                            @elseif($type === 'internal')
                                @php
                                    $stockTransfer = $tx->stockTransfer;
                                @endphp
                                @if($stockTransfer && $stockTransfer->fromLocation && $stockTransfer->toLocation)
                                    <div class="flex items-center text-sm">
                                        <span class="text-gray-700">{{ $stockTransfer->fromLocation->name }}</span>
                                        <svg class="w-4 h-4 mx-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                        <span class="text-purple-700 font-medium">{{ $stockTransfer->toLocation->name }}</span>
                                    </div>
                                @else
                                    <span class="text-gray-700">{{ $tx->location->name ?? '-' }}</span>
                                @endif
                            @else
                                <span class="text-gray-700">{{ $tx->location->name ?? '-' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-semibold {{ $type === 'sale' ? 'text-red-600' : ($type === 'external' ? 'text-green-600' : 'text-gray-900') }}">
                                {{ $type === 'external' ? '+' : '' }}{{ number_format(abs($tx->quantity_change_grams), 2) }} gr
                            </div>
                            <div class="text-xs text-gray-500">
                                ({{ number_format(abs($tx->quantity_change_grams) / 1000, 2) }} kg)
                            </div>
                        </td>
                        @if($type === 'internal' || $type === null)
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                            @if($tx->reference_id)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-mono bg-gray-100 text-gray-700">
                                    #{{ $tx->reference_id }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $type === 'internal' || $type === null ? '5' : '4' }}" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                </div>
                                <p class="text-gray-500 font-medium">Belum ada transaksi</p>
                                <p class="text-gray-400 text-sm mt-1">Transaksi akan muncul setelah Anda membuat transaksi baru</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Menampilkan {{ $transactions->firstItem() }} - {{ $transactions->lastItem() }} dari {{ $transactions->total() }} transaksi
                </div>
                <div>
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    @endif
</div>
