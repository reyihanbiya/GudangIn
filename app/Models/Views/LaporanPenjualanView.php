<?php

namespace App\Models\Views;

class LaporanPenjualanView extends ReadOnlyViewModel
{
    protected $table = 'view_laporanpenjualan';
    protected $primaryKey = 'id_penjualan';
    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'tanggal'         => 'date',
            'total_penjualan' => 'decimal:2',
        ];
    }
}
