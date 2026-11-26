<?php

namespace App\Http\Controllers;

use App\Models\CustomerPayment;
use App\Models\Penjualan;
use App\Models\OrderEvent;
use App\Services\CustomerOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class PaymentReviewController extends Controller
{
    public function index(): View
    {
        $payments = CustomerPayment::query()
            ->with(['penjualan.pelanggan'])
            ->whereIn('status', ['AWAITING_VERIFICATION', 'PENDING'])
            ->orderByRaw("CASE WHEN status = 'AWAITING_VERIFICATION' THEN 0 ELSE 1 END")
            ->orderBy('created_at')
            ->paginate(20);
        $pickupOrders = Penjualan::query()
            ->with(['pelanggan', 'payments'])
            ->where('jenis_pengiriman', 'ambil_di_toko')
            ->where('status_pengiriman', 'SIAP_DIAMBIL')
            ->orderBy('tanggal')
            ->get();

        return view('payments.review', compact('payments', 'pickupOrders'));
    }

    public function completePickup(Penjualan $penjualan): RedirectResponse
    {
        if (
            $penjualan->jenis_pengiriman !== 'ambil_di_toko'
            || $penjualan->status_pengiriman !== 'SIAP_DIAMBIL'
            || $penjualan->status_pembayaran !== 'TERBAYAR'
        ) {
            throw ValidationException::withMessages([
                'order' => 'Pesanan harus berstatus siap diambil dan pembayarannya lunas.',
            ]);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($penjualan): void {
            $sale = Penjualan::query()
                ->whereKey($penjualan->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if (
                $sale->jenis_pengiriman !== 'ambil_di_toko'
                || $sale->status_pengiriman !== 'SIAP_DIAMBIL'
                || $sale->status_pembayaran !== 'TERBAYAR'
            ) {
                throw ValidationException::withMessages([
                    'order' => 'Status pesanan sudah berubah. Muat ulang halaman.',
                ]);
            }

            $sale->update(['status_pengiriman' => 'SELESAI']);
            OrderEvent::query()->create([
                'id_penjualan' => $sale->id_penjualan,
                'event_type' => 'DELIVERY_STATUS',
                'status' => 'SELESAI',
                'message' => 'Pesanan telah diambil di toko.',
                'actor_type' => Auth::guard('employee')->check() ? 'employee' : 'owner',
                'actor_id' => (string) (Auth::guard('employee')->id() ?? Auth::guard('owner')->id()),
                'created_at' => now(),
            ]);
        });

        return back()->with('status', 'Pesanan ditandai selesai diambil.');
    }

    public function proof(CustomerPayment $payment)
    {
        abort_unless($payment->proof_path, 404);
        abort_unless(Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->download($payment->proof_path);
    }

    public function update(
        Request $request,
        CustomerPayment $payment,
        CustomerOrderService $orders
    ): RedirectResponse {
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['nullable', 'required_if:action,reject', 'string', 'max:255'],
        ]);

        if (Auth::guard('employee')->check()) {
            $reviewerType = 'employee';
            $reviewerId = (string) Auth::guard('employee')->id();
        } else {
            $reviewerType = 'owner';
            $reviewerId = (string) Auth::guard('owner')->id();
        }

        if ($data['action'] === 'approve') {
            $sale = $payment->penjualan()->firstOrFail();
            if ($payment->method === 'cod' && $sale->status_pengiriman !== 'SELESAI') {
                throw ValidationException::withMessages([
                    'payment' => 'Pembayaran COD baru dapat diverifikasi setelah pesanan selesai dikirim.',
                ]);
            }
            $canVerifyTransfer = $payment->method === 'bank_transfer'
                && $payment->status === 'AWAITING_VERIFICATION';
            $canVerifyCash = in_array($payment->method, ['store_cash', 'cod'], true)
                && $payment->status === 'PENDING';
            if (! $canVerifyTransfer && ! $canVerifyCash) {
                throw ValidationException::withMessages([
                    'payment' => 'Pembayaran ini tidak dapat diverifikasi secara manual.',
                ]);
            }
            $orders->markPaid($payment, null, $reviewerType, $reviewerId);
        } else {
            $orders->rejectManualPayment(
                $payment,
                $reviewerType,
                $reviewerId,
                $data['rejection_reason'] ?? null
            );
        }

        return redirect()
            ->back()
            ->with('status', $data['action'] === 'approve'
                ? 'Pembayaran berhasil diverifikasi.'
                : 'Bukti pembayaran ditolak.');
    }
}
