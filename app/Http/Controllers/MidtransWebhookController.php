<?php

namespace App\Http\Controllers;

use App\Models\CustomerPayment;
use App\Services\CustomerOrderService;
use App\Services\MidtransPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MidtransPaymentService $midtrans,
        CustomerOrderService $orders
    ): JsonResponse {
        $payload = $request->all();
        if (! $midtrans->hasValidSignature($payload)) {
            Log::warning('Rejected Midtrans notification with an invalid signature.');

            return response()->json(['message' => 'Invalid notification signature.'], 401);
        }

        $providerOrderId = $payload['order_id'] ?? null;
        if (! is_string($providerOrderId) || $providerOrderId === '') {
            return response()->json(['message' => 'Missing order ID.'], 422);
        }

        $payment = CustomerPayment::query()
            ->where('provider', 'midtrans')
            ->where('provider_order_id', $providerOrderId)
            ->first();

        if (! $payment) {
            Log::warning('Midtrans notification referenced an unknown order.', [
                'order_id' => $providerOrderId,
            ]);

            return response()->json(['message' => 'Payment not found.'], 404);
        }

        if (
            ! is_numeric($payload['gross_amount'] ?? null)
            || round((float) $payload['gross_amount'], 2) !== round((float) $payment->amount, 2)
        ) {
            Log::warning('Midtrans notification amount did not match the stored payment.', [
                'reference_id' => $payment->reference_id,
            ]);

            return response()->json(['message' => 'Payment amount does not match.'], 422);
        }

        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;
        $transactionId = is_string($payload['transaction_id'] ?? null)
            ? $payload['transaction_id']
            : null;

        if (
            $transactionStatus === 'settlement'
            || ($transactionStatus === 'capture' && in_array($fraudStatus, [null, 'accept'], true))
        ) {
            $orders->markPaid($payment, $transactionId);
        } elseif (in_array($transactionStatus, ['cancel', 'deny'], true)) {
            $orders->failGatewayPayment($payment, 'FAILED');
        } elseif ($transactionStatus === 'expire') {
            $orders->failGatewayPayment($payment, 'EXPIRED');
        } elseif ($transactionStatus === 'refund' || $transactionStatus === 'partial_refund') {
            Log::notice('Midtrans reported a refund for a customer order.', [
                'reference_id' => $payment->reference_id,
                'transaction_id' => $transactionId,
            ]);
        }

        return response()->json(['message' => 'Notification processed.']);
    }
}
