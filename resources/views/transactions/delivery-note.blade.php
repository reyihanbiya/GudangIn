<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Jalan {{ $penjualan->id_penjualan }}</title>
    <style>
        @page { margin: 28px; }
        body { color: #302820; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        .heading { margin-bottom: 30px; text-align: center; }
        .brand { margin: 0 0 12px; color: #805333; font-size: 24px; font-weight: bold; }
        h1 { margin: 0 0 5px; font-size: 19px; }
        .subtitle { margin: 0; color: #82766b; font-size: 10px; }
        .metadata { width: 100%; margin-bottom: 22px; border-collapse: collapse; }
        .metadata td { padding: 4px 0; }
        .metadata td:first-child { width: 150px; color: #82766b; }
        .section-title { margin: 18px 0 8px; padding: 9px 10px; color: #514337; background: #f1eae4; font-weight: bold; }
        .details, .items { width: 100%; border-collapse: collapse; }
        .details td, .items th, .items td { padding: 8px 10px; border: 1px solid #ded2c8; text-align: left; }
        .details td:first-child { width: 30%; color: #82766b; }
        .items th { color: #514337; background: #f1eae4; }
        .items td:first-child, .items th:first-child { width: 10%; text-align: center; }
        .signatures { width: 100%; margin-top: 55px; border-collapse: collapse; text-align: center; }
        .signatures td { width: 50%; padding: 0 20px; }
        .signature-space { height: 75px; }
        .signature-line { border-top: 1px solid #302820; }
        .signature-caption { padding-top: 6px; color: #66574b; }
    </style>
</head>
<body>
    <header class="heading">
        <p class="brand">GudangIn</p>
        <h1>SURAT JALAN</h1>
        <p class="subtitle">DOKUMEN PENGIRIMAN BARANG</p>
    </header>

    <table class="metadata">
        <tr>
            <td>ID TRANSAKSI</td>
            <td><strong>{{ $penjualan->id_penjualan }}</strong></td>
        </tr>
        <tr>
            <td>TANGGAL</td>
            <td><strong>{{ \Illuminate\Support\Carbon::parse($penjualan->tanggal)->translatedFormat('d F Y') }}</strong></td>
        </tr>
    </table>

    <div class="section-title">INFORMASI PENERIMA</div>
    <table class="details">
        <tr><td>ID Pelanggan</td><td>{{ $penjualan->pelanggan->id_pelanggan }}</td></tr>
        <tr><td>Nama</td><td>{{ $penjualan->pelanggan->nama_pelanggan }}</td></tr>
        <tr><td>No. Telepon</td><td>{{ $penjualan->pelanggan->no_hp ?: '—' }}</td></tr>
        <tr><td>Alamat</td><td>{{ $penjualan->pelanggan->alamat ?: '—' }}</td></tr>
    </table>

    <div class="section-title">DETAIL BARANG</div>
    <table class="items">
        <thead>
            <tr><th>No.</th><th>Nama Barang</th><th>Jumlah</th></tr>
        </thead>
        <tbody>
            @foreach ($penjualan->detailPenjualan as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->persediaan?->nama_barang ?? 'Barang tidak tersedia' }}</td>
                    <td>{{ \App\Support\QuantityFormatter::format($detail->jumlah_barang) }} {{ $detail->satuan ?: ($detail->persediaan?->satuan ?? '') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="signatures">
        <tr><td><strong>PENERIMA</strong></td><td><strong>PENGIRIM</strong></td></tr>
        <tr><td class="signature-space"></td><td class="signature-space"></td></tr>
        <tr><td class="signature-line"></td><td class="signature-line"></td></tr>
        <tr><td class="signature-caption">Nama dan tanda tangan</td><td class="signature-caption">Nama dan tanda tangan</td></tr>
    </table>
</body>
</html>
