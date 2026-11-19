<?php

namespace App\Http\Controllers;

use App\Models\DetailPembelian;
use App\Models\NotaPembelian;
use App\Models\Pembelian;
use App\Models\Pegawai;
use App\Models\Persediaan;
use App\Models\Supplier;
use App\Services\CodeGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PembelianController extends Controller
{
    private const MAX_STOCK = 999999999999.999;

    public function index(): View
    {
        $transactions = Pembelian::query()
            ->with(['supplier', 'pegawai'])
            ->withSum('detailPembelian as total', 'subtotal')
            ->orderByDesc('tanggal')
            ->orderByDesc('id_pembelian')
            ->paginate(15);

        return view('transactions.purchase-index', compact('transactions'));
    }

    public function create(): View
    {
        return view('transactions.form', [
            'type' => 'Pembelian',
            'action' => route('employee.pembelian.store'),
            'counterparts' => Supplier::query()->orderBy('nama_supplier')->get(),
            'products' => Persediaan::query()->orderBy('nama_barang')->get(),
            'date' => today()->toDateString(),
            'showStatus' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTransaction($request);
        $transaction = $this->savePurchase($data, Auth::guard('employee')->user());

        return redirect()
            ->route('employee.pembelian.show', $transaction)
            ->with('status', 'Transaksi pembelian berhasil disimpan.');
    }

    public function storeFromOcr(Request $request): RedirectResponse
    {
        $data = $this->validateTransaction($request);
        $receiptData = $request->validate([
            'receipt_token' => ['required', 'uuid'],
            'hasil_ocr' => ['required', 'string', 'max:50000'],
        ]);
        $draftKey = 'receipt_drafts.'.$receiptData['receipt_token'];
        $draft = $request->session()->get($draftKey);
        $employee = Auth::guard('employee')->user();

        if (! is_array($draft) || ($draft['employee_id'] ?? null) !== $employee->getAuthIdentifier()) {
            throw ValidationException::withMessages([
                'receipt_token' => 'Sesi pemrosesan nota sudah berakhir. Unggah kembali gambar nota.',
            ]);
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($draft['file_path'] ?? '')) {
            throw ValidationException::withMessages([
                'receipt_token' => 'File nota tidak ditemukan. Unggah kembali gambar nota.',
            ]);
        }

        $transaction = $this->savePurchase($data, $employee, [
            'file_path' => $draft['file_path'],
            'hasil_ocr' => $receiptData['hasil_ocr'],
        ]);
        $request->session()->forget($draftKey);

        return redirect()
            ->route('employee.pembelian.show', $transaction)
            ->with('status', 'Pembelian dan hasil pemeriksaan OCR berhasil disimpan.');
    }

    public function show(Pembelian $pembelian): View
    {
        $pembelian->load(['supplier', 'pegawai', 'detailPembelian.persediaan', 'notaPembelian']);

        return view('transactions.show', [
            'title' => 'Detail pembelian',
            'type' => 'Pembelian',
            'transactionId' => $pembelian->id_pembelian,
            'date' => $pembelian->tanggal,
            'counterpart' => $pembelian->supplier->nama_supplier,
            'employee' => $pembelian->pegawai->nama_pegawai,
            'status' => $pembelian->status_pengiriman,
            'lines' => $pembelian->detailPembelian,
            'priceField' => 'harga_beli',
            'total' => $pembelian->detailPembelian->sum('subtotal'),
            'receipt' => $pembelian->notaPembelian,
            'backRoute' => route('employee.pembelian.index'),
        ]);
    }

    public function updateStatus(Request $request, Pembelian $pembelian): RedirectResponse
    {
        $data = $request->validate([
            'status_pengiriman' => ['required', 'in:Diterima,Dibatalkan'],
        ]);

        DB::transaction(function () use ($pembelian, $data): void {
            $lockedPurchase = Pembelian::query()
                ->where('id_pembelian', $pembelian->id_pembelian)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPurchase->status_pengiriman !== 'Diproses') {
                throw ValidationException::withMessages([
                    'status_pengiriman' => 'Status hanya dapat diubah saat pembelian masih diproses.',
                ]);
            }

            if ($data['status_pengiriman'] === 'Diterima') {
                foreach ($lockedPurchase->detailPembelian as $detail) {
                    $inventory = Persediaan::query()
                        ->where('id_persediaan', $detail->id_persediaan)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ((float) $inventory->stok > self::MAX_STOCK - (float) $detail->jumlah_barang) {
                        throw ValidationException::withMessages([
                            'status_pengiriman' => "Penambahan stok {$inventory->nama_barang} melebihi kapasitas penyimpanan ({$inventory->satuan}).",
                        ]);
                    }

                    $inventory->increment('stok', $detail->jumlah_barang);
                }
            }

            $lockedPurchase->update(['status_pengiriman' => $data['status_pengiriman']]);
        });

        return redirect()
            ->route('employee.pembelian.show', $pembelian)
            ->with('status', 'Status pembelian berhasil diperbarui.');
    }

    private function validateTransaction(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'id_supplier' => ['required', 'string', 'exists:supplier,id_supplier'],
            'tanggal' => ['required', 'date'],
            'status_pengiriman' => ['required', 'in:Diproses,Diterima'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_persediaan' => ['nullable', 'string', 'exists:persediaan,id_persediaan'],
            'items.*.jumlah_barang' => ['nullable', 'numeric', 'decimal:0,3', 'min:0.001', 'max:999999999999.999'],
            'items.*.harga_beli' => ['nullable', 'numeric', 'min:0.01', 'max:9999999999999.99'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $completeRows = 0;

            foreach ((array) $request->input('items', []) as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $fields = [
                    'id_persediaan' => 'Pilih barang.',
                    'jumlah_barang' => 'Masukkan jumlah barang.',
                    'harga_beli' => 'Masukkan harga beli satuan.',
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
                    if ((float) $item['harga_beli'] * (float) $item['jumlah_barang'] > 9999999999999.99) {
                        $validator->errors()->add(
                            "items.{$index}.harga_beli",
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

        $data = $validator->validate();
        $data['items'] = array_values(array_filter(
            $data['items'],
            fn (array $item) => filled($item['id_persediaan'] ?? null)
        ));

        return $data;
    }

    private function savePurchase(array $data, Pegawai $employee, ?array $receipt = null): Pembelian
    {
        return DB::transaction(function () use ($data, $employee, $receipt): Pembelian {
            $transaction = Pembelian::query()->create([
                'id_pembelian' => CodeGeneratorService::generate(
                    'pembelian',
                    \Illuminate\Support\Carbon::parse($data['tanggal'])->format('ym')
                ),
                'id_supplier' => $data['id_supplier'],
                'id_pegawai' => $employee->id_pegawai,
                'tanggal' => $data['tanggal'],
                'status_pengiriman' => $data['status_pengiriman'],
            ]);

            foreach ($data['items'] as $item) {
                $inventory = Persediaan::query()
                    ->where('id_persediaan', $item['id_persediaan'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $data['status_pengiriman'] === 'Diterima'
                    && (float) $inventory->stok > self::MAX_STOCK - (float) $item['jumlah_barang']
                ) {
                    throw ValidationException::withMessages([
                        'items' => "Penambahan stok {$inventory->nama_barang} melebihi kapasitas penyimpanan ({$inventory->satuan}).",
                    ]);
                }

                DetailPembelian::query()->create([
                    'id_pembelian' => $transaction->id_pembelian,
                    'id_persediaan' => $inventory->id_persediaan,
                    'satuan' => $inventory->satuan,
                    'harga_beli' => $item['harga_beli'],
                    'jumlah_barang' => $item['jumlah_barang'],
                ]);

                if ($data['status_pengiriman'] === 'Diterima') {
                    $inventory->increment('stok', $item['jumlah_barang']);
                }
            }

            if ($receipt !== null) {
                NotaPembelian::query()->create([
                    'id_pembelian' => $transaction->id_pembelian,
                    'file_path' => $receipt['file_path'],
                    'hasil_ocr' => $receipt['hasil_ocr'],
                ]);
            }

            return $transaction;
        });
    }
}
