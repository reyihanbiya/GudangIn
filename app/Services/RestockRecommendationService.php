<?php

namespace App\Services;

use App\Models\Persediaan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RestockRecommendationService
{
    public function forPurchaseForm(): Collection
    {
        $today = today();
        $salesByProduct = DB::table('detail_penjualan as detail')
            ->join('penjualan as sale', 'sale.id_penjualan', '=', 'detail.id_penjualan')
            ->whereBetween('sale.tanggal', [
                $today->copy()->subDays(29)->toDateString(),
                $today->toDateString(),
            ])
            ->selectRaw('detail.id_persediaan as id_persediaan, SUM(detail.jumlah_barang) as jumlah_terjual')
            ->groupBy('detail.id_persediaan')
            ->pluck('jumlah_terjual', 'id_persediaan');

        return Persediaan::query()
            ->where('stok_minimum', '>', 0)
            ->whereColumn('stok', '<', 'stok_minimum')
            ->orderBy('nama_barang')
            ->get()
            ->map(function (Persediaan $product) use ($salesByProduct): array {
                $soldInThirtyDays = (float) ($salesByProduct->get($product->id_persediaan) ?? 0);
                $targetStock = max((float) $product->stok_minimum, $soldInThirtyDays);

                return [
                    'product' => $product,
                    'sold_in_thirty_days' => $soldInThirtyDays,
                    'suggested_quantity' => round(max(0, $targetStock - (float) $product->stok), 3),
                ];
            });
    }
}
