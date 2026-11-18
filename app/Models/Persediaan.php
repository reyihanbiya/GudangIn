<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Persediaan extends Model
{
    protected $table = 'persediaan';
    protected $primaryKey = 'id_persediaan';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_persediaan',
        'id_pegawai',
        'nama_barang',
        'kategori_barang',
        'stok',
    ];

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai', 'id_pegawai');
    }

    public function detailPenjualan(): HasMany
    {
        return $this->hasMany(DetailPenjualan::class, 'id_persediaan', 'id_persediaan');
    }

    public function detailPembelian(): HasMany
    {
        return $this->hasMany(DetailPembelian::class, 'id_persediaan', 'id_persediaan');
    }

    /**
     * Mengambil riwayat harga jual satuan terakhir berdasarkan garis waktu tanggal transaksi penjualan.
     * Mengembalikan null jika belum pernah ada transaksi penjualan.
     */
    public function getHargaJualTerakhirAttribute(): ?float
    {
        $lastSale = DetailPenjualan::query()
            ->join('penjualan', 'penjualan.id_penjualan', '=', 'detail_penjualan.id_penjualan')
            ->where('detail_penjualan.id_persediaan', $this->id_persediaan)
            ->orderByDesc('penjualan.tanggal')
            ->orderByDesc('penjualan.id_penjualan')
            ->orderByDesc('detail_penjualan.id_detail')
            ->first(['detail_penjualan.harga_satuan']);

        return $lastSale ? (float) $lastSale->harga_satuan : null;
    }
}
