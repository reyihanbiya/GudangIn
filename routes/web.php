<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PengirimanController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PersediaanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReceiptOcrController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/setup', [SetupController::class, 'create'])->name('setup');
Route::post('/setup', [SetupController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('setup.store');

Route::get('/login', [AuthController::class, 'employeeForm'])->name('login');
Route::post('/login', [AuthController::class, 'employeeLogin'])
    ->middleware('throttle:5,1')
    ->name('login.store');

Route::get('/owner/login', [AuthController::class, 'ownerForm'])->name('owner.login');
Route::post('/owner/login', [AuthController::class, 'ownerLogin'])
    ->middleware('throttle:5,1')
    ->name('owner.login.store');

Route::middleware('auth:employee')->prefix('employee')->name('employee.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'employee'])->name('dashboard');

    Route::get('/pengiriman', [PengirimanController::class, 'index'])->name('pengiriman.index');
    Route::get('/pengiriman/{penjualan}/edit', [PenjualanController::class, 'edit'])
        ->name('pengiriman.edit');
    Route::put('/pengiriman/{penjualan}', [PenjualanController::class, 'update'])
        ->name('pengiriman.update');
    Route::delete('/pengiriman/{penjualan}', [PenjualanController::class, 'destroy'])
        ->name('pengiriman.destroy');
    Route::get('/pengiriman/{penjualan}/pdf', [PengirimanController::class, 'exportPdf'])
        ->name('pengiriman.pdf');

    // Route cetak PDF data pengiriman pelanggan
    Route::get('/pelanggan/{pelanggan}/pdf', [PelangganController::class, 'exportPdf'])
        ->name('pelanggan.pdf');

    // Data pelanggan / pengiriman
    Route::resource('pelanggan', PelangganController::class)->except(['show']);

    // Persediaan
    Route::get('/persediaan/{persediaan}/kartu-stok', [PersediaanController::class, 'stockCard'])
        ->name('persediaan.stock-card');

    Route::resource('persediaan', PersediaanController::class)->except(['show']);

    // Supplier
    Route::resource('supplier', SupplierController::class)->except(['show']);

    // Penjualan
    Route::get('/penjualan', [PenjualanController::class, 'index'])->name('penjualan.index');
    Route::get('/penjualan/create', [PenjualanController::class, 'create'])->name('penjualan.create');
    Route::get('/penjualan/riwayat', [PenjualanController::class, 'history'])->name('penjualan.history');
    Route::post('/penjualan', [PenjualanController::class, 'store'])->name('penjualan.store');
    Route::get('/penjualan/{penjualan}', [PenjualanController::class, 'show'])->name('penjualan.show');

    // Pembelian
    Route::get('/pembelian', [PembelianController::class, 'index'])->name('pembelian.index');
    Route::get('/pembelian/create', [PembelianController::class, 'create'])->name('pembelian.create');

    // OCR nota pembelian
    Route::get('/pembelian/ocr', [ReceiptOcrController::class, 'create'])->name('pembelian.ocr.create');
    Route::post('/pembelian/ocr', [ReceiptOcrController::class, 'process'])
        ->middleware('throttle:10,1')
        ->name('pembelian.ocr.process');

    Route::get('/pembelian/ocr/{token}', [ReceiptOcrController::class, 'review'])
        ->name('pembelian.ocr.review');

    Route::post('/pembelian/ocr/{token}/discard', [ReceiptOcrController::class, 'discard'])
        ->name('pembelian.ocr.discard');

    Route::post('/pembelian/ocr/confirm', [PembelianController::class, 'storeFromOcr'])
        ->name('pembelian.ocr.store');

    Route::post('/pembelian', [PembelianController::class, 'store'])->name('pembelian.store');

    Route::get('/pembelian/{pembelian}/nota', [ReceiptOcrController::class, 'download'])
        ->name('pembelian.receipt.download');

    Route::patch('/pembelian/{pembelian}/status', [PembelianController::class, 'updateStatus'])
        ->name('pembelian.status');

    Route::get('/pembelian/{pembelian}', [PembelianController::class, 'show'])
        ->name('pembelian.show');

    // Logout employee
    Route::post('/logout', [AuthController::class, 'employeeLogout'])->name('logout');
});

Route::middleware('auth:owner')->prefix('owner')->name('owner.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'owner'])->name('dashboard');

    // Laporan owner
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/purchases', [ReportController::class, 'purchases'])->name('reports.purchases');

    // Logout owner
    Route::post('/logout', [AuthController::class, 'ownerLogout'])->name('logout');
});