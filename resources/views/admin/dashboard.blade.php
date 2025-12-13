@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard Admin</h1>
        <p class="text-gray-600 mt-2">Overview statistik gudang walet - {{ Carbon\Carbon::now()->format('d F Y') }}</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Barang Masuk Hari Ini</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($statisticsCards['barang_masuk_hari_ini'], 1) }} kg</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Barang Keluar Hari Ini</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($statisticsCards['barang_keluar_hari_ini'], 1) }} kg</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Barang di Grading Hari Ini</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($statisticsCards['barang_di_grading'], 1) }} kg</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Supplier Aktif</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $statisticsCards['total_supplier_aktif'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">30 hari terakhir</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Flow Masuk & Keluar -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Flow Masuk & Keluar (7 Hari Terakhir)</h3>
            <div class="h-64">
                <canvas id="flowChart"></canvas>
            </div>
        </div>

        <!-- Flow ke DMK -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Barang Dikirim ke DMK (7 Hari Terakhir)</h3>
            <div class="h-64">
                <canvas id="dmkChart"></canvas>
            </div>
        </div>

        <!-- Jasa Cuci -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Barang ke Jasa Cuci (Bulan Ini)</h3>
            <div class="h-64">
                <canvas id="jasaCuciChart"></canvas>
            </div>
            @if(empty($jasaCuci['labels']))
                <div class="text-center text-gray-500 mt-4">
                    <div class="flex flex-col items-center">
                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm font-medium">Belum ada pengiriman ke jasa cuci</p>
                        <p class="text-xs mt-1">Data akan muncul setelah ada transaksi penjualan ke lokasi jasa cuci</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Supplier -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Barang Masuk per Supplier (Bulan Ini)</h3>
            <div class="h-64">
                <canvas id="supplierChart"></canvas>
            </div>
        </div>

        <!-- Grading -->
        <div class="bg-white rounded-lg shadow-sm p-6 lg:col-span-2">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Total Barang di Grading (7 Hari Terakhir)</h3>
            <div class="h-64">
                <canvas id="gradingChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Stock Summary & Recent Activities -->
    {{-- <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Current Stock Summary -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Ringkasan Stok Saat Ini</h3>
            </div>
            <div class="max-h-96 overflow-y-auto">
                @forelse($stockSummary as $stock)
                    <div class="p-4 border-b border-gray-100 last:border-b-0">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-medium text-gray-900">{{ $stock['grade'] }}</p>
                                <p class="text-sm text-gray-500">{{ $stock['location'] }}</p>
                            </div>
                            <span class="text-lg font-bold text-blue-600">{{ number_format($stock['stock_kg'], 1) }} kg</span>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500">
                        <p class="text-sm">Belum ada stok tersedia</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Aktivitas Terbaru</h3>
            </div>
            
            <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                @forelse($recentActivities as $activity)
                    <div class="p-4 flex items-start space-x-3">
                        <div class="w-8 h-8 bg-{{ $activity['color'] }}-100 rounded-full flex items-center justify-center flex-shrink-0">
                            @if($activity['icon'] === 'plus')
                                <svg class="w-4 h-4 text-{{ $activity['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            @elseif($activity['icon'] === 'grid')
                                <svg class="w-4 h-4 text-{{ $activity['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m-6-4v-4m-2 2h4m14-4v4m-2-2h4m-6 11v4m-2-2h4m-6-4v-4m-2 2h4"></path>
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-{{ $activity['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16l4-4m0 0l-4-4m4 4H3"></path>
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900">{!! $activity['message'] !!}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $activity['time'] }}</p>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500">
                        <p class="text-sm">Belum ada aktivitas terbaru</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div> --}}
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Flow Chart - Barang Masuk & Keluar (Data Real)
    const flowCtx = document.getElementById('flowChart').getContext('2d');
    new Chart(flowCtx, {
        type: 'bar',
        data: {
            labels: @json($flowBarang['labels']),
            datasets: [{
                label: 'Barang Masuk (kg)',
                data: @json($flowBarang['masuk']),
                backgroundColor: 'rgba(59, 130, 246, 0.8)'
            }, {
                label: 'Barang Keluar (kg)', 
                data: @json($flowBarang['keluar']),
                backgroundColor: 'rgba(239, 68, 68, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Berat (kg)'
                    }
                }
            }
        }
    });

    // DMK Chart - Line Chart (Data Real)
    const dmkCtx = document.getElementById('dmkChart').getContext('2d');
    new Chart(dmkCtx, {
        type: 'line',
        data: {
            labels: @json($flowDMK['labels']),
            datasets: [{
                label: 'Dikirim ke DMK (kg)',
                data: @json($flowDMK['data']),
                borderColor: 'rgba(34, 197, 94, 1)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Berat (kg)'
                    }
                }
            }
        }
    });

     const jasaCuciCtx = document.getElementById('jasaCuciChart').getContext('2d');
    @if(!empty($jasaCuci['labels']) && count($jasaCuci['labels']) > 0)
        new Chart(jasaCuciCtx, {
            type: 'doughnut',
            data: {
                labels: @json($jasaCuci['labels']),
                datasets: [{
                    data: @json($jasaCuci['data']),
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',   // Blue
                        'rgba(34, 197, 94, 0.8)',    // Green
                        'rgba(251, 191, 36, 0.8)',   // Yellow
                        'rgba(239, 68, 68, 0.8)',    // Red
                        'rgba(168, 85, 247, 0.8)',   // Purple
                        'rgba(236, 72, 153, 0.8)',   // Pink
                        'rgba(99, 102, 241, 0.8)',   // Indigo
                        'rgba(20, 184, 166, 0.8)',   // Teal
                        'rgba(249, 115, 22, 0.8)',   // Orange
                        'rgba(139, 92, 246, 0.8)',   // Violet
                        'rgba(107, 114, 128, 0.8)',  // Gray
                        'rgba(132, 204, 22, 0.8)',   // Lime
                        'rgba(6, 182, 212, 0.8)',    // Cyan
                        'rgba(217, 70, 239, 0.8)',   // Fuchsia
                        'rgba(244, 63, 94, 0.8)'     // Rose
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + ' kg';
                            }
                        }
                    }
                }
            }
        });
    @else
        // Show better empty state
        const ctx = jasaCuciCtx;
        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
        ctx.font = "14px Arial";
        ctx.fillStyle = "#9CA3AF";
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.fillText("Tidak ada data transaksi", ctx.canvas.width/2, ctx.canvas.height/2 - 10);
        ctx.font = "12px Arial";
        ctx.fillText("ke jasa cuci bulan ini", ctx.canvas.width/2, ctx.canvas.height/2 + 10);
    @endif

    // Supplier Chart - Horizontal Bar (Data Real)
    const supplierCtx = document.getElementById('supplierChart').getContext('2d');
    new Chart(supplierCtx, {
        type: 'bar',
        data: {
            labels: @json($supplierData['labels']),
            datasets: [{
                label: 'Barang Masuk (kg)',
                data: @json($supplierData['data']),
                backgroundColor: 'rgba(168, 85, 247, 0.8)'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Berat (kg)'
                    }
                }
            }
        }
    });

    // Grading Chart - Area Chart (Data Real)
    const gradingCtx = document.getElementById('gradingChart').getContext('2d');
    new Chart(gradingCtx, {
        type: 'line',
        data: {
            labels: @json($gradingData['labels']),
            datasets: [{
                label: 'Total Grading (kg)',
                data: @json($gradingData['data']),
                borderColor: 'rgba(16, 185, 129, 1)',
                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Berat (kg)'
                    }
                }
            }
        }
    });
});
</script>
@endsection