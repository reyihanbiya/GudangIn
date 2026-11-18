@extends('layouts.app')

@section('title', $title.' | GudangIn')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Laporan Owner</p>
            <h1>{{ $title }}</h1>
            <p class="lead">Gunakan rentang tanggal untuk menyaring data transaksi.</p>
        </div>
    </section>
    @if ($errors->any())
        <div class="notice notice-error page-notice">{{ $errors->first() }}</div>
    @endif
    <section class="table-panel">
        <form method="GET" class="report-filter">
            <div>
                <label for="from">Dari tanggal</label>
                <input id="from" name="from" type="date" value="{{ $filters['from'] }}">
            </div>
            <div>
                <label for="to">Sampai tanggal</label>
                <input id="to" name="to" type="date" value="{{ $filters['to'] }}">
            </div>
            <button class="button button-primary" type="submit">Terapkan</button>
            <a class="text-link" href="{{ url()->current() }}">Atur ulang</a>
        </form>
        <div class="table-scroll">
            <table>
                @if ($type === 'sales')
                    <thead><tr><th>Tanggal</th><th>ID penjualan</th><th>Pelanggan</th><th>Pegawai</th><th>Total</th></tr></thead>
                    <tbody>
                        @forelse ($reports as $report)
                            <tr>
                                <td>{{ $report->tanggal?->format('d/m/Y') }}</td>
                                <td>{{ $report->id_penjualan }}</td>
                                <td>{{ $report->nama_pelanggan }}</td>
                                <td>{{ $report->nama_pegawai }}</td>
                                <td>Rp {{ number_format((float) $report->total_penjualan, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td class="empty-cell" colspan="5">Tidak ada data penjualan pada rentang tanggal ini.</td></tr>
                        @endforelse
                    </tbody>
                @else
                    <thead><tr><th>Tanggal</th><th>ID pembelian</th><th>Supplier</th><th>Pegawai</th><th>Status</th><th>Total</th></tr></thead>
                    <tbody>
                        @forelse ($reports as $report)
                            <tr>
                                <td>{{ $report->tanggal?->format('d/m/Y') }}</td>
                                <td>{{ $report->id_pembelian }}</td>
                                <td>{{ $report->nama_supplier }}</td>
                                <td>{{ $report->nama_pegawai }}</td>
                                <td>{{ $report->status_pengiriman }}</td>
                                <td>Rp {{ number_format((float) $report->total_pembelian, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td class="empty-cell" colspan="6">Tidak ada data pembelian pada rentang tanggal ini.</td></tr>
                        @endforelse
                    </tbody>
                @endif
            </table>
        </div>
        <div class="pagination-wrap">{{ $reports->links() }}</div>
    </section>
@endsection
