<?php

namespace App\Http\Controllers;

use App\Models\DetailPembelian;
use App\Models\NotaPembelian;
use App\Models\Pegawai;
use App\Models\Pembelian;
use App\Models\PenerimaanPembelian;
use App\Models\Persediaan;
use App\Models\Supplier;
use App\Services\CodeGeneratorService;
use App\Services\RestockRecommendationService;
use App\Support\QuantityFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PembelianController extends Controller
{
    private const MAX_STOCK = 999999999999.999;

    public function index(Request $request): View
    {
        $filters = $this->purchaseFilters($request);

        $suppliers = Supplier::query()
            ->orderBy('nama_supplier')
            ->get(['id_supplier', 'nama_supplier']);
        $selectedSupplier = $suppliers->firstWhere('id_supplier', $filters['supplier']);
        $hasFilters = count(array_filter(
            $filters,
            fn ($value) => $value !== null && $value !== ''
        )) > 0;
        $transactions = $this->filteredPurchases($filters)
            ->with([
                'supplier',
                'pegawai',
                'detailPembelian.persediaan',
                'detailPembelian.penerimaanLines',
                'penerimaanPembelian',
            ])
            ->withSum('detailPembelian as total', 'subtotal')
            ->paginate(10)
            ->withQueryString();

        return view('transactions.purchase-index', [
            'transactions' => $transactions,
            'filters' => $filters,
            'hasFilters' => $hasFilters,
            'suppliers' => $suppliers,
            'selectedSupplier' => $selectedSupplier,
            'statusLabels' => [
                'Diproses' => 'Menunggu',
                'Diterima' => 'Diterima',
                'Dibatalkan' => 'Dibatalkan',
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $purchases = $this->filteredPurchases($this->purchaseFilters($request))
            ->with(['supplier', 'pegawai'])
            ->withSum('detailPembelian as total', 'subtotal')
            ->lazy(500);

        return response()->streamDownload(function () use ($purchases): void {
            $temporaryPath = tempnam(sys_get_temp_dir(), 'purchase-export-');
            if ($temporaryPath === false) {
                throw new \RuntimeException('Tidak dapat membuat file sementara untuk ekspor pembelian.');
            }

            $writer = null;
            try {
                $writer = new XlsxWriter;
                $writer->openToFile($temporaryPath);
                $writer->addRow(Row::fromValues([
                    'Tanggal',
                    'No. PO',
                    'Supplier',
                    'Pegawai',
                    'Total',
                    'Status',
                ]));

                foreach ($purchases as $purchase) {
                    $writer->addRow($this->spreadsheetRow([
                        $purchase->tanggal,
                        $purchase->id_pembelian,
                        $purchase->supplier->nama_supplier,
                        $purchase->pegawai->nama_pegawai,
                        (float) $purchase->total,
                        $purchase->status_pengiriman,
                    ]));
                }

                $writer->close();
                if (readfile($temporaryPath) === false) {
                    throw new \RuntimeException('Tidak dapat membaca file ekspor pembelian.');
                }
            } finally {
                $writer?->close();
                if (is_file($temporaryPath) && ! unlink($temporaryPath)) {
                    throw new \RuntimeException('Tidak dapat menghapus file sementara ekspor pembelian.');
                }
            }
        }, 'daftar-pembelian-'.today()->format('Ymd').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function purchaseOrder(Pembelian $pembelian): View
    {
        $pembelian = $this->loadPurchaseOrder($pembelian);

        return view('transactions.purchase-order', [
            'pembelian' => $pembelian,
        ]);
    }

    public function downloadPurchaseOrder(Pembelian $pembelian): Response
    {
        $pembelian = $this->loadPurchaseOrder($pembelian);

        return Pdf::loadView('transactions.purchase-order-pdf', compact('pembelian'))
            ->setPaper('a4', 'portrait')
            ->download('PO-'.$pembelian->id_pembelian.'.pdf');
    }

    public function details(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);
        $filters = [
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
        ];

        if ($filters['from'] && $filters['to'] && $filters['to'] < $filters['from']) {
            throw ValidationException::withMessages([
                'to' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            ]);
        }

        $details = DB::table('detail_pembelian as detail')
            ->join('pembelian as purchase', 'purchase.id_pembelian', '=', 'detail.id_pembelian')
            ->join('supplier', 'supplier.id_supplier', '=', 'purchase.id_supplier')
            ->join('persediaan', 'persediaan.id_persediaan', '=', 'detail.id_persediaan')
            ->when($filters['from'], fn ($query, string $date) => $query->whereDate('purchase.tanggal', '>=', $date))
            ->when($filters['to'], fn ($query, string $date) => $query->whereDate('purchase.tanggal', '<=', $date))
            ->select([
                'purchase.tanggal',
                'purchase.id_pembelian',
                'supplier.nama_supplier',
                'persediaan.nama_barang',
                'detail.satuan',
                'detail.jumlah_barang',
                'detail.harga_beli',
                'detail.subtotal',
            ])
            ->orderByDesc('purchase.tanggal')
            ->orderByDesc('purchase.id_pembelian')
            ->paginate(20)
            ->withQueryString();

        return view('transactions.purchase-details', [
            'details' => $details,
            'filters' => $filters,
        ]);
    }

    public function create(RestockRecommendationService $restockRecommendations): View
    {
        return view('transactions.form', [
            'type' => 'Pembelian',
            'action' => route('employee.pembelian.store'),
            'counterparts' => Supplier::query()->orderBy('nama_supplier')->get(),
            'products' => Persediaan::query()->orderBy('nama_barang')->get(),
            'restockRecommendations' => $restockRecommendations->forPurchaseForm(),
            'date' => today()->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTransaction($request);
        $transaction = $this->savePurchase($data, Auth::guard('employee')->user());

        return redirect()
            ->route('employee.pembelian.index')
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
            ->route('employee.pembelian.index')
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

    public function edit(Pembelian $pembelian, RestockRecommendationService $restockRecommendations): View
    {
        abort_if($pembelian->status_pengiriman !== 'Diproses', 404);
        abort_if($pembelian->penerimaanPembelian()->exists(), 404);

        $pembelian->load(['detailPembelian']);

        return view('transactions.form', [
            'type' => 'Pembelian',
            'action' => route('employee.pembelian.update', $pembelian),
            'counterparts' => Supplier::query()->orderBy('nama_supplier')->get(),
            'products' => Persediaan::query()->orderBy('nama_barang')->get(),
            'restockRecommendations' => $restockRecommendations->forPurchaseForm(),
            'date' => $pembelian->tanggal,
            'method' => 'PUT',
            'submitLabel' => 'Simpan perubahan',
            'oldTransaction' => $pembelian,
        ]);
    }

    public function update(Request $request, Pembelian $pembelian): RedirectResponse
    {
        if (
            $pembelian->status_pengiriman !== 'Diproses'
            || $pembelian->penerimaanPembelian()->exists()
        ) {
            throw ValidationException::withMessages([
                'status_pengiriman' => 'Pembelian yang sudah menerima barang tidak dapat diubah.',
            ]);
        }

        $data = $this->validateTransaction($request);
        $data['status_pengiriman'] = $pembelian->status_pengiriman;

        DB::transaction(function () use ($pembelian, $data): void {
            $lockedPurchase = Pembelian::query()
                ->where('id_pembelian', $pembelian->id_pembelian)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedPurchase->status_pengiriman !== 'Diproses'
                || $lockedPurchase->penerimaanPembelian()->exists()
            ) {
                throw ValidationException::withMessages([
                    'status_pengiriman' => 'Pembelian yang sudah menerima barang tidak dapat diubah.',
                ]);
            }

            $lockedPurchase->update([
                'id_supplier' => $data['id_supplier'],
                'tanggal' => $data['tanggal'],
            ]);

            $lockedPurchase->detailPembelian()->delete();

            foreach ($data['items'] as $item) {
                $inventory = Persediaan::query()
                    ->where('id_persediaan', $item['id_persediaan'])
                    ->lockForUpdate()
                    ->firstOrFail();

                DetailPembelian::query()->create([
                    'id_pembelian' => $lockedPurchase->id_pembelian,
                    'id_persediaan' => $inventory->id_persediaan,
                    'satuan' => filled($item['satuan'] ?? null) ? trim((string) $item['satuan']) : $inventory->satuan,
                    'harga_beli' => $item['harga_beli'],
                    'jumlah_barang' => $item['jumlah_barang'],
                ]);
            }
        });

        return redirect()
            ->route('employee.pembelian.index')
            ->with('status', 'Pembelian berhasil diubah.');
    }

    public function destroy(Pembelian $pembelian): RedirectResponse
    {
        DB::transaction(function () use ($pembelian): void {
            $lockedPurchase = Pembelian::query()
                ->where('id_pembelian', $pembelian->id_pembelian)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPurchase->load(['detailPembelian.penerimaanLines']);
            $hasReceiptHistory = $lockedPurchase->penerimaanPembelian()->exists();
            $receivedByProduct = [];

            foreach ($lockedPurchase->detailPembelian as $detail) {
                $received = (float) $detail->penerimaanLines->sum('jumlah_datang');
                if (! $hasReceiptHistory && $lockedPurchase->status_pengiriman === 'Diterima') {
                    $received = (float) $detail->jumlah_barang;
                }

                if ($received > 0) {
                    $receivedByProduct[$detail->id_persediaan] = ($receivedByProduct[$detail->id_persediaan] ?? 0) + $received;
                }
            }

            foreach ($receivedByProduct as $productId => $received) {
                $inventory = Persediaan::query()
                    ->where('id_persediaan', $productId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((float) $inventory->stok < $received) {
                    throw ValidationException::withMessages([
                        'delete' => "Stok {$inventory->nama_barang} tidak mencukupi untuk menghapus jumlah barang yang sudah diterima.",
                    ]);
                }

                $inventory->decrement('stok', $received);
            }

            $receipt = $lockedPurchase->notaPembelian()->first();
            if ($receipt) {
                Storage::disk('local')->delete($receipt->file_path);
            }

            $receiptIds = $lockedPurchase->penerimaanPembelian()->pluck('id_penerimaan');
            DB::table('detail_penerimaan_pembelian')
                ->whereIn('id_penerimaan', $receiptIds)
                ->delete();
            $lockedPurchase->penerimaanPembelian()->delete();
            $lockedPurchase->detailPembelian()->delete();
            $lockedPurchase->notaPembelian()->delete();
            $lockedPurchase->delete();
        });

        return redirect()
            ->route('employee.pembelian.index')
            ->with('status', 'Pembelian berhasil dihapus.');
    }

    public function updateStatus(Request $request, Pembelian $pembelian): RedirectResponse
    {
        $data = $request->validate([
            'status_pengiriman' => ['required', 'in:Diterima,Dibatalkan'],
        ]);

        if ($data['status_pengiriman'] === 'Diterima') {
            return $this->receiveRemaining($pembelian);
        }

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

            $lockedPurchase->update(['status_pengiriman' => $data['status_pengiriman']]);
        });

        return redirect()
            ->route('employee.pembelian.show', $pembelian)
            ->with('status', 'Status pembelian berhasil diperbarui.');
    }

    public function receive(Request $request, Pembelian $pembelian): RedirectResponse
    {
        $data = $request->validate([
            'catatan' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array'],
            'items.*.jumlah_datang' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999999.999'],
        ]);
        $employee = Auth::guard('employee')->user();
        $isComplete = $this->recordReceipt(
            $pembelian,
            $data['items'],
            $data['catatan'] ?? null,
            $employee->id_pegawai
        );

        return redirect()
            ->route('employee.pembelian.index')
            ->with(
                'status',
                $isComplete
                    ? 'Penerimaan pembelian lengkap berhasil dicatat.'
                    : 'Penerimaan parsial berhasil dicatat. Barang yang tersisa tetap menjadi backorder.'
            );
    }

    private function receiveRemaining(Pembelian $pembelian): RedirectResponse
    {
        $pembelian->load(['detailPembelian.penerimaanLines']);
        $items = [];

        foreach ($pembelian->detailPembelian as $detail) {
            $alreadyReceived = (float) $detail->penerimaanLines->sum('jumlah_datang');
            $remaining = round(max(0, (float) $detail->jumlah_barang - $alreadyReceived), 3);
            $items[$detail->id_detail] = [
                'jumlah_datang' => number_format($remaining, 3, '.', ''),
            ];
        }

        $employee = Auth::guard('employee')->user();
        $this->recordReceipt($pembelian, $items, null, $employee->id_pegawai);

        return redirect()
            ->route('employee.pembelian.show', $pembelian)
            ->with('status', 'Status pembelian berhasil diperbarui.');
    }

    private function recordReceipt(
        Pembelian $pembelian,
        array $items,
        ?string $note,
        string $employeeId
    ): bool {
        return DB::transaction(function () use ($pembelian, $items, $note, $employeeId): bool {
            $lockedPurchase = Pembelian::query()
                ->where('id_pembelian', $pembelian->id_pembelian)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPurchase->status_pengiriman !== 'Diproses') {
                throw ValidationException::withMessages([
                    'status_pengiriman' => 'Penerimaan hanya dapat dicatat saat pembelian masih menunggu barang.',
                ]);
            }

            $details = $lockedPurchase->detailPembelian()
                ->orderBy('id_detail')
                ->lockForUpdate()
                ->get();
            $validDetailIds = $details->map(fn (DetailPembelian $detail): string => (string) $detail->id_detail)->all();
            foreach (array_keys($items) as $detailId) {
                if (! in_array((string) $detailId, $validDetailIds, true)) {
                    throw ValidationException::withMessages([
                        'items' => 'Daftar barang penerimaan tidak sesuai dengan pembelian ini.',
                    ]);
                }
            }

            $receivedLines = [];
            $receivedTotal = 0.0;
            $isComplete = true;

            foreach ($details as $detail) {
                $detailId = (string) $detail->id_detail;
                $alreadyReceived = (float) DB::table('detail_penerimaan_pembelian')
                    ->where('id_detail_pembelian', $detail->id_detail)
                    ->sum('jumlah_datang');
                $ordered = round((float) $detail->jumlah_barang, 3);
                $remaining = round(max(0, $ordered - $alreadyReceived), 3);
                $quantity = round((float) ($items[$detailId]['jumlah_datang'] ?? 0), 3);

                if ($quantity > $remaining) {
                    throw ValidationException::withMessages([
                        "items.{$detailId}.jumlah_datang" => "Jumlah datang {$detail->persediaan()->value('nama_barang')} melebihi sisa pesanan.",
                    ]);
                }

                if ($quantity > 0) {
                    $inventory = Persediaan::query()
                        ->where('id_persediaan', $detail->id_persediaan)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ((float) $inventory->stok > self::MAX_STOCK - $quantity) {
                        throw ValidationException::withMessages([
                            "items.{$detailId}.jumlah_datang" => "Penambahan stok {$inventory->nama_barang} melebihi kapasitas penyimpanan ({$inventory->satuan}).",
                        ]);
                    }

                    $inventory->increment('stok', $quantity);
                    $receivedLines[] = [
                        'id_detail_pembelian' => $detail->id_detail,
                        'jumlah_datang' => number_format($quantity, 3, '.', ''),
                    ];
                    $receivedTotal += $quantity;
                }

                if (round($alreadyReceived + $quantity, 3) < $ordered) {
                    $isComplete = false;
                }
            }

            if ($receivedTotal <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Masukkan jumlah barang yang datang pada minimal satu baris.',
                ]);
            }

            $receipt = PenerimaanPembelian::query()->create([
                'id_pembelian' => $lockedPurchase->id_pembelian,
                'id_pegawai' => $employeeId,
                'catatan' => filled($note) ? trim($note) : null,
            ]);

            foreach ($receivedLines as $line) {
                $receipt->detailPenerimaan()->create($line);
            }

            if ($isComplete) {
                $lockedPurchase->update(['status_pengiriman' => 'Diterima']);
            }

            return $isComplete;
        });
    }

    private function purchaseFilters(Request $request): array
    {
        return array_merge([
            'search' => null,
            'from' => null,
            'to' => null,
            'status' => null,
            'supplier' => null,
        ], $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'in:Diproses,Diterima,Dibatalkan'],
            'supplier' => ['nullable', 'string', 'exists:supplier,id_supplier'],
        ]));
    }

    private function filteredPurchases(array $filters): Builder
    {
        return Pembelian::query()
            ->when($filters['search'] !== null, function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function ($query) use ($search): void {
                    $query->where('id_pembelian', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($query) use ($search): void {
                            $query->where('nama_supplier', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['from'], fn ($query, string $date) => $query->whereDate('tanggal', '>=', $date))
            ->when($filters['to'], fn ($query, string $date) => $query->whereDate('tanggal', '<=', $date))
            ->when($filters['status'], fn ($query, string $status) => $query->where('status_pengiriman', $status))
            ->when($filters['supplier'], fn ($query, string $supplier) => $query->where('id_supplier', $supplier))
            ->orderByDesc('tanggal')
            ->orderByDesc('id_pembelian');
    }

    private function loadPurchaseOrder(Pembelian $pembelian): Pembelian
    {
        return $pembelian->load([
            'supplier',
            'pegawai',
            'detailPembelian.persediaan',
        ]);
    }

    private function spreadsheetRow(array $values): Row
    {
        return new Row(array_map(
            fn ($value) => is_string($value)
                ? new StringCell($value, null)
                : Cell::fromValue($value),
            $values
        ));
    }

    private function validateTransaction(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'id_supplier' => ['required', 'string', 'exists:supplier,id_supplier'],
            'tanggal' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_persediaan' => ['nullable', 'string', 'exists:persediaan,id_persediaan'],
            'items.*.jumlah_barang' => ['nullable', 'numeric', 'decimal:0,3', 'min:0.001', 'max:999999999999.999'],
            'items.*.harga_beli' => ['nullable', 'numeric', 'min:0.01', 'max:9999999999999.99'],
            'items.*.satuan' => ['nullable', 'string', 'max:20'],
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
        $data['status_pengiriman'] = 'Diproses';
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
                    Carbon::parse($data['tanggal'])->format('ym')
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
                    'satuan' => filled($item['satuan'] ?? null) ? trim((string) $item['satuan']) : $inventory->satuan,
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
