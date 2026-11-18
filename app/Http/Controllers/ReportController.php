<?php

namespace App\Http\Controllers;

use App\Models\Views\LaporanPembelianView;
use App\Models\Views\LaporanPenjualanView;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function sales(Request $request): View
    {
        $filters = $this->validateDateFilters($request);
        $reports = LaporanPenjualanView::query()
            ->when($filters['from'], fn ($query, string $date) => $query->whereDate('tanggal', '>=', $date))
            ->when($filters['to'], fn ($query, string $date) => $query->whereDate('tanggal', '<=', $date))
            ->orderByDesc('tanggal')
            ->orderByDesc('id_penjualan')
            ->paginate(20)
            ->withQueryString();

        return view('reports.index', [
            'title' => 'Laporan penjualan',
            'type' => 'sales',
            'reports' => $reports,
            'filters' => $filters,
        ]);
    }

    public function purchases(Request $request): View
    {
        $filters = $this->validateDateFilters($request);
        $reports = LaporanPembelianView::query()
            ->when($filters['from'], fn ($query, string $date) => $query->whereDate('tanggal', '>=', $date))
            ->when($filters['to'], fn ($query, string $date) => $query->whereDate('tanggal', '<=', $date))
            ->orderByDesc('tanggal')
            ->orderByDesc('id_pembelian')
            ->paginate(20)
            ->withQueryString();

        return view('reports.index', [
            'title' => 'Laporan pembelian',
            'type' => 'purchases',
            'reports' => $reports,
            'filters' => $filters,
        ]);
    }

    private function validateDateFilters(Request $request): array
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

        return $filters;
    }
}
