<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class XenditPayment extends Model
{
    protected $table = 'xendit_payments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'reference_id',
        'payment_request_id',
        'payment_id',
        'id_pelanggan',
        'id_pegawai',
        'id_penjualan',
        'tanggal',
        'amount',
        'items',
        'qr_string',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'items' => 'array',
            'tanggal' => 'date',
            'expires_at' => 'datetime',
        ];
    }

    public function isSimulation(): bool
    {
        return str_starts_with($this->payment_request_id, 'sim-');
    }
}
