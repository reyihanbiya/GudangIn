<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengirimanController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ])['search'] ?? null;

        $transactions = Penjualan::query()
            ->with('pelanggan')
            ->when($search, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('id_pengiriman', 'like', "%{$search}%")
                        ->orWhere('id_penjualan', 'like', "%{$search}%")
                        ->orWhereHas('pelanggan', function ($query) use ($search): void {
                            $query->where('nama_pelanggan', 'like', "%{$search}%")
                                ->orWhere('no_hp', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('tanggal')
            ->orderByDesc('id_penjualan')
            ->paginate(10)
            ->withQueryString();

        return view('pengiriman.index', compact('transactions', 'search'));
    }

    public function exportPdf(Penjualan $penjualan)
    {
        $penjualan->load(['pelanggan', 'detailPenjualan.persediaan']);

        $pdf = Pdf::loadView('pdf.pelanggan', [
            'pelanggan' => $penjualan->pelanggan,
            'penjualan' => $penjualan,
            'idPengiriman' => $penjualan->id_pengiriman,
            'detailBarang' => $penjualan->detailPenjualan,
            'tanggalCetak' => now()->format('d-m-Y H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('pengiriman-' . $penjualan->id_pengiriman . '.pdf');
    }
}
