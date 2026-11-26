<?php

namespace App\Http\Controllers;

use App\Models\CustomerPayment;
use App\Models\Pegawai;
use App\Models\Penjualan;
use App\Models\Persediaan;
use App\Services\CustomerOrderService;
use App\Services\MidtransPaymentException;
use App\Services\MidtransPaymentService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class CustomerOrderController extends Controller
{
    public function checkout(): View|RedirectResponse
    {
        $cart = session()->get('customer_cart', []);
        if ($cart === []) {
            return redirect()->route('customer.cart.index')
                ->with('error', 'Keranjang masih kosong.');
        }

        $products = Persediaan::query()
            ->whereIn('id_persediaan', array_keys($cart))
            ->get()
            ->keyBy('id_persediaan');
        $items = [];
        $subtotal = 0.0;

        foreach ($cart as $id => $quantity) {
            $product = $products->get($id);
            if (! $product || $product->harga_jual === null || (float) $product->harga_jual <= 0) {
                continue;
            }

            $lineTotal = (float) $product->harga_jual * (float) $quantity;
            $subtotal += $lineTotal;
            $items[] = [
                'product' => $product,
                'quantity' => (float) $quantity,
                'subtotal' => $lineTotal,
            ];
        }

        if ($items === []) {
            return redirect()->route('customer.cart.index')
                ->with('error', 'Produk di keranjang sudah tidak tersedia.');
        }

        return view('customer.checkout', [
            'items' => $items,
            'subtotal' => $subtotal,
            'deliveryFee' => (float) config('services.customer_store.delivery_fee', 0),
            'midtransAvailable' => app(MidtransPaymentService::class)->isConfigured(),
            'mapsApiKey' => config('services.google_maps.api_key'),
            'storeLatitude' => config('services.google_maps.store_latitude'),
            'storeLongitude' => config('services.google_maps.store_longitude'),
        ]);
    }

    public function store(
        Request $request,
        CustomerOrderService $orders,
        MidtransPaymentService $midtrans
    ): RedirectResponse {
        $data = $request->validate([
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'no_hp' => ['required', 'string', 'max:20', 'regex:/^[0-9+().\\-\\s]{8,20}$/'],
            'jenis_pengiriman' => ['required', 'in:pengiriman,ambil_di_toko'],
            'alamat' => ['required_if:jenis_pengiriman,pengiriman', 'nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'metode_pembayaran' => ['required', 'in:bank_transfer,qris,transfer_gateway,store_cash,cod'],
        ]);
        $data['no_hp'] = $orders->normalizePhone($data['no_hp']);
        if (strlen($data['no_hp']) < 8 || strlen($data['no_hp']) > 20) {
            throw ValidationException::withMessages([
                'no_hp' => 'Nomor WhatsApp harus berisi 8 sampai 20 digit.',
            ]);
        }

        if ($data['jenis_pengiriman'] === 'ambil_di_toko' && $data['metode_pembayaran'] === 'cod') {
            throw ValidationException::withMessages([
                'metode_pembayaran' => 'COD hanya tersedia untuk pesanan yang dikirim.',
            ]);
        }

        if (in_array($data['metode_pembayaran'], ['qris', 'transfer_gateway'], true) && ! $midtrans->isConfigured()) {
            throw ValidationException::withMessages([
                'metode_pembayaran' => 'Pembayaran otomatis belum aktif. Pilih transfer manual atau hubungi toko.',
            ]);
        }

        $cart = session()->get('customer_cart', []);
        if ($cart === []) {
            return redirect()->route('customer.cart.index')
                ->with('error', 'Keranjang masih kosong.');
        }

        $employee = Pegawai::query()->orderBy('id_pegawai')->first();
        if (! $employee) {
            abort(503, 'Akun pegawai belum tersedia. Toko belum siap menerima pesanan online.');
        }

        [$sale, $payment] = $orders->placeOrder($data, $cart, $employee);

        if ($payment->provider === 'midtrans') {
            try {
                $checkout = $midtrans->createCheckout($payment, $sale);
                $payment->update([
                    'checkout_url' => $checkout['redirect_url'],
                    'metadata' => ['snap_token' => $checkout['token']],
                ]);
            } catch (ConnectionException $exception) {
                report($exception);
                session()->forget('customer_cart');
                session()->put(
                    'customer_order_access.'.$sale->id_penjualan,
                    hash('sha256', $data['no_hp'])
                );

                return redirect()
                    ->route('customer.orders.show', $sale)
                    ->with('error', 'Konfirmasi dari payment gateway belum diterima. Jangan membuat pembayaran kedua; periksa status pesanan atau hubungi toko.');
            } catch (MidtransPaymentException $exception) {
                report($exception);
                $orders->failGatewayPayment($payment, 'FAILED');

                return back()
                    ->withInput($request->except(['no_hp']))
                    ->withErrors(['payment' => $exception->getMessage()]);
            }
        }

        session()->forget('customer_cart');
        session()->put(
            'customer_order_access.'.$sale->id_penjualan,
            hash('sha256', $data['no_hp'])
        );

        return redirect()->route('customer.orders.show', $sale);
    }

    public function tracking(): View
    {
        return view('customer.tracking');
    }

    public function lookup(Request $request, CustomerOrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'kode_pesanan' => ['required', 'string', 'max:30'],
            'no_hp' => ['required', 'string', 'max:20'],
        ]);
        $sale = Penjualan::query()
            ->with('pelanggan')
            ->where(function ($query) use ($data): void {
                $query->where('id_penjualan', $data['kode_pesanan'])
                    ->orWhere('id_pengiriman', $data['kode_pesanan']);
            })
            ->first();

        $providedPhone = $orders->normalizePhone($data['no_hp']);
        $storedPhone = $sale?->telepon_penerima
            ? $orders->normalizePhone($sale->telepon_penerima)
            : ($sale?->pelanggan?->no_hp ? $orders->normalizePhone($sale->pelanggan->no_hp) : '');

        if (! $sale || $storedPhone === '' || ! hash_equals($storedPhone, $providedPhone)) {
            return back()
                ->withErrors(['kode_pesanan' => 'Kode pesanan atau nomor WhatsApp tidak cocok.'])
                ->onlyInput('kode_pesanan');
        }

        session()->put(
            'customer_order_access.'.$sale->id_penjualan,
            hash('sha256', $providedPhone)
        );

        return redirect()->route('customer.orders.show', $sale);
    }

    public function show(string $penjualan, CustomerOrderService $orders): View
    {
        $sale = $this->authorizedOrder($penjualan, $orders);
        $sale->load([
            'pelanggan',
            'detailPenjualan.persediaan',
            'payments',
            'orderEvents',
            'courierLocation',
        ]);

        return view('customer.orders.show', [
            'sale' => $sale,
            'mapsApiKey' => config('services.google_maps.api_key'),
            'storeLatitude' => config('services.google_maps.store_latitude'),
            'storeLongitude' => config('services.google_maps.store_longitude'),
            'bankDetails' => [
                'name' => config('services.customer_store.bank_name'),
                'account' => config('services.customer_store.bank_account'),
                'holder' => config('services.customer_store.bank_holder'),
            ],
        ]);
    }

    public function uploadProof(
        Request $request,
        string $penjualan,
        CustomerOrderService $orders
    ): RedirectResponse {
        $sale = $this->authorizedOrder($penjualan, $orders);
        $validated = $request->validate([
            'bukti_pembayaran' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ]);
        $payment = $sale->payments()
            ->where('method', 'bank_transfer')
            ->latest('id')
            ->first();

        if (! $payment || ! in_array($payment->status, ['AWAITING_PAYMENT', 'REJECTED'], true)) {
            throw ValidationException::withMessages([
                'bukti_pembayaran' => 'Pesanan ini tidak sedang menunggu bukti transfer.',
            ]);
        }

        $path = $validated['bukti_pembayaran']->store(
            'customer-payment-proofs/'.$sale->id_penjualan,
            'local'
        );
        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Bukti pembayaran gagal disimpan.');
        }

        $previousPath = $payment->proof_path;
        DB::transaction(function () use ($payment, $sale, $path, $orders): void {
            $lockedPayment = CustomerPayment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedSale = Penjualan::query()
                ->whereKey($sale->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedPayment->status, ['AWAITING_PAYMENT', 'REJECTED'], true)) {
                throw ValidationException::withMessages([
                    'bukti_pembayaran' => 'Status pembayaran sudah berubah. Muat ulang halaman.',
                ]);
            }

            $lockedPayment->update([
                'proof_path' => $path,
                'status' => 'AWAITING_VERIFICATION',
                'verified_by' => null,
                'verified_by_type' => null,
                'verified_at' => null,
                'metadata' => array_diff_key($lockedPayment->metadata ?? [], ['rejection_reason' => true]),
            ]);
            $lockedSale->update(['status_pembayaran' => 'MENUNGGU_VERIFIKASI']);

            $orders->recordEvent(
                $lockedSale,
                'PAYMENT_STATUS',
                'AWAITING_VERIFICATION',
                'Bukti transfer diunggah dan menunggu verifikasi toko.',
                'customer',
                null,
                ['payment_id' => $lockedPayment->id]
            );
        });

        if ($previousPath && $previousPath !== $path) {
            Storage::disk('local')->delete($previousPath);
        }

        return back()->with('success', 'Bukti pembayaran terkirim. Toko akan memverifikasinya.');
    }

    public function courierLocation(
        string $penjualan,
        CustomerOrderService $orders
    ): JsonResponse {
        $sale = $this->authorizedOrder($penjualan, $orders);
        if ($sale->status_pengiriman !== 'DALAM_PENGIRIMAN') {
            return response()->json([
                'status' => $sale->status_pengiriman,
                'location' => null,
            ]);
        }

        $location = $sale->courierLocation;
        if (! $location || $location->recorded_at->lt(now()->subMinutes(3))) {
            return response()->json([
                'status' => $sale->status_pengiriman,
                'location' => null,
            ]);
        }

        return response()->json([
            'status' => $sale->status_pengiriman,
            'location' => [
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
                'recorded_at' => $location->recorded_at->toIso8601String(),
            ],
        ]);
    }

    private function authorizedOrder(string $id, CustomerOrderService $orders): Penjualan
    {
        $sale = Penjualan::query()
            ->with('pelanggan')
            ->whereKey($id)
            ->firstOrFail();
        $sessionHash = session('customer_order_access.'.$sale->id_penjualan);
        $storedPhone = $sale->telepon_penerima
            ? $orders->normalizePhone($sale->telepon_penerima)
            : ($sale->pelanggan?->no_hp ? $orders->normalizePhone($sale->pelanggan->no_hp) : '');

        abort_unless(
            is_string($sessionHash)
            && $storedPhone !== ''
            && hash_equals($sessionHash, hash('sha256', $storedPhone)),
            403
        );

        return $sale;
    }
}
