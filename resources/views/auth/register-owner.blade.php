@extends('layouts.app')

@section('title', 'Buat akun Owner | GudangIn')

@section('content')
    <section class="form-layout">
        <div class="form-intro">
            <p class="eyebrow">Akun Owner pertama</p>
            <h1>Siapkan akses<br>pemilik toko.</h1>
            <p class="lead">Akun Owner pertama hanya dapat dibuat sebelum akun Owner lain tersedia.</p>
        </div>
        <div class="form-panel">
            <h2>Buat akun Owner pertama</h2>
            @if ($errors->any())
                <div class="notice notice-error">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('register.owner.store') }}" class="stack-form">
                @csrf
                <label for="nama_owner">Nama Owner</label>
                <input id="nama_owner" name="nama_owner" value="{{ old('nama_owner') }}" maxlength="100" autocomplete="name" required autofocus>

                <label for="username">Username</label>
                <input id="username" name="username" value="{{ old('username') }}" maxlength="50" autocomplete="username" required>

                <label for="password">Password</label>
                <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>

                <label for="password_confirmation">Ulangi password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>

                <button class="button button-primary button-wide" type="submit">Buat akun Owner</button>
            </form>
            <a class="text-link back-link" href="{{ route('register') }}">Kembali memilih jenis akun</a>
        </div>
    </section>
@endsection
