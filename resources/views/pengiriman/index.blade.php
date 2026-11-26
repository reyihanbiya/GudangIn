@extends('layouts.app')

@section('title', 'Pengiriman | GudangIn')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Transaksi</p>
            <h1>Pengiriman</h1>
            <p class="lead">Setiap transaksi penjualan memiliki ID pengiriman dan surat jalan sendiri.</p>
        </div>
    </section>

    @if (session('status'))
        <div class="notice notice-success page-notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="notice notice-error page-notice">{{ $errors->first() }}</div>
    @endif

    <section class="table-panel">
        <form method="GET" class="search-form">
            <label class="sr-only" for="search">Cari pengiriman</label>
            <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Cari ID atau nama pelanggan...">
            <button class="button button-secondary" type="submit">Cari</button>
            @if ($search)
                <a class="text-link" href="{{ url()->current() }}">Hapus pencarian</a>
            @endif
        </form>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>ID Pengiriman</th>
                        <th>ID Transaksi</th>
                        <th>Pelanggan</th>
                        <th class="actions-heading">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($transaction->tanggal)->format('d/m/Y') }}</td>
                            <td>{{ $transaction->id_pengiriman ?? '—' }}</td>
                            <td>{{ $transaction->id_penjualan }}</td>
                            <td>{{ $transaction->pelanggan?->nama_pelanggan ?? '—' }}</td>
                            <td class="row-actions">
                                <a class="text-button" href="{{ route('employee.penjualan.delivery-note.preview', $transaction) }}" target="_blank">Lihat PDF</a>
                                <a class="text-button" href="{{ route('employee.penjualan.delivery-note', $transaction) }}">Ekspor PDF</a>
                                <a class="action-icon action-print" style="width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;padding:0;border:0;border-radius:7px;background:transparent;color:#764c2d;text-decoration:none" href="{{ route('employee.pengiriman.pdf', $transaction) }}" target="_blank" aria-label="Cetak surat jalan" title="Cetak surat jalan">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#764c2d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M6 9V3h12v6M6 17H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2" />
                                        <path d="M6 14h12v7H6zM18 12h.01" />
                                    </svg>
                                </a>
                                <a class="action-icon action-edit" style="width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;padding:0;border:0;border-radius:7px;background:transparent;color:#23824a;text-decoration:none" href="{{ route('employee.pengiriman.edit', $transaction) }}" aria-label="Ubah transaksi" title="Ubah transaksi">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#23824a" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="m16 4 4 4M4 20l4-.8L19 8a2.1 2.1 0 0 0-3-3L5 16z" />
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('employee.pengiriman.destroy', $transaction) }}" onsubmit="return confirm('Hapus transaksi ini? Stok barang akan dikembalikan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="action-icon action-delete" style="width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;padding:0;border:0;border-radius:7px;background:transparent;color:#a13d31;cursor:pointer" type="submit" aria-label="Hapus transaksi" title="Hapus transaksi">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#a13d31" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M3 6h18M8 6V4h8v2m-11 0 1 15h12l1-15M10 11v6M14 11v6" />
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-cell" colspan="5">Belum ada transaksi untuk dikirim.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $transactions->links() }}</div>
    </section>
@endsection
