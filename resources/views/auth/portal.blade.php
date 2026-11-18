@extends('layouts.app')

@section('title', 'Selamat datang | GudangIn')

@section('content')
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow">Sistem operasional toko bangunan</p>
            <h1>Semua urusan toko,<br><span>dalam satu tempat.</span></h1>
            <p class="lead">Kelola persediaan dan aktivitas toko dengan lebih rapi bersama GudangIn.</p>
        </div>
        <div class="login-panel">
            <p class="eyebrow">Masuk ke GudangIn</p>
            <h2>Pilih akses Anda</h2>
            <p class="muted">Gunakan akun sesuai peran Anda di toko.</p>
            <a class="button button-primary button-wide" href="{{ route('login') }}">Masuk sebagai Pegawai</a>
            <a class="button button-secondary button-wide" href="{{ route('owner.login') }}">Masuk sebagai Owner</a>
            @if ($setupAvailable)
                <p class="setup-hint">Pertama kali menggunakan GudangIn? <a href="{{ route('setup') }}">Buat akun awal</a></p>
            @endif
        </div>
    </section>
    <section class="feature-strip" aria-label="Fitur GudangIn">
        <article><span class="feature-icon">01</span><div><h3>Persediaan terpantau</h3><p>Ketahui jumlah barang dan pergerakan stok.</p></div></article>
        <article><span class="feature-icon">02</span><div><h3>Transaksi tercatat</h3><p>Catat penjualan dan pembelian toko.</p></div></article>
        <article><span class="feature-icon">03</span><div><h3>Laporan lebih jelas</h3><p>Pantau ringkasan kegiatan usaha.</p></div></article>
    </section>
@endsection
