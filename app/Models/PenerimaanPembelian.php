<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PenerimaanPembelian extends Model
{
    protected $table = 'penerimaan_pembelian';

    protected $primaryKey = 'id_penerimaan';

    protected $fillable = [
        'id_pembelian',
        'id_pegawai',
        'catatan',
    ];

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'id_pembelian', 'id_pembelian');
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai', 'id_pegawai');
    }

    public function detailPenerimaan(): HasMany
    {
        return $this->hasMany(DetailPenerimaanPembelian::class, 'id_penerimaan', 'id_penerimaan');
    }
}
