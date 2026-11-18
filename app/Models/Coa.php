<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coa extends Model
{
    protected $table = 'coa';
    protected $primaryKey = 'kode_akun';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'kode_akun',
        'nama_akun',
        'jenis_akun',
    ];
}
