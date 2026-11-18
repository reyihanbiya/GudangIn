@extends('layouts.app')

@section('title', 'Pembelian | GudangIn')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Transaksi</p>
            <h1>Pembelian</h1>
            <p class="lead">Catat pembelian barang dan konfirmasi penerimaan untuk menambah stok.</p>
        </div>
        <div class="heading-actions">
            <a class="button button-secondary" href="{{ route('employee.pembelian.ocr.create') }}">Baca nota (OCR)</a>
            <a class="button button-primary" href="{{ route('employee.pembelian.create') }}">Buat pembelian</a>
        </div>
    </section>

    @if (session('status'))
        <div class="notice notice-success page-notice">{{ session('status') }}</div>
    @endif
    <section class="table-panel">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Tanggal</th><th>ID</th><th>Supplier</th><th>Pegawai</th><th>Status</th><th>Total</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($transaction->tanggal)->format('d/m/Y') }}</td>
                            <td>{{ $transaction->id_pembelian }}</td>
                            <td>{{ $transaction->supplier->nama_supplier }}</td>
                            <td>{{ $transaction->pegawai->nama_pegawai }}</td>
                            <td><span class="status-badge">{{ $transaction->status_pengiriman }}</span></td>
                            <td>Rp {{ number_format((float) $transaction->total, 0, ',', '.') }}</td>
                            <td class="row-actions">
                                <a class="text-link" href="{{ route('employee.pembelian.show', $transaction) }}">Detail</a>
                                @if ($transaction->status_pengiriman === 'Diproses')
                                    <form method="POST" action="{{ route('employee.pembelian.status', $transaction) }}" onsubmit="return confirm('Konfirmasi barang sudah diterima?')">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status_pengiriman" value="Diterima">
                                        <button class="text-button" type="submit">Terima</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-cell" colspan="7">Belum ada transaksi pembelian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $transactions->links() }}</div>
    </section>
@endsection
