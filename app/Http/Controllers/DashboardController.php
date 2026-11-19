<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\Persediaan;
use App\Models\Penjualan;
use App\Models\Views\LaporanPembelianView;
use App\Models\Views\LaporanPenjualanView;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function employee(): View
    {
        $employee = Auth::guard('employee')->user();

        return view('dashboard.index', array_merge($this->dashboardData(), [
            'role' => 'Pegawai',
            'name' => $employee->nama_pegawai,
        ]));
    }

    public function owner(): View
    {
        $owner = Auth::guard('owner')->user();

        return view('dashboard.index', array_merge($this->dashboardData(true), [
            'role' => 'Owner',
            'name' => $owner->nama_owner,
        ]));
    }

    private function dashboardData(bool $isOwner = false): array
    {
        $today = today();
        $yesterday = $today->copy()->subDay();
        $weekStart = $today->copy()->subDays(6);
        $todayString = $today->toDateString();
        $yesterdayString = $yesterday->toDateString();
        $weekStartString = $weekStart->toDateString();

        $dailyTotals = DB::table('detail_penjualan as detail')
            ->join('penjualan as sale', 'sale.id_penjualan', '=', 'detail.id_penjualan')
            ->whereBetween('sale.tanggal', [$weekStartString, $todayString])
            ->selectRaw('sale.tanggal as sale_date, SUM(detail.subtotal) as total')
            ->groupBy('sale.tanggal')
            ->get()
            ->keyBy('sale_date');

        $salesTrend = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $dailyTotals): array {
            $date = $weekStart->copy()->addDays($offset);
            $sale = $dailyTotals->get($date->toDateString());

            return [
                'label' => $date->translatedFormat('D'),
                'date' => $date->translatedFormat('d M'),
                'total' => (float) ($sale->total ?? 0),
            ];
        });

        $maxDailyTotal = max(0, (float) $salesTrend->max('total'));
        $salesTrend = $salesTrend->map(function (array $day) use ($maxDailyTotal): array {
            $day['bar_height'] = $maxDailyTotal > 0 && $day['total'] > 0
                ? max(4, (int) round(($day['total'] / $maxDailyTotal) * 100))
                : 0;
            $day['value_label'] = $this->formatCompactAmount($day['total']);

            return $day;
        });

        $todaySales = (float) ($dailyTotals->get($todayString)->total ?? 0);
        $yesterdaySales = (float) ($dailyTotals->get($yesterdayString)->total ?? 0);
        $todayTransactions = Penjualan::query()->whereDate('tanggal', $todayString)->count();
        $yesterdayTransactions = Penjualan::query()->whereDate('tanggal', $yesterdayString)->count();
        $availableItems = Persediaan::query()->where('stok', '>', 0)->count();
        $totalItems = Persediaan::query()->count();
        $todayPurchases = (float) LaporanPembelianView::query()
            ->whereDate('tanggal', $todayString)
            ->sum('total_pembelian');
        $todayPurchaseCount = Pembelian::query()->whereDate('tanggal', $todayString)->count();

        $stats = [
            [
                'label' => 'Total penjualan',
                'value' => $this->formatCurrency($todaySales),
                'detail' => $this->amountComparison($todaySales, $yesterdaySales),
                'icon' => '↗',
                'route' => $isOwner ? 'owner.reports.sales' : null,
            ],
            [
                'label' => 'Jumlah transaksi',
                'value' => number_format($todayTransactions, 0, ',', '.'),
                'detail' => $this->countComparison($todayTransactions, $yesterdayTransactions),
                'icon' => '▤',
            ],
            [
                'label' => 'Barang tersedia',
                'value' => number_format($availableItems, 0, ',', '.'),
                'detail' => number_format($availableItems, 0, ',', '.').' dari '.number_format($totalItems, 0, ',', '.').' jenis barang memiliki stok',
                'icon' => '◇',
            ],
            [
                'label' => 'Total pembelian',
                'value' => $this->formatCurrency($todayPurchases),
                'detail' => number_format($todayPurchaseCount, 0, ',', '.').' nota pembelian hari ini',
                'icon' => '▤',
                'route' => $isOwner ? 'owner.reports.purchases' : null,
            ],
        ];

        $lowStockForecast = $this->predictedLowStock($weekStartString, $todayString);

        return [
            'stats' => $stats,
            'salesTrend' => $salesTrend,
            'sevenDaySalesTotal' => (float) $salesTrend->sum('total'),
            'lowStockItems' => $lowStockForecast['items'],
            'lowStockCount' => $lowStockForecast['count'],
            'stockForecastDays' => 7,
        ];
    }

    private function predictedLowStock(string $startDate, string $endDate): array
    {
        $salesByItem = DB::table('detail_penjualan as detail')
            ->join('penjualan as sale', 'sale.id_penjualan', '=', 'detail.id_penjualan')
            ->join('persediaan as item', 'item.id_persediaan', '=', 'detail.id_persediaan')
            ->whereColumn('detail.satuan', 'item.satuan')
            ->whereBetween('sale.tanggal', [$startDate, $endDate])
            ->select('detail.id_persediaan')
            ->selectRaw('SUM(detail.jumlah_barang) as sold_quantity')
            ->groupBy('detail.id_persediaan')
            ->get()
            ->keyBy('id_persediaan');

        $items = Persediaan::query()
            ->get(['id_persediaan', 'nama_barang', 'stok', 'satuan'])
            ->map(function (Persediaan $item) use ($salesByItem): ?array {
                $soldQuantity = (float) ($salesByItem->get($item->id_persediaan)->sold_quantity ?? 0);
                if ($soldQuantity <= 0) {
                    return null;
                }

                $averageDailySales = $soldQuantity / 7;
                $daysRemaining = max(0, (float) $item->stok) / $averageDailySales;

                if ($daysRemaining > 7) {
                    return null;
                }

                return [
                    'name' => $item->nama_barang,
                    'stock' => $item->stok_dengan_satuan,
                    'days_remaining' => $daysRemaining,
                    'forecast' => (float) $item->stok <= 0
                        ? 'Stok sudah habis'
                        : ($daysRemaining <= 1
                            ? 'Diperkirakan habis dalam 1 hari'
                            : 'Diperkirakan habis dalam '.number_format($daysRemaining, 1, ',', '.').' hari'),
                ];
            })
            ->filter()
            ->sortBy('days_remaining')
            ->values();

        return [
            'count' => $items->count(),
            'items' => $items->take(5)->values(),
        ];
    }

    private function formatCurrency(float $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }

    private function formatCompactAmount(float $amount): string
    {
        if ($amount >= 1000000) {
            return number_format($amount / 1000000, 1, ',', '.').' jt';
        }

        if ($amount >= 1000) {
            return number_format($amount / 1000, 1, ',', '.').' rb';
        }

        return number_format($amount, 0, ',', '.');
    }

    private function amountComparison(float $current, float $previous): string
    {
        if ($previous <= 0) {
            return $current > 0 ? 'Ada penjualan hari ini; belum ada kemarin' : 'Belum ada penjualan hari ini';
        }

        $change = (($current - $previous) / $previous) * 100;
        $direction = $change >= 0 ? 'Naik' : 'Turun';

        return $direction.' '.number_format(abs($change), 1, ',', '.').'% dari kemarin';
    }

    private function countComparison(int $current, int $previous): string
    {
        $difference = $current - $previous;
        if ($difference === 0) {
            return 'Sama dengan kemarin';
        }

        return ($difference > 0 ? '+' : '').number_format($difference, 0, ',', '.').' transaksi dari kemarin';
    }
}
