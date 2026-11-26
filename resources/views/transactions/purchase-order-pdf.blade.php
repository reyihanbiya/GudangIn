<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Purchase order {{ $pembelian->id_pembelian }}</title>
    <style>
        @page { margin: 34px 38px; }
        body { color: #171717; font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .heading { width: 100%; margin-bottom: 28px; }
        .heading td { vertical-align: top; }
        h1 { margin: 0 0 5px; font-size: 22px; font-weight: 600; }
        p { margin: 4px 0; color: #5f5f5f; }
        .company { text-align: right; font-size: 16px; font-weight: 600; }
        .recipient { margin-bottom: 24px; }
        .recipient strong, .recipient span { display: block; margin-top: 4px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th, table.items td { padding: 10px 8px; border-bottom: 1px solid #d5d5d5; text-align: left; }
        table.items th { color: #5f5f5f; font-weight: 500; }
        table.items th:nth-child(n+2), table.items td:nth-child(n+2) { text-align: right; white-space: nowrap; }
        table.items td small { color: #666; }
        table.items tfoot th, table.items tfoot td { color: #171717; font-size: 14px; font-weight: 600; }
        .meta { margin-top: 24px; color: #5f5f5f; }
    </style>
</head>
<body>
    <table class="heading">
        <tr>
            <td>
                <h1>Purchase order</h1>
                <p>No. {{ $pembelian->id_pembelian }} · {{ \Illuminate\Support\Carbon::parse($pembelian->tanggal)->format('d/m/Y') }}</p>
            </td>
            <td class="company">{{ config('app.name', 'GudangIn') }}</td>
        </tr>
    </table>

    <section class="recipient">
        <p>Kepada:</p>
        <strong>{{ $pembelian->supplier->nama_supplier }}</strong>
        @if ($pembelian->supplier->alamat)
            <span>{{ $pembelian->supplier->alamat }}</span>
        @endif
        @if ($pembelian->supplier->no_hp)
            <span>{{ $pembelian->supplier->no_hp }}</span>
        @endif
    </section>

    <table class="items">
        <thead>
            <tr><th>Barang</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr>
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

    <p class="meta">Status: {{ $pembelian->status_pengiriman }} · Dibuat oleh {{ $pembelian->pegawai->nama_pegawai }}</p>
</body>
</html>
