@extends('layouts.app')

@section('title', 'Database belum siap | GudangIn')

@section('content')
    <section class="message-panel">
        <p class="eyebrow">Perlu penyiapan database</p>
        <h1>Skema GudangIn belum lengkap.</h1>
        <p>Jalankan migrasi untuk membuat tabel pendukung, dan pastikan skema database GudangIn sudah diimpor.</p>
        <div class="code-box">php artisan migrate</div>
        <p class="muted">Tabel yang belum tersedia: {{ implode(', ', $missingTables) }}</p>
    </section>
@endsection
