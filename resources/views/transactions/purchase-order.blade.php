@extends('layouts.app')

@section('title', 'Purchase order '.$pembelian->id_pembelian.' | GudangIn')

@section('content')
    <section class="purchase-order-page">
        <nav class="purchase-order-toolbar" aria-label="Aksi purchase order">
            <a class="button button-primary" href="{{ route('employee.pembelian.purchase-order.download', $pembelian) }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M7 3h7l5 5v13H7z"></path><path d="M14 3v5h5M9 14h6M9 17h4"></path>
                </svg>
                Unduh PO (PDF)
            </a>
            <a class="button button-secondary" href="{{ route('employee.pembelian.export') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 4h13v16H4zM8 8h5M8 12h5M8 16h3M17 8h3v12h-3M17 12h3"></path>
                </svg>
                Ekspor daftar (Excel)
            </a>
            <a class="purchase-order-back" href="{{ route('employee.pembelian.index') }}">Kembali ke pembelian</a>
        </nav>

        <div class="purchase-order-canvas">
            <article class="purchase-order-sheet">
                <header class="purchase-order-heading">
                    <div>
                        <h1>Purchase order</h1>
                        <p>No. {{ $pembelian->id_pembelian }} · {{ \Illuminate\Support\Carbon::parse($pembelian->tanggal)->format('d/m/Y') }}</p>
                    </div>
                    <div class="purchase-order-company">
                        <strong>{{ config('app.name', 'GudangIn') }}</strong>
                        <span>Purchase order</span>
                    </div>
                </header>

                <section class="purchase-order-recipient">
                    <h2>Kepada</h2>
                    <strong>{{ $pembelian->supplier->nama_supplier }}</strong>
                    @if ($pembelian->supplier->alamat)
                        <span>{{ $pembelian->supplier->alamat }}</span>
                    @endif
                    @if ($pembelian->supplier->no_hp)
                        <span>{{ $pembelian->supplier->no_hp }}</span>
                    @endif
                </section>

                <div class="purchase-order-table-wrap">
                    <table class="purchase-order-table">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pembelian->detailPembelian as $detail)
                                <tr>
                                    <td>{{ $detail->persediaan->nama_barang }} <small>{{ $detail->satuan }}</small></td>
                                    <td>{{ \App\Support\QuantityFormatter::format($detail->jumlah_barang) }}</td>
                                    <td>Rp {{ number_format((float) $detail->harga_beli, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format((float) $detail->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3">Total</th>
                                <td>Rp {{ number_format((float) $pembelian->detailPembelian->sum('subtotal'), 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <footer class="purchase-order-footer">
                    <span>Status: {{ $pembelian->status_pengiriman }}</span>
                    <span>Dibuat oleh {{ $pembelian->pegawai->nama_pegawai }}</span>
                </footer>
            </article>
        </div>
    </section>
@endsection
