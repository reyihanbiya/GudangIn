<?php

namespace App\Models\Views;

class LaporanPembelianView extends ReadOnlyViewModel
{
    protected $table = 'view_laporanpembelian';
    protected $primaryKey = 'id_pembelian';
    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'tanggal'         => 'date',
            'total_pembelian' => 'decimal:2',
        ];
    }
}
