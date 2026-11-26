<?php

namespace App\Http\Controllers;

use App\Models\CourierLocation;
use App\Models\Pegawai;
use App\Models\Penjualan;
use App\Services\CustomerOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CourierController extends Controller
{
    public function index(): View
    {
        $courier = $this->courier();
        $orders = Penjualan::query()
            ->with(['pelanggan', 'detailPenjualan.persediaan'])
            ->where('jenis_pengiriman', 'pengiriman')
            ->whereNotIn('status_pengiriman', ['SELESAI', 'DIBATALKAN'])
            ->where(function ($query) use ($courier): void {
                $query->whereNull('id_kurir')
                    ->orWhere('id_kurir', $courier->id_pegawai);
            })
            ->orderByRaw('CASE WHEN id_kurir IS NULL THEN 0 ELSE 1 END')
            ->orderBy('tanggal')
            ->paginate(20);

        return view('courier.index', compact('orders', 'courier'));
    }

    public function claim(Penjualan $penjualan): RedirectResponse
    {
        $courier = $this->courier();
        DB::transaction(function () use ($penjualan, $courier): void {
            $sale = Penjualan::query()
                ->whereKey($penjualan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $sale->jenis_pengiriman !== 'pengiriman'
                || in_array($sale->status_pengiriman, ['SELESAI', 'DIBATALKAN'], true)
                || ($sale->id_kurir !== null && $sale->id_kurir !== $courier->id_pegawai)
            ) {
                abort(409, 'Pesanan ini tidak dapat diambil oleh kurir.');
            }

            if ($sale->id_kurir === null) {
                $sale->update(['id_kurir' => $courier->id_pegawai]);
                app(CustomerOrderService::class)->recordEvent(
                    $sale,
                    'COURIER_ASSIGNED',
                    $sale->status_pengiriman,
                    'Kurir telah ditugaskan untuk pesanan.',
                    'courier',
                    $courier->id_pegawai
                );
            }
        });

        return back()->with('status', 'Pesanan berhasil ditugaskan kepada Anda.');
    }

    public function updateStatus(
        Request $request,
        Penjualan $penjualan,
        CustomerOrderService $orders
    ): RedirectResponse {
        $data = $request->validate([
            'status_pengiriman' => ['required', 'in:DISIAPKAN,DALAM_PENGIRIMAN,SIAP_DIAMBIL,SELESAI'],
        ]);

        $orders->updateDeliveryStatus(
            $penjualan,
            $data['status_pengiriman'],
            $this->courier()->id_pegawai
        );

        return back()->with('status', 'Status pengiriman berhasil diperbarui.');
    }

    public function updateLocation(Request $request, Penjualan $penjualan): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);
        $courier = $this->courier();

        DB::transaction(function () use ($penjualan, $data, $courier): void {
            $sale = Penjualan::query()
                ->whereKey($penjualan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $sale->id_kurir === $courier->id_pegawai
                && $sale->status_pengiriman === 'DALAM_PENGIRIMAN',
                403
            );

            CourierLocation::query()->updateOrCreate(
                ['id_penjualan' => $sale->id_penjualan],
                [
                    'id_kurir' => $courier->id_pegawai,
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'recorded_at' => now(),
                ]
            );
        });

        return response()->json(['updated' => true]);
    }

    private function courier(): Pegawai
    {
        /** @var Pegawai $employee */
        $employee = Auth::guard('employee')->user();
        abort_unless(mb_strtolower(trim((string) $employee->jabatan)) === 'kurir', 403);

        return $employee;
    }
}
