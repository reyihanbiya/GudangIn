<?php

namespace App\Models\Views;

class KartuStokView extends ReadOnlyViewModel
{
    protected $table = 'view_kartu_stok';
    protected $primaryKey = 'id_persediaan';
    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'masuk'   => 'integer',
            'keluar'  => 'integer',
        ];
    }
}
