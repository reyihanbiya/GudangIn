<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaPembelian extends Model
{
    protected $table = 'nota_pembelian';
    protected $primaryKey = 'id_nota';
    public $timestamps = false;

    protected $fillable = [
        'id_pembelian',
        'file_path',
        'hasil_ocr',
    ];

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'id_pembelian', 'id_pembelian');
    }
}
