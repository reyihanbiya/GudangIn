@extends('customer.layouts.app')

@section('title', 'Lacak Pesanan')

@section('content')
<section class="customer-flow">
    <a class="back-link" href="{{ route('customer.products.index') }}">← Kembali ke katalog</a>
    <div class="section-heading">
        <div>
            <span class="eyebrow">PELACAKAN PESANAN</span>
            <h2>Lacak pesanan<span>.</span></h2>
            <p>Masukkan kode pesanan atau ID pengiriman dan nomor WhatsApp yang digunakan saat checkout.</p>
        </div>
    </div>
    @if ($errors->any())
        <div class="customer-alert error">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('customer.tracking.lookup') }}" class="customer-form">
        @csrf
        <label>Kode pesanan / ID pengiriman
            <input name="kode_pesanan" value="{{ old('kode_pesanan') }}" maxlength="30" autocomplete="off" required>
        </label>
        <label>Nomor WhatsApp
            <input name="no_hp" type="tel" maxlength="20" autocomplete="tel" required>
        </label>
        <button class="hero-button" type="submit">Lacak pesanan <span>→</span></button>
    </form>
</section>
@endsection
