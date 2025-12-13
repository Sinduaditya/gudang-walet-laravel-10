@extends('layouts.app')

@section('title', 'Tracking Stok')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header Section --}}
            <header class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Tracking Stok</h1>
                <p class="text-gray-600">Ringkasan total stok dan log rinci pergerakan stok.</p>
            </header>

            {{-- Tabs Navigation --}}
            <div class="mb-6">
                <div
                    class="relative bg-white/50 backdrop-blur-md border border-white/30 shadow-lg
                rounded-xl p-1 flex overflow-x-auto">

                    {{-- Animated highlight bar --}}
                    <div id="highlight-bar"
                        class="absolute top-1 bottom-1 rounded-lg bg-blue-500/10 shadow-inner transition-all duration-300">
                    </div>

                    <button onclick="switchTab('ringkasan')" id="tab-ringkasan"
                        class="tab-button relative z-10 px-6 py-3 rounded-lg text-sm font-semibold transition
                   hover:text-blue-600 whitespace-nowrap">
                        Total Stok
                    </button>

                    <button onclick="switchTab('penjualan')" id="tab-penjualan"
                        class="tab-button relative z-10 px-6 py-3 rounded-lg text-sm font-semibold transition
                   hover:text-blue-600 whitespace-nowrap">
                        Riwayat Penjualan
                    </button>

                    <button onclick="switchTab('internal')" id="tab-internal"
                        class="tab-button relative z-10 px-6 py-3 rounded-lg text-sm font-semibold transition
                   hover:text-blue-600 whitespace-nowrap">
                        Transfer Internal
                    </button>

                    <button onclick="switchTab('external')" id="tab-external"
                        class="tab-button relative z-10 px-6 py-3 rounded-lg text-sm font-semibold transition
                   hover:text-blue-600 whitespace-nowrap">
                        Transfer External
                    </button>
                </div>
            </div>


            {{-- Tab Contents --}}
            <div class="tab-content">

                {{-- Tab 1: Total Stok per Grade --}}
                <section id="content-ringkasan" class="tab-panel">
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h2 class="text-lg font-semibold text-gray-900">Total Stok per Grade (Semua Lokasi)</h2>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Grade
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Total Stok (Gram)
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Total Stok (Kg)
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($totalStokPerGrade as $stok)
                                        @if ($stok->total_grams > 0.01)
                                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="font-medium text-gray-900">
                                                        {{ $stok->gradeCompany->name ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                                    <span class="font-semibold text-gray-800">
                                                        {{ number_format($stok->total_grams, 2) }} gr
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                                    <span class="text-xl font-bold text-amber-600">
                                                        {{ number_format($stok->total_grams / 1000, 2) }}
                                                    </span>
                                                    <span class="text-sm text-gray-500 ml-1">kg</span>
                                                </td>
                                            </tr>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                                Belum ada stok yang tercatat.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                {{-- Tab 2: Riwayat Penjualan --}}
                <section id="content-penjualan" class="tab-panel hidden">
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h2 class="text-lg font-semibold text-gray-900">Riwayat Penjualan Langsung (SALE_OUT)</h2>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Tanggal
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Grade
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Lokasi Asal
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Stok Berkurang
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($penjualanTransactions as $tx)
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                {{ \Carbon\Carbon::parse($tx->transaction_date)->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $tx->gradeCompany->name ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                {{ $tx->location->name ?? '-' }}
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-red-600">
                                                {{ number_format($tx->quantity_change_grams, 2) }} gr
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                                Tidak ada data penjualan.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($penjualanTransactions->hasPages())
                            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                                {{ $penjualanTransactions->appends(['tab' => 'penjualan'])->links() }}
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Tab 3: Riwayat Transfer Internal --}}
                <section id="content-internal" class="tab-panel hidden">
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h2 class="text-lg font-semibold text-gray-900">Riwayat Transfer Internal (TRANSFER_IN/OUT)</h2>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Tanggal
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Grade
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Lokasi
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Perubahan Stok
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Ref. ID
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($transferInternalTransactions as $tx)
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                {{ \Carbon\Carbon::parse($tx->transaction_date)->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $tx->gradeCompany->name ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                {{ $tx->location->name ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold">
                                                @if ($tx->quantity_change_grams > 0)
                                                    <span class="text-green-600">
                                                        +{{ number_format($tx->quantity_change_grams, 2) }} gr
                                                    </span>
                                                @else
                                                    <span class="text-red-600">
                                                        {{ number_format($tx->quantity_change_grams, 2) }} gr
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                                #{{ $tx->reference_id }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                                Tidak ada data transfer internal.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($transferInternalTransactions->hasPages())
                            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                                {{ $transferInternalTransactions->appends(['tab' => 'internal'])->links() }}
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Tab 4: Riwayat Transfer External --}}
                <section id="content-external" class="tab-panel hidden">
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h2 class="text-lg font-semibold text-gray-900">Riwayat Transfer External
                                (EXTERNAL_TRANSFER_IN)</h2>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Tanggal
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Grade
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Lokasi Asal (Eksternal)
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Stok Bertambah
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($transferExternalTransactions as $tx)
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                {{ \Carbon\Carbon::parse($tx->transaction_date)->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $tx->gradeCompany->name ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                {{ $tx->stockTransfer->fromLocation->name ?? 'N/A' }}
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-green-600">
                                                +{{ number_format($tx->quantity_change_grams, 2) }} gr
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                                Tidak ada data transfer eksternal.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($transferExternalTransactions->hasPages())
                            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                                {{ $transferExternalTransactions->appends(['tab' => 'external'])->links() }}
                            </div>
                        @endif
                    </div>
                </section>

            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Function to switch tabs
            function switchTab(tabName) {
                // Hide all tab panels
                document.querySelectorAll('.tab-panel').forEach(panel => {
                    panel.classList.add('hidden');
                });

                // Remove active state from all tab buttons
                document.querySelectorAll('.tab-button').forEach(button => {
                    button.classList.remove('border-blue-500', 'text-blue-600');
                    button.classList.add('border-transparent', 'text-gray-500');
                });

                // Show selected tab panel
                document.getElementById('content-' + tabName).classList.remove('hidden');

                // Add active state to selected tab button
                const activeButton = document.getElementById('tab-' + tabName);
                activeButton.classList.remove('border-transparent', 'text-gray-500');
                activeButton.classList.add('border-blue-500', 'text-blue-600');

                // Update URL with tab parameter (optional, for deep linking)
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.pushState({}, '', url);
            }

            // Check URL parameter on page load to show correct tab
            document.addEventListener('DOMContentLoaded', function() {
                const urlParams = new URLSearchParams(window.location.search);
                const activeTab = urlParams.get('tab') || 'ringkasan';
                switchTab(activeTab);
            });

            function moveHighlight(activeButton) {
    const bar = document.getElementById('highlight-bar');
    const rect = activeButton.getBoundingClientRect();
    const containerRect = activeButton.parentElement.getBoundingClientRect();

    bar.style.width = rect.width + 'px';
    bar.style.left = (rect.left - containerRect.left) + 'px';
}

function switchTab(tabName) {
    // Panel logic tetap punya kamu
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
    document.getElementById('content-' + tabName).classList.remove('hidden');

    // Tab button styling
    document.querySelectorAll('.tab-button')
        .forEach(btn => btn.classList.remove('text-blue-600', 'font-bold'));

    const activeButton = document.getElementById('tab-' + tabName);
    activeButton.classList.add('text-blue-600', 'font-bold');

    // Animate highlight bar
    moveHighlight(activeButton);

    // Update URL
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.pushState({}, '', url);
}

// On load
document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab') || 'ringkasan';

    switchTab(activeTab);

    // Delay highlight position to ensure DOM rendered
    setTimeout(() => moveHighlight(document.getElementById('tab-' + activeTab)), 50);
});
        </script>
    @endpush
@endsection
