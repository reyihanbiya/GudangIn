<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JurnalUmum extends Model
{
    protected $table = 'jurnal_umum';
    protected $primaryKey = 'id_jurnal';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'kode_akun',
        'id_pembelian',
        'id_penjualan',
        'tanggal_jurnal',
        'no_akun',
        'nama_akun',
        'debit',
        'kredit',
    ];
}
