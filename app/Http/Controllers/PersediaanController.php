<?php

namespace App\Http\Controllers;

use App\Models\DetailPenjualan;
use App\Models\Persediaan;
use App\Services\CodeGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersediaanController extends Controller
{
    private const LOW_STOCK_THRESHOLD = 15;

    private const INVENTORY_UNITS = [
        'pcs' => 'Pcs',
        'unit' => 'Unit',
        'kg' => 'Kilogram (kg)',
        'gram' => 'Gram (g)',
        'ton' => 'Ton',
        'meter' => 'Meter (m)',
        'cm' => 'Sentimeter (cm)',
        'mm' => 'Milimeter (mm)',
        'm2' => 'Meter persegi (m²)',
        'm3' => 'Meter kubik (m³)',
        'liter' => 'Liter (L)',
        'ml' => 'Mililiter (ml)',
        'batang' => 'Batang',
        'sak' => 'Sak',
        'karung' => 'Karung',
        'dus' => 'Dus',
        'box' => 'Box',
        'roll' => 'Roll',
        'lembar' => 'Lembar',
        'set' => 'Set',
        'pak' => 'Pak',
        'galon' => 'Galon',
    ];

    private const CUSTOM_UNIT = '__custom__';

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
        ]);
        $search = $filters['search'] ?? null;
        $category = $filters['category'] ?? null;

        $summary = Persediaan::query()
            ->selectRaw('COUNT(*) AS total_items')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN stok > 0 AND stok <= ? THEN 1 ELSE 0 END), 0) AS low_stock_items',
                [self::LOW_STOCK_THRESHOLD]
            )
            ->selectRaw('COALESCE(SUM(CASE WHEN stok <= 0 THEN 1 ELSE 0 END), 0) AS out_of_stock_items')
            ->first();

        $categories = Persediaan::query()
            ->whereNotNull('kategori_barang')
            ->where('kategori_barang', '<>', '')
            ->distinct()
            ->orderBy('kategori_barang')
            ->pluck('kategori_barang');

        $lastSalePrice = DetailPenjualan::query()
            ->join('penjualan', 'penjualan.id_penjualan', '=', 'detail_penjualan.id_penjualan')
            ->whereColumn('detail_penjualan.id_persediaan', 'persediaan.id_persediaan')
            ->orderByDesc('penjualan.tanggal')
            ->orderByDesc('detail_penjualan.id_detail')
            ->select('detail_penjualan.harga_satuan')
            ->limit(1);

        $inventory = Persediaan::query()
            ->select(['id_persediaan', 'nama_barang', 'kategori_barang', 'satuan', 'stok'])
            ->selectSub($lastSalePrice, 'harga_jual')
            ->when($search, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('id_persediaan', 'like', "%{$search}%")
                        ->orWhere('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kategori_barang', 'like', "%{$search}%")
                        ->orWhere('satuan', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($query, string $category) => $query->where('kategori_barang', $category))
            ->orderBy('nama_barang')
            ->paginate(10)
            ->withQueryString();

        return view('masters.inventory-index', [
            'summary' => $summary,
            'categories' => $categories,
            'maxStock' => (float) (Persediaan::query()->max('stok') ?? 0),
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
            'search' => $search,
            'selectedCategory' => $category,
            'rows' => $inventory,
            'createRoute' => 'employee.persediaan.create',
            'editRoute' => 'employee.persediaan.edit',
            'deleteRoute' => 'employee.persediaan.destroy',
        ]);
    }

    public function create(): View
    {
        return $this->form();
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateInventory($request);
        $data['id_persediaan'] = CodeGeneratorService::generate('persediaan');
        $data['id_pegawai'] = $request->user('employee')->id_pegawai;

        Persediaan::query()->create($data);

        return redirect()->route('employee.persediaan.index')->with('status', 'Barang berhasil ditambahkan.');
    }

    public function edit(Persediaan $persediaan): View
    {
        return $this->form($persediaan);
    }

    public function update(Request $request, Persediaan $persediaan): RedirectResponse
    {
        $data = $this->validateInventory($request);

        $hasTransactions = $persediaan->detailPenjualan()->exists() || $persediaan->detailPembelian()->exists();
        $hasFractionalHistory = $persediaan->detailPenjualan()->whereRaw('jumlah_barang <> ROUND(jumlah_barang, 0)')->exists()
            || $persediaan->detailPembelian()->whereRaw('jumlah_barang <> ROUND(jumlah_barang, 0)')->exists();
        $isLegacyUnit = $persediaan->satuan === 'unit'
            && floor((float) $persediaan->stok) === (float) $persediaan->stok
            && ! $hasFractionalHistory;

        if ($data['satuan'] !== $persediaan->satuan && $hasTransactions && ! $isLegacyUnit) {
            return back()
                ->withInput()
                ->withErrors(['satuan' => 'Satuan tidak dapat diubah karena barang sudah memiliki riwayat transaksi. Buat data barang baru jika satuannya berbeda.']);
        }

        $persediaan->update($data);

        return redirect()->route('employee.persediaan.index')->with('status', 'Data barang berhasil diperbarui.');
    }

    public function destroy(Persediaan $persediaan): RedirectResponse
    {
        if ((float) $persediaan->stok > 0) {
            return back()->withErrors(['delete' => 'Stok barang harus nol sebelum barang dapat dihapus.']);
        }

        if ($persediaan->detailPenjualan()->exists() || $persediaan->detailPembelian()->exists()) {
            return back()->withErrors(['delete' => 'Barang ini sudah digunakan dalam transaksi dan tidak dapat dihapus.']);
        }

        $persediaan->delete();

        return redirect()->route('employee.persediaan.index')->with('status', 'Barang berhasil dihapus.');
    }

    private function form(?Persediaan $persediaan = null): View
    {
        return view('masters.form', [
            'title' => $persediaan ? 'Ubah barang' : 'Tambah barang',
            'fields' => [
                ['name' => 'nama_barang', 'label' => 'Nama barang', 'type' => 'text', 'required' => true, 'maxlength' => 100],
                ['name' => 'kategori_barang', 'label' => 'Kategori', 'type' => 'text', 'required' => false, 'maxlength' => 50],
                [
                    'name' => 'satuan',
                    'label' => 'Satuan stok',
                    'type' => 'select',
                    'required' => true,
                    'options' => self::INVENTORY_UNITS,
                    'customOption' => true,
                    'help' => 'Stok, pembelian, dan penjualan barang ini akan selalu memakai satuan ini.',
                ],
                ['name' => 'stok', 'label' => 'Stok awal', 'type' => 'number', 'required' => true, 'min' => 0, 'step' => '0.001'],
            ],
            'values' => $persediaan?->only(['nama_barang', 'kategori_barang', 'satuan', 'stok']) ?? ['satuan' => 'pcs', 'stok' => 0],
            'formAction' => $persediaan
                ? route('employee.persediaan.update', $persediaan)
                : route('employee.persediaan.store'),
            'cancelRoute' => route('employee.persediaan.index'),
            'method' => $persediaan ? 'PUT' : null,
            'submitLabel' => $persediaan ? 'Simpan perubahan' : 'Simpan barang',
        ]);
    }

    private function validateInventory(Request $request): array
    {
        $data = $request->validate([
            'nama_barang' => ['required', 'string', 'max:100'],
            'kategori_barang' => ['nullable', 'string', 'max:50'],
            'satuan' => ['required', 'string', Rule::in([...array_keys(self::INVENTORY_UNITS), self::CUSTOM_UNIT])],
            'satuan_custom' => ['nullable', 'required_if:satuan,'.self::CUSTOM_UNIT, 'string', 'max:20'],
            'stok' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999999.999'],
        ]);

        if ($data['satuan'] === self::CUSTOM_UNIT) {
            $data['satuan'] = $data['satuan_custom'];
        }

        unset($data['satuan_custom']);

        return $data;
    }
}
