<?php

namespace App\Http\Controllers;

use App\Models\Persediaan;
use App\Services\CodeGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PersediaanController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ])['search'] ?? null;

        $inventory = Persediaan::query()
            ->when($search, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kategori_barang', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama_barang')
            ->paginate(10)
            ->withQueryString();

        return view('masters.index', [
            'title' => 'Persediaan',
            'description' => 'Kelola barang dan pantau jumlah stok yang tersedia.',
            'createRoute' => 'employee.persediaan.create',
            'editRoute' => 'employee.persediaan.edit',
            'deleteRoute' => 'employee.persediaan.destroy',
            'search' => $search,
            'columns' => [
                ['label' => 'ID', 'field' => 'id_persediaan'],
                ['label' => 'Nama barang', 'field' => 'nama_barang'],
                ['label' => 'Kategori', 'field' => 'kategori_barang'],
                ['label' => 'Stok', 'field' => 'stok'],
            ],
            'rows' => $inventory,
            'stockCardRoute' => 'employee.persediaan.stock-card',
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
        $persediaan->update($this->validateInventory($request));

        return redirect()->route('employee.persediaan.index')->with('status', 'Data barang berhasil diperbarui.');
    }

    public function destroy(Persediaan $persediaan): RedirectResponse
    {
        if ((int) $persediaan->stok > 0) {
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
                "purchase.tanggal as tanggal, 'Pembelian' as keterangan, purchase.id_pembelian as id_transaksi, detail.jumlah_barang as masuk, 0 as keluar"
            );

        $sales = DB::table('detail_penjualan as detail')
            ->join('penjualan as sale', 'sale.id_penjualan', '=', 'detail.id_penjualan')
            ->where('detail.id_persediaan', $persediaan->id_persediaan)
            ->selectRaw(
                "sale.tanggal as tanggal, 'Penjualan' as keterangan, sale.id_penjualan as id_transaksi, 0 as masuk, detail.jumlah_barang as keluar"
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
                ['name' => 'stok', 'label' => 'Stok', 'type' => 'number', 'required' => true, 'min' => 0],
            ],
            'values' => $persediaan?->only(['nama_barang', 'kategori_barang', 'stok']) ?? ['stok' => 0],
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
        return $request->validate([
            'nama_barang' => ['required', 'string', 'max:100'],
            'kategori_barang' => ['nullable', 'string', 'max:50'],
            'stok' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ]);
    }
}
