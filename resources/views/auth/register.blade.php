@extends('layouts.app')

@section('title', 'Buat akun Pegawai | GudangIn')

@section('content')
    <section class="form-layout">
        <div class="form-intro">
            <p class="eyebrow">Daftar Pegawai</p>
            <h1>Buat akun<br>GudangIn.</h1>
            <p class="lead">Buat akun Pegawai untuk masuk dan membantu operasional toko.</p>
        </div>
        <div class="form-panel">
            <h2>Buat akun Pegawai</h2>
            @if ($errors->any())
                <div class="notice notice-error">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('register.employee.store') }}" class="stack-form">
                @csrf
                <label for="nama_pegawai">Nama Pegawai</label>
                <input id="nama_pegawai" name="nama_pegawai" value="{{ old('nama_pegawai') }}" maxlength="100" autocomplete="name" required autofocus>

                <label for="username">Username</label>
                <input id="username" name="username" value="{{ old('username') }}" maxlength="50" autocomplete="username" required>

                <label for="password">Password</label>
                <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>

                <label for="password_confirmation">Ulangi password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>

                <button class="button button-primary button-wide" type="submit">Buat akun</button>
            </form>
            <p class="setup-hint">Akun Owner hanya bisa ditambahkan oleh Owner yang sudah masuk.</p>
            <a class="text-link back-link" href="{{ route('register') }}">Ganti jenis akun</a>
            <a class="text-link back-link" href="{{ route('login') }}">Sudah punya akun? Masuk</a>
        </div>
    </section>
@endsection
