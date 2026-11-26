<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerPayment extends Model
{
    protected $table = 'customer_payments';

    protected $fillable = [
        'id_penjualan',
        'method',
        'provider',
        'reference_id',
        'provider_order_id',
        'provider_transaction_id',
        'checkout_url',
        'amount',
        'status',
        'proof_path',
        'metadata',
        'verified_by',
        'verified_by_type',
        'verified_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'verified_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function penjualan(): BelongsTo
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan', 'id_penjualan');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class, 'customer_payment_id');
    }
}
