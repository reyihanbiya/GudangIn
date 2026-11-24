<?php

namespace App\Services;

use App\Models\DetailPenjualan;
use App\Models\Penjualan;
use App\Models\Persediaan;
use App\Models\XenditPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PenjualanPaymentSettlementService
{
    public function capture(XenditPayment $candidate, ?string $paymentId): void
    {
        DB::transaction(function () use ($candidate, $paymentId): void {
            $payment = XenditPayment::query()
                ->whereKey($candidate->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($payment->status, ['SUCCEEDED', 'PAID_STOCK_CONFLICT'], true)) {
                return;
            }

            if ($payment->isSimulation()) {
                if ($payment->status !== 'PENDING') {
                    return;
                }

                $this->changeStock($payment, true);
            } elseif ($payment->status !== 'PENDING') {
                if (! $this->tryReserveStock($payment, $paymentId)) {
                    return;
                }
            }

            $sale = Penjualan::query()->create([
                'id_penjualan' => CodeGeneratorService::generate(
                    'penjualan',
                    Carbon::parse($payment->tanggal)->format('ym')
                ),
                'id_pelanggan' => $payment->id_pelanggan,
                'id_pegawai' => $payment->id_pegawai,
                'tanggal' => $payment->tanggal,
            ]);

            foreach ($payment->items as $item) {
                DetailPenjualan::query()->create([
                    'id_penjualan' => $sale->id_penjualan,
                    'id_persediaan' => $item['id_persediaan'],
                    'satuan' => $item['satuan'],
                    'harga_satuan' => $item['harga_satuan'],
                    'jumlah_barang' => $item['jumlah_barang'],
                ]);
            }

            $payment->update([
                'status' => 'SUCCEEDED',
                'payment_id' => $paymentId,
                'id_penjualan' => $sale->id_penjualan,
            ]);
        });
    }

    public function failOrExpire(XenditPayment $candidate, string $status): void
    {
        if (! in_array($status, ['FAILED', 'EXPIRED'], true)) {
            throw new \InvalidArgumentException('Unsupported payment status.');
        }

        DB::transaction(function () use ($candidate, $status): void {
            $payment = XenditPayment::query()
                ->whereKey($candidate->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status !== 'PENDING') {
                return;
            }

            if (! $payment->isSimulation()) {
                $this->changeStock($payment, false);
            }

            $payment->update(['status' => $status]);
        });
    }

    private function tryReserveStock(XenditPayment $payment, ?string $paymentId): bool
    {
        $quantities = $this->quantitiesByInventory($payment);
        $inventories = [];

        foreach ($quantities as $inventoryId => $quantity) {
            $inventory = Persediaan::query()
                ->where('id_persediaan', $inventoryId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($inventory->stok < $quantity) {
                $payment->update([
                    'status' => 'PAID_STOCK_CONFLICT',
                    'payment_id' => $paymentId,
                ]);
                Log::critical('Captured Xendit payment could not reclaim its expired stock reservation.', [
                    'payment_request_id' => $payment->payment_request_id,
                    'inventory_id' => $inventoryId,
                ]);

                return false;
            }

            $inventories[] = [$inventory, $quantity];
        }

        foreach ($inventories as [$inventory, $quantity]) {
            $inventory->decrement('stok', $quantity);
        }

        return true;
    }

    private function changeStock(XenditPayment $payment, bool $decrement): void
    {
        foreach ($this->quantitiesByInventory($payment) as $inventoryId => $quantity) {
            $inventory = Persediaan::query()
                ->where('id_persediaan', $inventoryId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($decrement && $inventory->stok < $quantity) {
                throw ValidationException::withMessages([
                    'payment' => "Stok {$inventory->nama_barang} berubah. Buat checkout baru untuk melanjutkan simulasi.",
                ]);
            }

            $decrement
                ? $inventory->decrement('stok', $quantity)
                : $inventory->increment('stok', $quantity);
        }
    }

    private function quantitiesByInventory(XenditPayment $payment): array
    {
        $quantities = [];
        foreach ($payment->items as $item) {
            $inventoryId = $item['id_persediaan'];
            $quantities[$inventoryId] = ($quantities[$inventoryId] ?? 0) + (float) $item['jumlah_barang'];
        }
        ksort($quantities);

        return $quantities;
    }
}
