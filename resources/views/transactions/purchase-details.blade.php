@extends('layouts.app')

@section('title', 'Detail pembelian | GudangIn')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Transaksi</p>
            <h1>Detail pembelian</h1>
            <p class="lead">Lihat rincian pembelian berdasarkan tanggal.</p>
        </div>
        <a class="button button-secondary" href="{{ route('employee.pembelian.index') }}">Kembali</a>
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
            <a class="text-link" href="{{ route('employee.pembelian.details') }}">Atur ulang</a>
        </form>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>ID pembelian</th>
                        <th>Supplier</th>
                        <th>Barang</th>
                        <th>Jumlah</th>
                        <th>Harga satuan</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($details as $detail)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($detail->tanggal)->format('d/m/Y') }}</td>
                            <td>{{ $detail->id_pembelian }}</td>
                            <td>{{ $detail->nama_supplier }}</td>
                            <td>{{ $detail->nama_barang }}</td>
                            <td>{{ \App\Support\QuantityFormatter::format($detail->jumlah_barang) }} {{ $detail->satuan }}</td>
                            <td>Rp {{ number_format((float) $detail->harga_beli, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $detail->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td class="empty-cell" colspan="7">Belum ada detail pembelian pada rentang tanggal ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $details->links() }}</div>
    </section>
@endsection
