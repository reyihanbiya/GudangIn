<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\CodeGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ])['search'] ?? null;

        $suppliers = Supplier::query()
            ->when($search, function ($query, string $search): void {
                $query->where('nama_supplier', 'like', "%{$search}%");
            })
            ->orderBy('nama_supplier')
            ->paginate(10)
            ->withQueryString();

        return view('masters.index', [
            'title' => 'Supplier',
            'description' => 'Kelola data pemasok barang untuk transaksi pembelian.',
            'createRoute' => 'employee.supplier.create',
            'editRoute' => 'employee.supplier.edit',
            'deleteRoute' => 'employee.supplier.destroy',
            'search' => $search,
            'columns' => [
                ['label' => 'ID', 'field' => 'id_supplier'],
                ['label' => 'Nama supplier', 'field' => 'nama_supplier'],
                ['label' => 'Nomor HP', 'field' => 'no_hp'],
                ['label' => 'Alamat', 'field' => 'alamat'],
            ],
            'rows' => $suppliers,
        ]);
    }

    public function create(): View
    {
        return $this->form();
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateSupplier($request);
        $data['id_supplier'] = CodeGeneratorService::generate('supplier');

        Supplier::query()->create($data);

        return redirect()->route('employee.supplier.index')->with('status', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier): View
    {
        return $this->form($supplier);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validateSupplier($request));

        return redirect()->route('employee.supplier.index')->with('status', 'Data supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->pembelian()->exists()) {
            return back()->withErrors(['delete' => 'Supplier ini sudah digunakan pada transaksi pembelian dan tidak dapat dihapus.']);
        }

        $supplier->delete();

        return redirect()->route('employee.supplier.index')->with('status', 'Supplier berhasil dihapus.');
    }

    private function form(?Supplier $supplier = null): View
    {
        return view('masters.form', [
            'title' => $supplier ? 'Ubah supplier' : 'Tambah supplier',
            'fields' => [
                ['name' => 'nama_supplier', 'label' => 'Nama supplier', 'type' => 'text', 'required' => true, 'maxlength' => 100],
                ['name' => 'no_hp', 'label' => 'Nomor HP', 'type' => 'text', 'required' => false, 'maxlength' => 20],
                ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea', 'required' => false, 'maxlength' => 255],
            ],
            'values' => $supplier?->only(['nama_supplier', 'no_hp', 'alamat']) ?? [],
            'formAction' => $supplier
                ? route('employee.supplier.update', $supplier)
                : route('employee.supplier.store'),
            'cancelRoute' => route('employee.supplier.index'),
            'method' => $supplier ? 'PUT' : null,
            'submitLabel' => $supplier ? 'Simpan perubahan' : 'Simpan supplier',
        ]);
    }

    private function validateSupplier(Request $request): array
    {
        return $request->validate([
            'nama_supplier' => ['required', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
