@extends('layouts.app')

@section('title', 'Pengiriman | GudangIn')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Operasional toko</p>
            <h1>Pengiriman</h1>
            <p class="lead">Kelola surat jalan untuk setiap transaksi penjualan.</p>
        </div>
        <a class="button button-secondary" href="{{ route('employee.pelanggan.index') }}">Data pelanggan</a>
    </section>

    <section class="table-panel">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>ID transaksi</th>
                        <th>Penerima</th>
                        <th>Alamat</th>
                        <th>Jenis barang</th>
                        <th class="actions-heading">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deliveries as $delivery)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($delivery->tanggal)->format('d/m/Y') }}</td>
                            <td>{{ $delivery->id_penjualan }}</td>
                            <td>{{ $delivery->pelanggan?->nama_pelanggan ?? '—' }}</td>
                            <td>{{ $delivery->pelanggan?->alamat ?: '—' }}</td>
                            <td>{{ number_format($delivery->detail_penjualan_count, 0, ',', '.') }}</td>
                            <td class="row-actions">
                                <a class="button button-secondary transaction-action-button" href="{{ route('employee.penjualan.delivery-note.preview', $delivery) }}" target="_blank" rel="noopener">Lihat PDF</a>
                                <a class="button button-secondary transaction-action-button" href="{{ route('employee.penjualan.delivery-note', $delivery) }}">Ekspor PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-cell" colspan="6">Belum ada transaksi pengiriman.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $deliveries->links() }}</div>
    </section>
@endsection
