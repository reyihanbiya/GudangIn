<?php

namespace App\Http\Controllers;

use App\Models\XenditPayment;
use App\Services\PenjualanPaymentSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    public function __invoke(Request $request, PenjualanPaymentSettlementService $settlement): JsonResponse
    {
        $webhookToken = config('services.xendit.webhook_token');
        if (! is_string($webhookToken) || $webhookToken === '') {
            return response()->json(['message' => 'Webhook token is not configured.'], 503);
        }

        $callbackToken = $request->header('x-callback-token');
        if (! is_string($callbackToken) || ! hash_equals($webhookToken, $callbackToken)) {
            return response()->json(['message' => 'Invalid callback token.'], 401);
        }

        $event = $request->input('event');
        if (! in_array($event, ['payment.capture', 'payment.failure', 'payment_request.expiry'], true)) {
            return response()->json(['message' => 'Event ignored.']);
        }

        $data = $request->input('data');
        $paymentRequestId = is_array($data) ? ($data['payment_request_id'] ?? null) : null;
        if (! is_string($paymentRequestId) || $paymentRequestId === '') {
            return response()->json(['message' => 'Missing payment request ID.'], 422);
        }

        $payment = XenditPayment::query()
            ->where('payment_request_id', $paymentRequestId)
            ->first();

        if (! $payment) {
            Log::warning('Xendit webhook references an unknown payment request.', [
                'payment_request_id' => $paymentRequestId,
            ]);

            return response()->json(['message' => 'Payment request not found.'], 404);
        }

        if ($payment->isSimulation()) {
            return response()->json(['message' => 'Simulation payments are not settled by webhooks.']);
        }

        if ($event === 'payment.capture') {
            $amountMatches = isset($data['request_amount'])
                && round((float) $data['request_amount'], 2) === round((float) $payment->amount, 2);

            if (
                ($data['status'] ?? null) !== 'SUCCEEDED'
                || ($data['reference_id'] ?? null) !== $payment->reference_id
                || ($data['currency'] ?? null) !== 'IDR'
                || ! $amountMatches
            ) {
                Log::warning('Xendit payment capture did not match the stored checkout.', [
                    'payment_request_id' => $paymentRequestId,
                ]);

                return response()->json(['message' => 'Payment details do not match.'], 422);
            }
        }

        if ($event === 'payment.capture') {
            $settlement->capture($payment, $data['payment_id'] ?? null);
        } else {
            $settlement->failOrExpire($payment, $event === 'payment.failure' ? 'FAILED' : 'EXPIRED');
        }

        return response()->json(['message' => 'Webhook processed.']);
    }
}
