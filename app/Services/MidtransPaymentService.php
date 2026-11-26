<?php

namespace App\Services;

use App\Models\CustomerPayment;
use App\Models\Penjualan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransPaymentService
{
    public function isConfigured(): bool
    {
        $serverKey = config('services.midtrans.server_key');

        return is_string($serverKey) && $serverKey !== '';
    }

    public function createCheckout(CustomerPayment $payment, Penjualan $sale): array
    {
        $serverKey = config('services.midtrans.server_key');
        if (! is_string($serverKey) || $serverKey === '') {
            throw new MidtransPaymentException('Pembayaran otomatis belum dikonfigurasi.');
        }

        $sale->loadMissing('pelanggan');
        $grossAmount = (int) round((float) $payment->amount);

        $payload = [
            'transaction_details' => [
                'order_id' => $payment->provider_order_id,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => mb_substr($sale->nama_penerima ?: $sale->pelanggan->nama_pelanggan, 0, 50),
                'phone' => $sale->telepon_penerima ?: $sale->pelanggan->no_hp,
            ],
            'enabled_payments' => $payment->method === 'qris'
                ? ['qris']
                : ['bca_va', 'bni_va', 'bri_va', 'permata_va', 'other_va', 'echannel'],
        ];

        $baseUrl = config('services.midtrans.is_production')
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';
        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->post($baseUrl.'/snap/v1/transactions', $payload);

        if ($response->failed()) {
            Log::warning('Midtrans rejected a customer checkout request.', [
                'reference_id' => $payment->reference_id,
                'http_status' => $response->status(),
            ]);
            throw new MidtransPaymentException('Midtrans gagal menyiapkan pembayaran. Silakan coba kembali atau pilih transfer manual.');
        }

        $result = $response->json();
        if (
            ! is_array($result)
            || ! is_string($result['token'] ?? null)
            || ! is_string($result['redirect_url'] ?? null)
        ) {
            Log::error('Midtrans checkout response was incomplete.', [
                'reference_id' => $payment->reference_id,
            ]);
            throw new MidtransPaymentException('Respons pembayaran dari Midtrans tidak lengkap.');
        }

        return [
            'token' => $result['token'],
            'redirect_url' => $result['redirect_url'],
        ];
    }

    public function hasValidSignature(array $payload): bool
    {
        $serverKey = config('services.midtrans.server_key');
        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signature = $payload['signature_key'] ?? null;

        if (
            ! is_string($serverKey) || $serverKey === ''
            || ! is_string($orderId)
            || ! (is_string($statusCode) || is_int($statusCode))
            || ! (is_string($grossAmount) || is_numeric($grossAmount))
            || ! is_string($signature)
        ) {
            return false;
        }

        $expected = hash('sha512', $orderId.(string) $statusCode.(string) $grossAmount.$serverKey);

        return hash_equals($expected, $signature);
    }
}
