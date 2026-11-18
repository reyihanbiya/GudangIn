@extends('layouts.app')

@section('title', 'Dashboard '.$role.' | GudangIn')

@section('content')
    <section class="dashboard-heading">
        <p class="eyebrow">Ringkasan {{ $role }}</p>
        <h1>Halo, {{ $name }}.</h1>
        <p class="lead">Berikut gambaran aktivitas GudangIn saat ini.</p>
    </section>
    <section class="stats-grid">
        @foreach ($stats as $stat)
            <article class="stat-card">
                <p>{{ $stat['label'] }}</p>
                <strong>{{ $stat['value'] }}</strong>
                <span>{{ $stat['detail'] }}</span>
                @isset($stat['route'])
                    <a class="text-link stat-link" href="{{ route($stat['route']) }}">Lihat laporan</a>
                @endisset
            </article>
        @endforeach
    </section>
    <section class="next-step">
        <div>
            <p class="eyebrow">Selanjutnya</p>
            <h2>Data dan transaksi toko terhubung.</h2>
            <p>Gunakan menu di samping untuk mengelola persediaan, mencatat penjualan dan pembelian, atau melihat ringkasan laporan sesuai peran Anda.</p>
        </div>
        <span class="next-step-mark">G</span>
    </section>
@endsection
