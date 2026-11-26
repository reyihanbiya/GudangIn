<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPenerimaanPembelian extends Model
{
    protected $table = 'detail_penerimaan_pembelian';

    protected $primaryKey = 'id_detail_penerimaan';

    public $timestamps = false;

    protected $fillable = [
        'id_penerimaan',
        'id_detail_pembelian',
        'jumlah_datang',
    ];

    public function penerimaan(): BelongsTo
    {
        return $this->belongsTo(PenerimaanPembelian::class, 'id_penerimaan', 'id_penerimaan');
    }

    public function detailPembelian(): BelongsTo
    {
        return $this->belongsTo(DetailPembelian::class, 'id_detail_pembelian', 'id_detail');
    }
}
