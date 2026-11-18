<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class DatabaseReadiness
{
    private const REQUIRED_TABLES = [
        'owner',
        'pegawai',
        'pelanggan',
        'supplier',
        'persediaan',
        'penjualan',
        'detail_penjualan',
        'pembelian',
        'detail_pembelian',
        'nota_pembelian',
        'sys_sequences',
    ];

    private const REQUIRED_VIEWS = [
        'view_kartu_stok',
        'view_laporanpembelian',
        'view_laporanpenjualan',
    ];

    public static function missingTables(): array
    {
        $missingTables = array_values(array_filter(
            self::REQUIRED_TABLES,
            fn (string $table) => ! Schema::hasTable($table)
        ));

        $missingViews = array_values(array_filter(
            self::REQUIRED_VIEWS,
            fn (string $view) => ! Schema::hasView($view)
        ));

        return array_merge($missingTables, $missingViews);
    }
}
