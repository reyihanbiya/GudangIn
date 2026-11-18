@extends('layouts.app')

@section('title', $title.' | GudangIn')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Transaksi / Rincian</p>
            <h1>{{ $title }}</h1>
            <p class="lead">{{ $transactionId }} · {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}</p>
        </div>
        <a class="button button-secondary" href="{{ $backRoute }}">Kembali</a>
    </section>

    @if (session('status'))
        <div class="notice notice-success page-notice">{{ session('status') }}</div>
    @endif
    <section class="transaction-summary">
        <div><span>{{ $type === 'Pembelian' ? 'Supplier' : 'Pelanggan' }}</span><strong>{{ $counterpart }}</strong></div>
        <div><span>Dicatat oleh</span><strong>{{ $employee }}</strong></div>
        @if ($status)
            <div><span>Status pengiriman</span><strong>{{ $status }}</strong></div>
        @endif
    </section>
    @if (isset($receipt) && $receipt)
        <section class="receipt-panel">
            <div class="receipt-heading">
                <div>
                    <p class="eyebrow">Nota pembelian</p>
                    <h2>Nota sudah diperiksa</h2>
                </div>
                <a class="button button-secondary" href="{{ route('employee.pembelian.receipt.download', $transactionId) }}">Unduh gambar</a>
            </div>
            <details>
                <summary>Lihat hasil OCR yang disimpan</summary>
                <pre>{{ $receipt->hasil_ocr }}</pre>
            </details>
        </section>
    @endif
    <section class="table-panel">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Barang</th><th>Jumlah</th><th>Harga satuan</th><th>Subtotal</th></tr></thead>
                <tbody>
                    @foreach ($lines as $line)
                        @php($lineSubtotal = $line->subtotal ?? ((float) $line->{$priceField} * (int) $line->jumlah_barang))
                        <tr>
                            <td>{{ $line->persediaan->nama_barang }}</td>
                            <td>{{ $line->jumlah_barang }}</td>
                            <td>Rp {{ number_format((float) $line->{$priceField}, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $lineSubtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="3">Total</td>
                        <td>Rp {{ number_format((float) $total, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
@endsection
