@extends('layouts.app')

@section('title', 'Riwayat transaksi | GudangIn')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Kasir</p>
            <h1>Riwayat transaksi</h1>
            <p class="lead">Lihat kembali transaksi penjualan yang sudah tersimpan.</p>
        </div>
        <a class="button button-primary" href="{{ route('employee.penjualan.index') }}">Kembali ke kasir</a>
    </section>

    @if (session('status'))
        <div class="notice notice-success page-notice">{{ session('status') }}</div>
    @endif
    <section class="table-panel">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Tanggal</th><th>ID</th><th>Pelanggan</th><th>Pegawai</th><th>Total</th><th></th></tr></thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($transaction->tanggal)->format('d/m/Y') }}</td>
                            <td>{{ $transaction->id_penjualan }}</td>
                            <td>{{ $transaction->pelanggan->nama_pelanggan }}</td>
                            <td>{{ $transaction->pegawai->nama_pegawai }}</td>
                            <td>Rp {{ number_format((float) $transaction->total, 0, ',', '.') }}</td>
                            <td><a class="text-link" href="{{ route('employee.penjualan.show', $transaction) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td class="empty-cell" colspan="6">Belum ada transaksi penjualan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $transactions->links() }}</div>
    </section>
@endsection
