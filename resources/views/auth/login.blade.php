@extends('layouts.app')

@section('title', 'Masuk sebagai '.$role.' | GudangIn')

@section('content')
    <section class="form-layout">
        <div class="form-intro">
            <p class="eyebrow">Akses {{ strtolower($role) }}</p>
            <h1>Selamat datang<br>kembali.</h1>
            <p class="lead">Masuk untuk melanjutkan aktivitas Anda di GudangIn.</p>
        </div>
        <div class="form-panel">
            <h2>Masuk sebagai {{ $role }}</h2>
            @if (session('status'))
                <div class="notice notice-success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="notice notice-error">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ $action }}" class="stack-form">
                @csrf
                <label for="username">Username</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required autofocus>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <label class="checkbox-row"><input type="checkbox" name="remember" value="1"> Ingat saya di perangkat ini</label>
                <button class="button button-primary button-wide" type="submit">Masuk</button>
            </form>
            <a class="text-link back-link" href="{{ $portalRoute }}">Kembali ke pilihan akses</a>
        </div>
    </section>
@endsection
