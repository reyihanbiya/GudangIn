<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Penjualan extends Model
{
    protected $table = 'penjualan';
    protected $primaryKey = 'id_penjualan';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_penjualan',
        'id_pengiriman',
        'id_pelanggan',
        'id_pegawai',
        'tanggal',
        'jenis_pengiriman',
        'status_pengiriman',
        'status_pembayaran',
        'metode_pembayaran',
        'alamat_pengiriman',
        'nama_penerima',
        'telepon_penerima',
        'latitude_pengiriman',
        'longitude_pengiriman',
        'biaya_pengiriman',
        'id_kurir',
    ];

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai', 'id_pegawai');
    }

    public function detailPenjualan(): HasMany
    {
        return $this->hasMany(DetailPenjualan::class, 'id_penjualan', 'id_penjualan');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class, 'id_penjualan', 'id_penjualan')
            ->latest();
    }

    public function orderEvents(): HasMany
    {
        return $this->hasMany(OrderEvent::class, 'id_penjualan', 'id_penjualan')
            ->orderBy('created_at');
    }

    public function courierLocation(): HasOne
    {
        return $this->hasOne(CourierLocation::class, 'id_penjualan', 'id_penjualan');
    }
}
