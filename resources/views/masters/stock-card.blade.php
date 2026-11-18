@extends('layouts.app')

@section('title', 'Kartu stok | GudangIn')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Persediaan / Kartu stok</p>
            <h1>{{ $persediaan->nama_barang }}</h1>
            <p class="lead">Stok saat ini: <strong>{{ $persediaan->stok }}</strong> {{ $persediaan->kategori_barang ? '· '.$persediaan->kategori_barang : '' }}</p>
        </div>
        <a class="button button-secondary" href="{{ route('employee.persediaan.index') }}">Kembali ke persediaan</a>
    </section>
    <section class="table-panel">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis transaksi</th>
                        <th>ID transaksi</th>
                        <th>Barang masuk</th>
                        <th>Barang keluar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($movement->tanggal)->format('d/m/Y') }}</td>
                            <td>{{ $movement->keterangan }}</td>
                            <td>{{ $movement->id_transaksi }}</td>
                            <td>{{ $movement->masuk }}</td>
                            <td>{{ $movement->keluar }}</td>
                        </tr>
                    @empty
                        <tr><td class="empty-cell" colspan="5">Belum ada pergerakan stok untuk barang ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $movements->links() }}</div>
    </section>
@endsection
