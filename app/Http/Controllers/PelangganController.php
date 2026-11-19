<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Services\CodeGeneratorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PelangganController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ])['search'] ?? null;

        $customers = Pelanggan::query()
            ->when($search, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nama_pelanggan', 'like', "%{$search}%")
                        ->orWhere('no_hp', 'like', "%{$search}%")
                        ->orWhere('alamat', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama_pelanggan')
            ->paginate(10)
            ->withQueryString();

        return view('masters.index', [
            'title' => 'Pelanggan',
            'description' => 'Kelola nama, nomor telepon, dan alamat pelanggan.',
            'createRoute' => 'employee.pelanggan.create',
            'editRoute' => 'employee.pelanggan.edit',
            'deleteRoute' => 'employee.pelanggan.destroy',
            'pdfRoute' => 'employee.pelanggan.pdf',
            'search' => $search,
            'columns' => [
                ['label' => 'Nama pelanggan', 'field' => 'nama_pelanggan'],
                ['label' => 'No. telepon', 'field' => 'no_hp'],
                ['label' => 'Alamat', 'field' => 'alamat'],
            ],
            'rows' => $customers,
        ]);
    }

    public function create(): View
    {
        return $this->form();
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCustomer($request);
        $data['id_pelanggan'] = CodeGeneratorService::generate('pelanggan');

        Pelanggan::query()->create($data);

        return redirect()->route('employee.pelanggan.index')
            ->with('status', 'Data pengiriman berhasil ditambahkan.');
    }

    public function edit(Pelanggan $pelanggan): View
    {
        return $this->form($pelanggan);
    }

    public function update(Request $request, Pelanggan $pelanggan): RedirectResponse
    {
        $pelanggan->update($this->validateCustomer($request));

        return redirect()->route('employee.pelanggan.index')
            ->with('status', 'Data pengiriman berhasil diperbarui.');
    }

    public function destroy(Pelanggan $pelanggan): RedirectResponse
    {
        if ($pelanggan->penjualan()->exists()) {
            return back()->withErrors([
                'delete' => 'Pelanggan ini sudah memiliki transaksi dan tidak dapat dihapus.',
            ]);
        }

        $pelanggan->delete();

        return redirect()->route('employee.pelanggan.index')
            ->with('status', 'Data pengiriman berhasil dihapus.');
    }

    /**
     * Mengunduh PDF untuk satu pelanggan yang dipilih.
     */
    public function exportPdf(Pelanggan $pelanggan)
    {
        $penjualan = $pelanggan->penjualan()
            ->with('detailPenjualan.persediaan')
            ->orderByDesc('tanggal')
            ->orderByDesc('id_penjualan')
            ->first();

        $pdf = Pdf::loadView('pdf.pelanggan', [
            'pelanggan' => $pelanggan,
            'penjualan' => $penjualan,
            'idPengiriman' => $penjualan?->id_pengiriman ?? $penjualan?->id_penjualan ?? '—',
            'detailBarang' => $penjualan?->detailPenjualan ?? collect(),
            'tanggalCetak' => now()->format('d-m-Y H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('pengiriman-' . $pelanggan->id_pelanggan . '.pdf');
    }

    private function form(?Pelanggan $pelanggan = null): View
    {
        return view('masters.form', [
            'title' => $pelanggan ? 'Ubah data pengiriman' : 'Tambah data pengiriman',
            'fields' => [
                [
                    'name' => 'nama_pelanggan',
                    'label' => 'Nama pelanggan',
                    'type' => 'text',
                    'required' => true,
                    'maxlength' => 100,
                ],
                [
                    'name' => 'no_hp',
                    'label' => 'No. telepon',
                    'type' => 'tel',
                    'required' => false,
                    'maxlength' => 20,
                ],
                [
                    'name' => 'alamat',
                    'label' => 'Alamat',
                    'type' => 'textarea',
                    'required' => false,
                    'maxlength' => 255,
                ],
            ],
            'values' => $pelanggan?->only([
                'nama_pelanggan',
                'no_hp',
                'alamat',
            ]) ?? [],
            'formAction' => $pelanggan
                ? route('employee.pelanggan.update', $pelanggan)
                : route('employee.pelanggan.store'),
            'cancelRoute' => route('employee.pelanggan.index'),
            'method' => $pelanggan ? 'PUT' : null,
            'submitLabel' => $pelanggan ? 'Simpan perubahan' : 'Simpan data pengiriman',
        ]);
    }

    private function validateCustomer(Request $request): array
    {
        return $request->validate([
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:255'],
        ]);
    }
}