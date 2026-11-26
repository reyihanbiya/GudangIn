@extends('layouts.app')

@section('title', 'Dashboard '.$role.' | GudangIn')

@section('content')
    <section class="dashboard-heading">
        <p class="eyebrow">Ringkasan {{ $role }} · {{ now()->translatedFormat('d F Y') }}</p>
        <h1>Halo, {{ $name }}.</h1>
        <p class="lead">Pantau penjualan, pembelian, dan perkiraan kebutuhan stok toko.</p>
    </section>
    <section class="stats-grid dashboard-stats">
        @foreach ($stats as $stat)
            <article class="stat-card">
                <div class="stat-card-heading">
                    <p>{{ $stat['label'] }}</p>
                    <span aria-hidden="true">{{ $stat['icon'] }}</span>
                </div>
                <strong>{{ $stat['value'] }}</strong>
                <span>{{ $stat['detail'] }}</span>
                @if (! empty($stat['route']))
                    <a class="text-link stat-link" href="{{ route($stat['route']) }}">Lihat laporan</a>
                @endif
            </article>
        @endforeach
    </section>

    <section class="dashboard-panels">
        <article class="dashboard-panel sales-trend-panel">
            <header class="dashboard-panel-heading">
                <div>
                    <h2>Tren penjualan</h2>
                    <p>Pergerakan penjualan 7 hari terakhir</p>
                </div>
                <span class="dashboard-badge">7 hari terakhir</span>
            </header>
            <strong class="sales-trend-total">Rp{{ number_format($sevenDaySalesTotal, 0, ',', '.') }}</strong>
            <div class="sales-chart" role="img" aria-label="Grafik penjualan 7 hari terakhir">
                @foreach ($salesTrend as $day)
                    <div class="sales-chart-column" title="{{ $day['date'] }}: Rp{{ number_format($day['total'], 0, ',', '.') }}">
                        <span class="sales-chart-value">
                            {{ $day['value_label'] }}
                        </span>
                        <div class="sales-chart-track">
                            <span class="sales-chart-bar" style="height: {{ $day['bar_height'] }}%"></span>
                        </div>
                        <span class="sales-chart-day">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="dashboard-panel low-stock-panel">
            <header class="dashboard-panel-heading">
                <div>
                    <h2>Stok menipis</h2>
                    <p>Perkiraan habis dalam {{ $stockForecastDays }} hari</p>
                </div>
                <span class="dashboard-badge dashboard-badge-warning">{{ $lowStockCount }} barang</span>
            </header>
            @if ($lowStockItems->isNotEmpty())
                <ul class="low-stock-list">
                    @foreach ($lowStockItems as $item)
                        <li>
                            <div>
                                <strong>{{ $item['name'] }}</strong>
                                <span>{{ $item['forecast'] }}</span>
                            </div>
                            <strong class="low-stock-quantity">{{ $item['stock'] }}</strong>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="low-stock-empty">Belum ada barang yang diperkirakan habis dalam {{ $stockForecastDays }} hari.</p>
            @endif
            <p class="dashboard-footnote">Prediksi memakai penjualan 7 hari terakhir dan hanya menghitung transaksi dalam satuan stok saat ini.</p>
        </article>
    </section>
@endsection
