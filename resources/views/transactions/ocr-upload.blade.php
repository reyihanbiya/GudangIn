@extends('layouts.app')

@section('title', 'Baca nota pembelian | GudangIn')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Pembelian / OCR nota</p>
            <h1>Baca nota pembelian</h1>
            <p class="lead">Unggah foto nota. Hasil pembacaan akan ditampilkan untuk diperiksa sebelum transaksi disimpan.</p>
        </div>
    </section>
    <section class="form-panel ocr-upload-panel">
        @if ($errors->any())
            <div class="notice notice-error">{{ $errors->first() }}</div>
        @endif
        <div class="notice notice-info">
            OCR menggunakan Gemini API, dengan Tesseract sebagai cadangan bila Gemini tidak tersedia.
        </div>
        <form method="POST" action="{{ route('employee.pembelian.ocr.process') }}" enctype="multipart/form-data" class="stack-form">
            @csrf
            <label for="receipt">Foto atau scan nota</label>
            <input id="receipt" name="receipt" type="file" accept="image/jpeg,image/png,image/webp" required>
            <span class="muted">Format JPG, PNG, atau WEBP; ukuran maksimal 10 MB.</span>
            <div class="form-actions">
                <a class="button button-secondary" href="{{ route('employee.pembelian.index') }}">Kembali</a>
                <button class="button button-primary" type="submit">Proses OCR</button>
            </div>
        </form>
    </section>
@endsection
