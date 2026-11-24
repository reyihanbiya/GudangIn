<?php

namespace App\Http\Controllers;

use App\Models\Persediaan;
use App\Services\CodeGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PersediaanController extends Controller
{
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

        $inventory = Persediaan::query()
            ->when($search, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('id_persediaan', 'like', "%{$search}%")
                        ->orWhere('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kategori_barang', 'like', "%{$search}%")
                        ->orWhere('satuan', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($query) => $query->where('kategori_barang', $category))
            ->orderBy('nama_barang')
            ->paginate(10)
            ->withQueryString();

        $categories = Persediaan::query()
            ->whereNotNull('kategori_barang')
            ->where('kategori_barang', '<>', '')
            ->distinct()
            ->orderBy('kategori_barang')
            ->pluck('kategori_barang');

        return view('masters.inventory', [
            'search' => $search,
            'category' => $category,
            'categories' => $categories,
            'rows' => $inventory,
            'totalItems' => Persediaan::query()->count(),
            'outOfStockItems' => Persediaan::query()->where('stok', '<=', 0)->count(),
            'unitTypes' => Persediaan::query()->distinct()->count('satuan'),
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

    public function stockCard(Persediaan $persediaan): View
    {
        $purchases = DB::table('detail_pembelian as detail')
            ->join('pembelian as purchase', 'purchase.id_pembelian', '=', 'detail.id_pembelian')
            ->where('detail.id_persediaan', $persediaan->id_persediaan)
            ->where('purchase.status_pengiriman', 'Diterima')
            ->selectRaw(
                "purchase.tanggal as tanggal, 'Pembelian' as keterangan, purchase.id_pembelian as id_transaksi, detail.jumlah_barang as masuk, 0 as keluar, detail.satuan as satuan"
            );

        $sales = DB::table('detail_penjualan as detail')
            ->join('penjualan as sale', 'sale.id_penjualan', '=', 'detail.id_penjualan')
            ->where('detail.id_persediaan', $persediaan->id_persediaan)
            ->selectRaw(
                "sale.tanggal as tanggal, 'Penjualan' as keterangan, sale.id_penjualan as id_transaksi, 0 as masuk, detail.jumlah_barang as keluar, detail.satuan as satuan"
            );

        $movements = $purchases->unionAll($sales)
            ->orderBy('tanggal')
            ->orderBy('id_transaksi')
            ->paginate(20);

        return view('masters.stock-card', compact('persediaan', 'movements'));
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
                ['name' => 'harga_jual', 'label' => 'Harga jual', 'type' => 'number', 'required' => true, 'min' => 0.01, 'max' => 9999999999999.99, 'step' => '0.01'],
                ['name' => 'stok', 'label' => 'Stok awal', 'type' => 'number', 'required' => true, 'min' => 0, 'step' => '0.001'],
            ],
            'values' => $persediaan?->only(['nama_barang', 'kategori_barang', 'satuan', 'harga_jual', 'stok']) ?? ['satuan' => 'pcs', 'harga_jual' => '', 'stok' => 0],
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
            'harga_jual' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999999.99'],
            'stok' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999999.999'],
        ]);

        if ($data['satuan'] === self::CUSTOM_UNIT) {
            $data['satuan'] = $data['satuan_custom'];
        }

        unset($data['satuan_custom']);

        return $data;
    }
}
