<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pembelian extends Model
{
    protected $table = 'pembelian';
    protected $primaryKey = 'id_pembelian';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_pembelian',
        'id_supplier',
        'id_pegawai',
        'tanggal',
        'status_pengiriman',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai', 'id_pegawai');
    }

    public function detailPembelian(): HasMany
    {
        return $this->hasMany(DetailPembelian::class, 'id_pembelian', 'id_pembelian');
    }

    public function penerimaanPembelian(): HasMany
    {
        return $this->hasMany(PenerimaanPembelian::class, 'id_pembelian', 'id_pembelian')
            ->orderBy('created_at');
    }

    public function notaPembelian(): HasOne
    {
        return $this->hasOne(NotaPembelian::class, 'id_pembelian', 'id_pembelian');
    }
}
