<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPenjualan extends Model
{
    protected $table = 'detail_penjualan';
    protected $primaryKey = 'id_detail';
    public $incrementing = true;
    public $timestamps = false;

    // Subtotal dihasilkan otomatis oleh MariaDB (STORED GENERATED COLUMN)
    protected $guarded = [
        'id_detail',
        'subtotal',
    ];

    public function penjualan(): BelongsTo
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan', 'id_penjualan');
    }

    public function persediaan(): BelongsTo
    {
        return $this->belongsTo(Persediaan::class, 'id_persediaan', 'id_persediaan');
    }
}
