<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailPembelian extends Model
{
    protected $table = 'detail_pembelian';
    protected $primaryKey = 'id_detail';
    public $incrementing = true;
    public $timestamps = false;

    // Subtotal dihasilkan otomatis oleh MariaDB (STORED GENERATED COLUMN)
    protected $guarded = [
        'id_detail',
        'subtotal',
    ];

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'id_pembelian', 'id_pembelian');
    }

    public function persediaan(): BelongsTo
    {
        return $this->belongsTo(Persediaan::class, 'id_persediaan', 'id_persediaan');
    }

    public function penerimaanLines(): HasMany
    {
        return $this->hasMany(DetailPenerimaanPembelian::class, 'id_detail_pembelian', 'id_detail');
    }
}
