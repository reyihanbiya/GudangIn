<?php

namespace App\Services;

use App\Models\CustomerPayment;
use App\Models\DetailPenjualan;
use App\Models\OrderEvent;
use App\Models\Pegawai;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\Persediaan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerOrderService
{
    public function placeOrder(array $data, array $cart, Pegawai $employee): array
    {
        return DB::transaction(function () use ($data, $cart, $employee): array {
            $phone = $this->normalizePhone($data['no_hp']);
            $customer = Pelanggan::query()
                ->where('no_hp', $phone)
                ->lockForUpdate()
                ->first();

            if ($customer) {
                $customerData = [
                    'nama_pelanggan' => $data['nama_pelanggan'],
                    'no_hp' => $phone,
                ];
                if ($data['jenis_pengiriman'] === 'pengiriman' && filled($data['alamat'] ?? null)) {
                    $customerData['alamat'] = $data['alamat'];
                }
                if (
                    $data['jenis_pengiriman'] === 'pengiriman'
                    && isset($data['latitude'], $data['longitude'])
                ) {
                    $customerData['latitude'] = $data['latitude'];
                    $customerData['longitude'] = $data['longitude'];
                }
                $customer->update($customerData);
            } else {
                $customer = Pelanggan::query()->create([
                    'id_pelanggan' => CodeGeneratorService::generate('pelanggan'),
                    'nama_pelanggan' => $data['nama_pelanggan'],
                    'no_hp' => $phone,
                    'alamat' => $data['jenis_pengiriman'] === 'pengiriman'
                        ? ($data['alamat'] ?? null)
                        : null,
                    'latitude' => $data['jenis_pengiriman'] === 'pengiriman'
                        ? ($data['latitude'] ?? null)
                        : null,
                    'longitude' => $data['jenis_pengiriman'] === 'pengiriman'
                        ? ($data['longitude'] ?? null)
                        : null,
                ]);
            }

            $items = [];
            $total = 0.0;
            $inventoryIds = array_keys($cart);
            sort($inventoryIds);

            foreach ($inventoryIds as $inventoryId) {
                $quantity = (float) $cart[$inventoryId];
                $inventory = Persediaan::query()
                    ->where('id_persediaan', $inventoryId)
                    ->lockForUpdate()
                    ->first();

                if (! $inventory || $inventory->harga_jual === null || (float) $inventory->harga_jual <= 0) {
                    throw ValidationException::withMessages([
                        'cart' => 'Salah satu produk sudah tidak tersedia untuk dibeli.',
                    ]);
                }

                if ($quantity <= 0 || $inventory->stok < $quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "Stok {$inventory->nama_barang} tidak mencukupi. Perbarui keranjang dan coba lagi.",
                    ]);
                }

                $unitPrice = (float) $inventory->harga_jual;
                $total += $unitPrice * $quantity;
                $items[] = [
                    'inventory' => $inventory,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ];
            }

            if ($items === []) {
                throw ValidationException::withMessages([
                    'cart' => 'Keranjang kosong. Tambahkan produk sebelum checkout.',
                ]);
            }

            $deliveryFee = $data['jenis_pengiriman'] === 'pengiriman'
                ? (float) config('services.customer_store.delivery_fee', 0)
                : 0.0;
            $amount = round($total + $deliveryFee, 2);

            $sale = Penjualan::query()->create([
                'id_penjualan' => CodeGeneratorService::generate(
                    'penjualan',
                    Carbon::now()->format('ym')
                ),
                'id_pengiriman' => CodeGeneratorService::generate('pengiriman'),
                'id_pelanggan' => $customer->id_pelanggan,
                'id_pegawai' => $employee->id_pegawai,
                'tanggal' => today()->toDateString(),
                'jenis_pengiriman' => $data['jenis_pengiriman'],
                'status_pengiriman' => $data['jenis_pengiriman'] === 'ambil_di_toko'
                    ? 'SIAP_DIAMBIL'
                    : 'DIPROSES',
                'status_pembayaran' => 'MENUNGGU_PEMBAYARAN',
                'metode_pembayaran' => $data['metode_pembayaran'],
                'nama_penerima' => $data['nama_pelanggan'],
                'telepon_penerima' => $phone,
                'alamat_pengiriman' => $data['jenis_pengiriman'] === 'pengiriman'
                    ? $data['alamat']
                    : null,
                'latitude_pengiriman' => $data['jenis_pengiriman'] === 'pengiriman'
                    ? ($data['latitude'] ?? null)
                    : null,
                'longitude_pengiriman' => $data['jenis_pengiriman'] === 'pengiriman'
                    ? ($data['longitude'] ?? null)
                    : null,
                'biaya_pengiriman' => $deliveryFee,
            ]);

            foreach ($items as $item) {
                /** @var Persediaan $inventory */
                $inventory = $item['inventory'];
                $quantity = $item['quantity'];

                DetailPenjualan::query()->create([
                    'id_penjualan' => $sale->id_penjualan,
                    'id_persediaan' => $inventory->id_persediaan,
                    'satuan' => $inventory->satuan,
                    'harga_satuan' => $item['unit_price'],
                    'jumlah_barang' => $quantity,
                ]);

                $inventory->decrement('stok', $quantity);
            }

            $isGateway = in_array($data['metode_pembayaran'], ['qris', 'transfer_gateway'], true);
            if ($isGateway) {
                $amount = round($amount);
            }
            $payment = CustomerPayment::query()->create([
                'id_penjualan' => $sale->id_penjualan,
                'method' => $data['metode_pembayaran'],
                'provider' => $isGateway ? 'midtrans' : null,
                'reference_id' => (string) Str::uuid(),
                'provider_order_id' => $isGateway
                    ? 'GI-'.Str::upper(Str::random(32))
                    : null,
                'amount' => $amount,
                'status' => $data['metode_pembayaran'] === 'bank_transfer'
                    ? 'AWAITING_PAYMENT'
                    : 'PENDING',
            ]);

            $this->recordEvent(
                $sale,
                'ORDER_CREATED',
                $sale->status_pengiriman,
                'Pesanan berhasil dibuat dan stok telah dipesan.',
                'customer'
            );
            $this->recordEvent(
                $sale,
                'PAYMENT_STATUS',
                $payment->status,
                'Menunggu pembayaran.',
                'system',
                null,
                ['payment_id' => $payment->id]
            );

            return [$sale, $payment];
        });
    }

    public function markPaid(
        CustomerPayment $candidate,
        ?string $providerTransactionId = null,
        ?string $reviewerType = null,
        ?string $reviewerId = null
    ): void {
        DB::transaction(function () use (
            $candidate,
            $providerTransactionId,
            $reviewerType,
            $reviewerId
        ): void {
            $payment = CustomerPayment::query()
                ->whereKey($candidate->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $sale = Penjualan::query()
                ->whereKey($payment->id_penjualan)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status === 'PAID') {
                return;
            }

            if (in_array($payment->status, ['FAILED', 'EXPIRED', 'CANCELLED', 'REFUNDED'], true)) {
                return;
            }

            $payment->update([
                'status' => 'PAID',
                'provider_transaction_id' => $providerTransactionId ?? $payment->provider_transaction_id,
                'verified_by_type' => $reviewerType,
                'verified_by' => $reviewerId,
                'verified_at' => $reviewerType ? now() : $payment->verified_at,
                'paid_at' => now(),
            ]);
            $sale->update(['status_pembayaran' => 'TERBAYAR']);

            $this->recordEvent(
                $sale,
                'PAYMENT_STATUS',
                'PAID',
                'Pembayaran berhasil dikonfirmasi.',
                $reviewerType ?? 'gateway',
                $reviewerId,
                ['payment_id' => $payment->id]
            );
        });
    }

    public function rejectManualPayment(
        CustomerPayment $candidate,
        string $reviewerType,
        string $reviewerId,
        ?string $reason = null
    ): void {
        DB::transaction(function () use ($candidate, $reviewerType, $reviewerId, $reason): void {
            $payment = CustomerPayment::query()
                ->whereKey($candidate->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $sale = Penjualan::query()
                ->whereKey($payment->id_penjualan)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->method !== 'bank_transfer' || $payment->status !== 'AWAITING_VERIFICATION') {
                throw ValidationException::withMessages([
                    'payment' => 'Pembayaran ini tidak sedang menunggu verifikasi bukti transfer.',
                ]);
            }

            $payment->update([
                'status' => 'REJECTED',
                'verified_by_type' => $reviewerType,
                'verified_by' => $reviewerId,
                'verified_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'rejection_reason' => $reason,
                ]),
            ]);
            $sale->update(['status_pembayaran' => 'MENUNGGU_PEMBAYARAN']);

            $this->recordEvent(
                $sale,
                'PAYMENT_STATUS',
                'REJECTED',
                $reason ? 'Bukti transfer ditolak: '.$reason : 'Bukti transfer ditolak. Silakan unggah ulang.',
                $reviewerType,
                $reviewerId,
                ['payment_id' => $payment->id]
            );
        });
    }

    public function failGatewayPayment(CustomerPayment $candidate, string $status): void
    {
        if (! in_array($status, ['FAILED', 'EXPIRED', 'CANCELLED'], true)) {
            throw new \InvalidArgumentException('Unsupported gateway payment status.');
        }

        DB::transaction(function () use ($candidate, $status): void {
            $payment = CustomerPayment::query()
                ->whereKey($candidate->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $sale = Penjualan::query()
                ->whereKey($payment->id_penjualan)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status !== 'PENDING' || $payment->provider !== 'midtrans') {
                return;
            }

            $details = DetailPenjualan::query()
                ->where('id_penjualan', $sale->id_penjualan)
                ->lockForUpdate()
                ->get();

            foreach ($details as $detail) {
                Persediaan::query()
                    ->where('id_persediaan', $detail->id_persediaan)
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->increment('stok', $detail->jumlah_barang);
            }

            $payment->update(['status' => $status]);
            $sale->update([
                'status_pembayaran' => $status,
                'status_pengiriman' => 'DIBATALKAN',
            ]);

            $this->recordEvent(
                $sale,
                'PAYMENT_STATUS',
                $status,
                'Pembayaran gagal atau kedaluwarsa; stok pesanan dikembalikan.',
                'gateway',
                null,
                ['payment_id' => $payment->id]
            );
        });
    }

    public function updateDeliveryStatus(
        Penjualan $candidate,
        string $status,
        string $courierId
    ): void {
        $allowedTransitions = [
            'DIPROSES' => ['DISIAPKAN', 'DALAM_PENGIRIMAN'],
            'DISIAPKAN' => ['DALAM_PENGIRIMAN', 'SIAP_DIAMBIL'],
            'SIAP_DIAMBIL' => ['SELESAI'],
            'DALAM_PENGIRIMAN' => ['SELESAI'],
        ];

        DB::transaction(function () use ($candidate, $status, $courierId, $allowedTransitions): void {
            $sale = Penjualan::query()
                ->whereKey($candidate->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($sale->id_kurir !== $courierId) {
                abort(403);
            }

            if (! in_array($status, $allowedTransitions[$sale->status_pengiriman] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status_pengiriman' => 'Status pengiriman tidak dapat diubah ke tahap tersebut.',
                ]);
            }

            $sale->update(['status_pengiriman' => $status]);
            $this->recordEvent(
                $sale,
                'DELIVERY_STATUS',
                $status,
                $this->deliveryStatusMessage($status),
                'courier',
                $courierId
            );
        });
    }

    public function recordEvent(
        Penjualan $sale,
        string $type,
        ?string $status,
        string $message,
        ?string $actorType = null,
        ?string $actorId = null,
        array $metadata = []
    ): void {
        OrderEvent::query()->create([
            'id_penjualan' => $sale->id_penjualan,
            'event_type' => $type,
            'status' => $status,
            'message' => $message,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'metadata' => $metadata === [] ? null : $metadata,
            'customer_payment_id' => $metadata['payment_id'] ?? null,
            'created_at' => now(),
        ]);
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    private function deliveryStatusMessage(string $status): string
    {
        return match ($status) {
            'DISIAPKAN' => 'Pesanan sedang disiapkan.',
            'DALAM_PENGIRIMAN' => 'Pesanan sedang dalam pengiriman.',
            'SIAP_DIAMBIL' => 'Pesanan siap diambil di toko.',
            'SELESAI' => 'Pesanan telah selesai.',
            default => 'Status pengiriman diperbarui.',
        };
    }
}
