<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XenditPaymentService
{
    public function createQrisPayment(string $referenceId, float $amount): array
    {
        $secretKey = config('services.xendit.secret_key');

        if (! is_string($secretKey) || $secretKey === '') {
            throw new XenditPaymentException('Xendit belum dikonfigurasi. Isi XENDIT_SECRET_KEY.');
        }

        if (str_starts_with($secretKey, 'xnd_public_')) {
            throw new XenditPaymentException(
                'XENDIT_SECRET_KEY berisi Public Key. Ganti dengan Secret Key Xendit dari dashboard.'
            );
        }

        $response = Http::withBasicAuth($secretKey, '')
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->withHeaders([
                'Idempotency-key' => $referenceId,
                'api-version' => '2024-11-11',
            ])
            ->post(rtrim(config('services.xendit.api_url'), '/').'/v3/payment_requests', [
                'reference_id' => $referenceId,
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $amount,
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'channel_properties' => [
                    'qr_string_type' => 'DYNAMIC',
                ],
            ]);

        if ($response->failed()) {
            $errorCode = $response->json('error_code');
            $errorCode = is_string($errorCode) && preg_match('/^[A-Z0-9_]{1,64}$/', $errorCode)
                ? $errorCode
                : null;
            $errorDetails = $this->errorDetails($response->json());

            Log::warning('Xendit rejected a QRIS payment request.', [
                'reference_id' => $referenceId,
                'status' => $response->status(),
                'error_code' => $errorCode,
                'error_details' => $errorDetails,
                'request_id' => $response->header('Request-Id'),
            ]);

            $message = match ($response->status()) {
                400 => 'Xendit menolak data pembayaran QRIS (HTTP 400).',
                401 => 'Xendit menolak autentikasi (HTTP 401). Pastikan XENDIT_SECRET_KEY adalah Secret Key aktif.',
                default => 'Xendit gagal membuat pembayaran QRIS (HTTP '.$response->status().').',
            };

            if ($errorCode !== null) {
                $message .= ' Kode: '.$errorCode.'.';
            }
            if ($errorDetails !== null) {
                $message .= ' Detail: '.$errorDetails;
            }

            throw new XenditPaymentException($message);
        }

        $paymentRequest = $response->json();
        $qrAction = is_array($paymentRequest)
            ? collect($paymentRequest['actions'] ?? [])
                ->first(fn ($action) => is_array($action) && ($action['type'] ?? null) === 'PRESENT_TO_CUSTOMER')
            : null;

        if (
            ! is_array($paymentRequest)
            || ! filled($paymentRequest['payment_request_id'] ?? null)
            || ! is_array($qrAction)
            || ! filled($qrAction['value'] ?? null)
        ) {
            Log::error('Xendit QRIS response did not contain the expected payment action.', [
                'reference_id' => $referenceId,
            ]);

            throw new XenditPaymentException('Respons QRIS dari Xendit tidak lengkap. Silakan coba checkout kembali.');
        }

        return [
            'payment_request_id' => $paymentRequest['payment_request_id'],
            'qr_string' => $qrAction['value'],
            'expires_at' => $paymentRequest['expires_at'] ?? null,
        ];
    }

    private function errorDetails(mixed $body): ?string
    {
        if (! is_array($body)) {
            return null;
        }

        $details = [];
        $message = $body['message'] ?? null;
        if (is_string($message)) {
            $details[] = $message;
        }

        $errors = is_array($body['errors'] ?? null) ? $body['errors'] : [];
        foreach (array_slice($errors, 0, 3) as $error) {
            if (! is_array($error)) {
                continue;
            }

            $field = $error['path'] ?? $error['field'] ?? $error['param'] ?? null;
            $reason = $error['message'] ?? $error['detail'] ?? null;
            if (is_string($reason)) {
                $details[] = (is_string($field) ? $field.': ' : '').$reason;
            }
        }

        $details = array_map(
            fn (string $detail) => trim(preg_replace('/[\r\n\t]+/', ' ', strip_tags($detail)) ?? ''),
            $details
        );
        $details = array_values(array_filter(array_unique($details)));

        return $details === [] ? null : substr(implode('; ', $details), 0, 300);
    }
}
