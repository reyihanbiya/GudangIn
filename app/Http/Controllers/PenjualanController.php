<?php

namespace App\Http\Controllers;

use App\Models\DetailPenjualan;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\Persediaan;
use App\Models\XenditPayment;
use App\Services\CodeGeneratorService;
use App\Services\PenjualanPaymentSettlementService;
use App\Services\XenditPaymentException;
use App\Services\XenditPaymentService;
use Barryvdh\DomPDF\PDF as DompdfDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PenjualanController extends Controller
{
    public function index(): View
    {
        $products = Persediaan::query()
            ->where('stok', '>', 0)
            ->select(['id_persediaan', 'nama_barang', 'kategori_barang', 'satuan', 'stok', 'harga_jual'])
            ->orderBy('nama_barang')
            ->get();

        return view('transactions.cashier', compact('products'));
    }

    public function history(): View
    {
        $transactions = Penjualan::query()
            ->with(['pelanggan', 'pegawai'])
            ->withSum('detailPenjualan as total', 'subtotal')
            ->orderByDesc('tanggal')
            ->orderByDesc('id_penjualan')
            ->paginate(15);

        return view('transactions.sales-index', compact('transactions'));
    }

    public function deliveryNote(Penjualan $penjualan): Response
    {
        return $this->makeDeliveryNote($penjualan)->download(
            'surat-jalan-'.$penjualan->id_penjualan.'.pdf'
        );
    }

    public function previewDeliveryNote(Penjualan $penjualan): Response
    {
        return $this->makeDeliveryNote($penjualan)->stream(
            'surat-jalan-'.$penjualan->id_penjualan.'.pdf'
        );
    }

    private function makeDeliveryNote(Penjualan $penjualan): DompdfDocument
    {
        $penjualan->load(['pelanggan', 'detailPenjualan.persediaan']);

        return Pdf::loadView('transactions.delivery-note', compact('penjualan'))
            ->setPaper('a4', 'portrait');
    }

    public function create(): View
    {
        return view('transactions.form', [
            'type' => 'Penjualan',
            'action' => route('employee.penjualan.store'),
            'counterparts' => Pelanggan::query()->orderBy('nama_pelanggan')->get(),
            'products' => Persediaan::query()->orderBy('nama_barang')->get(),
            'date' => today()->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->saveTransaction($request);
    }

    public function cashierStore(Request $request): RedirectResponse
    {
        $data = $this->validateTransaction($request, 'harga_satuan');
        $employee = Auth::guard('employee')->user();
        $simulation = (bool) config('services.xendit.simulation');

        try {
            $payment = DB::transaction(function () use ($data, $employee, $simulation): XenditPayment {
                $customerId = $data['id_pelanggan'] ?? null;

                if (filled($data['pelanggan_baru']['nama_pelanggan'] ?? null)) {
                    $customer = Pelanggan::query()->create([
                        'id_pelanggan' => CodeGeneratorService::generate('pelanggan'),
                        'nama_pelanggan' => $data['pelanggan_baru']['nama_pelanggan'],
                        'no_hp' => $data['pelanggan_baru']['no_hp'] ?? null,
                        'alamat' => $data['pelanggan_baru']['alamat'] ?? null,
                    ]);
                    $customerId = $customer->id_pelanggan;
                }

                $items = [];
                $amount = 0;

                foreach ($data['items'] as $item) {
                    $inventory = Persediaan::query()
                        ->where('id_persediaan', $item['id_persediaan'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($inventory->stok < $item['jumlah_barang']) {
                        throw ValidationException::withMessages([
                            'items' => "Stok {$inventory->nama_barang} tidak mencukupi. Tersedia {$inventory->stok_dengan_satuan}.",
                        ]);
                    }

                    $unitPrice = $inventory->harga_jual;
                    if ($unitPrice === null || (float) $unitPrice <= 0) {
                        throw ValidationException::withMessages([
                            'items' => "Harga jual {$inventory->nama_barang} belum diatur di data persediaan.",
                        ]);
                    }

                    $quantity = (float) $item['jumlah_barang'];
                    $items[] = [
                        'id_persediaan' => $inventory->id_persediaan,
                        'satuan' => $inventory->satuan,
                        'harga_satuan' => $unitPrice,
                        'jumlah_barang' => $item['jumlah_barang'],
                    ];
                    $amount += (float) $unitPrice * $quantity;
                }

                $amount = round($amount, 2);
                if ($amount < 1 || $amount > 10000000) {
                    throw ValidationException::withMessages([
                        'items' => 'Total pembayaran QRIS harus antara Rp1 dan Rp10.000.000.',
                    ]);
                }

                $referenceId = (string) Str::uuid();
                $xenditPayment = $simulation
                    ? [
                        'payment_request_id' => 'sim-'.$referenceId,
                        'qr_string' => 'SIMULASI QRIS - TIDAK ADA PEMBAYARAN '.$referenceId,
                        'expires_at' => null,
                    ]
                    : app(XenditPaymentService::class)->createQrisPayment($referenceId, $amount);

                if (! $simulation) {
                    foreach ($items as $item) {
                        Persediaan::query()
                            ->where('id_persediaan', $item['id_persediaan'])
                            ->decrement('stok', $item['jumlah_barang']);
                    }
                }

                return XenditPayment::query()->create([
                    'id' => $referenceId,
                    'reference_id' => $referenceId,
                    'payment_request_id' => $xenditPayment['payment_request_id'],
                    'id_pelanggan' => $customerId,
                    'id_pegawai' => $employee->id_pegawai,
                    'tanggal' => $data['tanggal'],
                    'amount' => $amount,
                    'items' => $items,
                    'qr_string' => $xenditPayment['qr_string'],
                    'status' => 'PENDING',
                    'expires_at' => $xenditPayment['expires_at'],
                ]);
            });
        } catch (XenditPaymentException $exception) {
            return back()->withInput()->withErrors(['payment' => $exception->getMessage()]);
        } catch (ConnectionException $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'payment' => 'Tidak dapat terhubung ke Xendit. Periksa koneksi internet lalu coba lagi.',
            ]);
        }

        return redirect()->route('employee.penjualan.payment.show', $payment);
    }

    public function payment(XenditPayment $payment): View
    {
        $this->authorizePayment($payment);

        return view('transactions.qris-payment', compact('payment'));
    }

    public function paymentStatus(XenditPayment $payment): \Illuminate\Http\JsonResponse
    {
        $this->authorizePayment($payment);

        return response()->json([
            'status' => $payment->status,
            'sale_url' => $payment->id_penjualan
                ? route('employee.penjualan.show', $payment->id_penjualan)
                : null,
        ]);
    }

    public function simulatePayment(
        XenditPayment $payment,
        PenjualanPaymentSettlementService $settlement
    ): RedirectResponse {
        $this->authorizePayment($payment);
        abort_unless($payment->isSimulation(), 404);

        $settlement->capture($payment, 'sim-'.$payment->reference_id);
        $payment->refresh();

        if ($payment->status !== 'SUCCEEDED' || ! $payment->id_penjualan) {
            return back()->withErrors(['payment' => 'Simulasi pembayaran belum dapat diselesaikan.']);
        }

        return redirect()
            ->route('employee.penjualan.show', $payment->id_penjualan)
            ->with('status', 'Pembayaran simulasi berhasil. Tidak ada pembayaran sungguhan.');
    }

    private function authorizePayment(XenditPayment $payment): void
    {
        abort_unless(
            $payment->id_pegawai === Auth::guard('employee')->user()->id_pegawai,
            403
        );
    }

    private function saveTransaction(Request $request): RedirectResponse
    {
        $data = $this->validateTransaction($request, 'harga_satuan');
        $employee = Auth::guard('employee')->user();

        $transaction = DB::transaction(function () use ($data, $employee): Penjualan {
            $customerId = $data['id_pelanggan'] ?? null;

            if (filled($data['pelanggan_baru']['nama_pelanggan'] ?? null)) {
                $customer = Pelanggan::query()->create([
                    'id_pelanggan' => CodeGeneratorService::generate('pelanggan'),
                    'nama_pelanggan' => $data['pelanggan_baru']['nama_pelanggan'],
                    'no_hp' => $data['pelanggan_baru']['no_hp'] ?? null,
                    'alamat' => $data['pelanggan_baru']['alamat'] ?? null,
                ]);
                $customerId = $customer->id_pelanggan;
            }

            $transaction = Penjualan::query()->create([
                'id_penjualan' => CodeGeneratorService::generate(
                    'penjualan',
                    \Illuminate\Support\Carbon::parse($data['tanggal'])->format('ym')
                ),
                'id_pelanggan' => $customerId,
                'id_pegawai' => $employee->id_pegawai,
                'tanggal' => $data['tanggal'],
            ]);

            foreach ($data['items'] as $item) {
                $inventory = Persediaan::query()
                    ->where('id_persediaan', $item['id_persediaan'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($inventory->stok < $item['jumlah_barang']) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$inventory->nama_barang} tidak mencukupi. Tersedia {$inventory->stok_dengan_satuan}.",
                    ]);
                }

                $unitPrice = $item['harga_satuan'];

                if ($unitPrice === null || (float) $unitPrice <= 0) {
                    throw ValidationException::withMessages([
                        'items' => "Harga jual {$inventory->nama_barang} belum diatur di data persediaan.",
                    ]);
                }

                DetailPenjualan::query()->create([
                    'id_penjualan' => $transaction->id_penjualan,
                    'id_persediaan' => $inventory->id_persediaan,
                    'satuan' => $inventory->satuan,
                    'harga_satuan' => $unitPrice,
                    'jumlah_barang' => $item['jumlah_barang'],
                ]);

                $inventory->decrement('stok', $item['jumlah_barang']);
            }

            return $transaction;
        });

        return redirect()
            ->route('employee.penjualan.show', $transaction)
            ->with('status', 'Transaksi penjualan berhasil disimpan.');
    }

    public function show(Penjualan $penjualan): View
    {
        $penjualan->load(['pelanggan', 'pegawai', 'detailPenjualan.persediaan']);

        return view('transactions.show', [
            'title' => 'Detail penjualan',
            'type' => 'Penjualan',
            'transactionId' => $penjualan->id_penjualan,
            'date' => $penjualan->tanggal,
            'counterpart' => $penjualan->pelanggan->nama_pelanggan,
            'employee' => $penjualan->pegawai->nama_pegawai,
            'status' => null,
            'lines' => $penjualan->detailPenjualan,
            'priceField' => 'harga_satuan',
            'total' => $penjualan->detailPenjualan->sum('subtotal'),
            'backRoute' => route('employee.penjualan.index'),
        ]);
    }

    private function validateTransaction(Request $request, string $priceField): array
    {
        $validator = Validator::make($request->all(), [
            'id_pelanggan' => ['nullable', 'string', 'exists:pelanggan,id_pelanggan'],
            'pelanggan_baru.nama_pelanggan' => ['nullable', 'string', 'max:100'],
            'pelanggan_baru.no_hp' => ['nullable', 'string', 'max:20'],
            'pelanggan_baru.alamat' => ['required_with:pelanggan_baru.nama_pelanggan', 'nullable', 'string', 'max:255'],
            'tanggal' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_persediaan' => ['nullable', 'string', 'exists:persediaan,id_persediaan'],
            'items.*.jumlah_barang' => ['nullable', 'numeric', 'decimal:0,3', 'min:0.001', 'max:999999999999.999'],
            "items.*.{$priceField}" => ['nullable', 'numeric', 'min:0.01', 'max:9999999999999.99'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $hasExistingCustomer = filled($request->input('id_pelanggan'));
            $hasNewCustomerName = filled($request->input('pelanggan_baru.nama_pelanggan'));

            if ($hasExistingCustomer === $hasNewCustomerName) {
                $validator->errors()->add(
                    'id_pelanggan',
                    'Pilih pelanggan yang sudah ada atau isi nama pelanggan baru.'
                );
            }
        });

        $this->validateCompleteLines($validator, $request, $priceField);

        $data = $validator->validate();
        $data['items'] = array_values(array_filter(
            $data['items'],
            fn (array $item) => filled($item['id_persediaan'] ?? null)
        ));

        return $data;
    }

    private function validateCompleteLines($validator, Request $request, string $priceField): void
    {
        $validator->after(function ($validator) use ($request, $priceField): void {
            $completeRows = 0;

            foreach ((array) $request->input('items', []) as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $fields = [
                    'id_persediaan' => 'Pilih barang.',
                    'jumlah_barang' => 'Masukkan jumlah barang.',
                    $priceField => 'Masukkan harga satuan.',
                ];
                $hasAnyValue = false;
                $missingFields = [];

                foreach ($fields as $field => $message) {
                    if (filled($item[$field] ?? null)) {
                        $hasAnyValue = true;
                    } else {
                        $missingFields[$field] = $message;
                    }
                }

                if (! $hasAnyValue) {
                    continue;
                }

                if ($missingFields !== []) {
                    foreach ($missingFields as $field => $message) {
                        $validator->errors()->add("items.{$index}.{$field}", $message);
                    }
                } else {
                    if ((float) $item[$priceField] * (float) $item['jumlah_barang'] > 9999999999999.99) {
                        $validator->errors()->add(
                            "items.{$index}.{$priceField}",
                            'Subtotal barang melebihi batas maksimum yang dapat disimpan.'
                        );
                        continue;
                    }

                    $completeRows++;
                }
            }

            if ($completeRows === 0) {
                $validator->errors()->add('items', 'Tambahkan minimal satu barang dengan jumlah dan harga yang lengkap.');
            }
        });
    }
}
