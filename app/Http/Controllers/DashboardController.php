<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use App\Models\Pelanggan;
use App\Models\Persediaan;
use App\Models\Penjualan;
use App\Models\Views\LaporanPembelianView;
use App\Models\Views\LaporanPenjualanView;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function employee(): View
    {
        $employee = Auth::guard('employee')->user();

        return view('dashboard.index', [
            'role' => 'Pegawai',
            'name' => $employee->nama_pegawai,
            'stats' => [
                ['label' => 'Barang terdaftar', 'value' => Persediaan::query()->count(), 'detail' => 'Jenis barang di persediaan'],
                ['label' => 'Stok kosong', 'value' => Persediaan::query()->where('stok', '<=', 0)->count(), 'detail' => 'Barang yang perlu diperiksa'],
                ['label' => 'Pelanggan', 'value' => Pelanggan::query()->count(), 'detail' => 'Data pelanggan tersimpan'],
                ['label' => 'Penjualan hari ini', 'value' => Penjualan::query()->whereDate('tanggal', today())->count(), 'detail' => 'Transaksi tercatat hari ini'],
            ],
        ]);
    }

    public function owner(): View
    {
        $owner = Auth::guard('owner')->user();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        return view('dashboard.index', [
            'role' => 'Owner',
            'name' => $owner->nama_owner,
            'stats' => [
                [
                    'label' => 'Penjualan bulan ini',
                    'value' => 'Rp '.number_format((float) LaporanPenjualanView::query()
                        ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                        ->sum('total_penjualan'), 0, ',', '.'),
                    'detail' => 'Total dari laporan penjualan',
                    'route' => 'owner.reports.sales',
                ],
                [
                    'label' => 'Pembelian bulan ini',
                    'value' => 'Rp '.number_format((float) LaporanPembelianView::query()
                        ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                        ->sum('total_pembelian'), 0, ',', '.'),
                    'detail' => 'Total dari laporan pembelian',
                    'route' => 'owner.reports.purchases',
                ],
                ['label' => 'Barang terdaftar', 'value' => Persediaan::query()->count(), 'detail' => 'Jenis barang di persediaan'],
                ['label' => 'Pegawai', 'value' => Pegawai::query()->count(), 'detail' => 'Akun operasional aktif'],
            ],
        ]);
    }
}
