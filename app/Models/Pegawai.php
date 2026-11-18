<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pegawai extends Authenticatable
{
    protected $table = 'pegawai';
    protected $primaryKey = 'id_pegawai';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_pegawai',
        'nama_pegawai',
        'username',
        'password',
        'jabatan',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function penjualan(): HasMany
    {
        return $this->hasMany(Penjualan::class, 'id_pegawai', 'id_pegawai');
    }

    public function pembelian(): HasMany
    {
        return $this->hasMany(Pembelian::class, 'id_pegawai', 'id_pegawai');
    }

    public function persediaan(): HasMany
    {
        return $this->hasMany(Persediaan::class, 'id_pegawai', 'id_pegawai');
    }
}
