@extends('layouts.app')

@section('title', 'Buat akun baru | GudangIn')

@section('content')
    <section class="form-layout">
        <div class="form-intro">
            <p class="eyebrow">Daftar akun</p>
            <h1>Pilih jenis<br>akun baru.</h1>
            <p class="lead">Pilih akses yang sesuai dengan peran Anda di toko.</p>
        </div>
        <div class="form-panel">
            <h2>Buat akun baru</h2>
            <div class="register-role-options">
                <section class="register-role-card">
                    <h3>Pegawai</h3>
                    <p>Daftar akun Pegawai baru untuk mulai menggunakan GudangIn.</p>
                    <a class="button button-primary button-wide" href="{{ route('register.employee') }}">Daftar sebagai Pegawai</a>
                </section>
                <section class="register-role-card">
                    <h3>Owner</h3>
                    @if ($ownerAccountExists)
                        <p>Akun Owner tambahan hanya bisa dibuat oleh Owner yang sudah masuk.</p>
                        <a class="button button-secondary button-wide" href="{{ route('owner.login') }}">Masuk sebagai Owner</a>
                    @else
                        <p>Belum ada akun Owner. Buat akun Owner pertama untuk mengelola akses Owner.</p>
                        <a class="button button-secondary button-wide" href="{{ route('register.owner') }}">Buat akun Owner pertama</a>
                    @endif
                </section>
            </div>
            <a class="text-link back-link" href="{{ route('home') }}">Kembali ke pilihan akses</a>
        </div>
    </section>
@endsection
