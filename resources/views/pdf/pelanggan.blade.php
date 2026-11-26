
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan - GudangIn</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 35px 40px;
        }

        body {
            font-family: Arial, sans-serif;
            color: #302720;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .brand {
            color: #764c2d;
            font-size: 27px;
            font-weight: bold;
            margin: 0 0 18px;
        }

        .header h2 {
            font-size: 20px;
            margin: 0 0 7px;
        }

        .header p {
            color: #82766b;
            margin: 0;
            font-size: 11px;
        }

        .identity {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }

        .identity td {
            padding: 5px 0;
        }

        .identity .label {
            width: 150px;
            color: #82766b;
        }

        .section-title {
            background: #f2eae4;
            color: #503a2b;
            padding: 9px 10px;
            font-weight: bold;
            margin: 18px 0 10px;
        }

        .info-table,
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 8px 10px;
            border: 1px solid #d8ccc3;
        }

        .info-table td:first-child {
            width: 30%;
            color: #82766b;
        }

        .items-table th {
            background: #f2eae4;
            color: #503a2b;
            text-align: left;
            padding: 10px;
            border: 1px solid #d8ccc3;
        }

        .items-table td {
            padding: 10px;
            border: 1px solid #d8ccc3;
        }

        .center {
            text-align: center;
        }

        .signatures {
            width: 100%;
            margin-top: 55px;
            text-align: center;
        }

        .signatures td {
            width: 50%;
            vertical-align: top;
        }

        .signature-space {
            height: 75px;
        }

        .signature-line {
            border-top: 1px solid #302720;
            width: 70%;
            margin: 0 auto;
            padding-top: 7px;
        }

        .footer {
            margin-top: 35px;
            text-align: right;
            color: #82766b;
            font-size: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1 class="brand">GudangIn</h1>
        <h2>SURAT JALAN</h2>
        <p>DOKUMEN PENGIRIMAN BARANG</p>
    </div>

    <table class="identity">
        <tr>
            <td class="label">ID PENGIRIMAN</td>
            <td><strong>{{ $idPengiriman }}</strong></td>
        </tr>
        <tr>
            <td class="label">TANGGAL</td>
            <td>
                <strong>
                    {{ $penjualan ? \Carbon\Carbon::parse($penjualan->tanggal)->locale('id')->translatedFormat('d F Y') : '—' }}
                </strong>
            </td>
        </tr>
    </table>

    <div class="section-title">INFORMASI PENERIMA</div>

    <table class="info-table">
        <tr>
            <td>ID Pelanggan</td>
            <td>{{ $pelanggan->id_pelanggan }}</td>
        </tr>
        <tr>
            <td>Nama</td>
            <td>{{ $pelanggan->nama_pelanggan }}</td>
        </tr>
        <tr>
            <td>No. Telepon</td>
            <td>{{ $pelanggan->no_hp ?: '—' }}</td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td>{{ $pelanggan->alamat ?: '—' }}</td>
        </tr>
    </table>

    <div class="section-title">DETAIL BARANG</div>

    <table class="items-table">
        <thead>
            <tr>
                <th class="center" style="width: 10%;">No.</th>
                <th>Nama Barang</th>
                <th style="width: 25%;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($detailBarang as $index => $detail)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $detail->persediaan->nama_barang ?? 'Barang tidak ditemukan' }}</td>
                    <td>
                        {{ $detail->jumlah_barang }}
                        {{ $detail->satuan ?? $detail->persediaan->satuan ?? '' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="center">Tidak ada detail barang.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <strong>PENERIMA</strong>
                <div class="signature-space"></div>
                <div class="signature-line">Nama dan tanda tangan</div>
            </td>
            <td>
                <strong>PENGIRIM</strong>
                <div class="signature-space"></div>
                <div class="signature-line">Nama dan tanda tangan</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Dicetak pada: {{ $tanggalCetak }}
    </div>
</body>
</html>
