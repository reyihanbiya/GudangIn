<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $table = 'supplier';
    protected $primaryKey = 'id_supplier';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_supplier',
        'nama_supplier',
        'alamat',
        'no_hp',
    ];

    public function pembelian(): HasMany
    {
        return $this->hasMany(Pembelian::class, 'id_supplier', 'id_supplier');
    }
}
