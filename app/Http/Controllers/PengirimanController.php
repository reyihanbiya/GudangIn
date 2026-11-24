<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use Illuminate\View\View;

class PengirimanController extends Controller
{
    public function index(): View
    {
        $deliveries = Penjualan::query()
            ->with('pelanggan')
            ->withCount('detailPenjualan')
            ->orderByDesc('tanggal')
            ->orderByDesc('id_penjualan')
            ->paginate(15);

        return view('transactions.delivery-index', compact('deliveries'));
    }
}
