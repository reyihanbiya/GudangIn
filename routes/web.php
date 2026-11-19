<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeRegistrationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OwnerAccountController;
use App\Http\Controllers\OwnerRegistrationController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PelangganController;
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
Route::get('/register', [EmployeeRegistrationController::class, 'chooseRole'])->name('register');
Route::get('/register/pegawai', [EmployeeRegistrationController::class, 'create'])->name('register.employee');
Route::post('/register/pegawai', [EmployeeRegistrationController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('register.employee.store');
Route::get('/register/owner', [OwnerRegistrationController::class, 'create'])->name('register.owner');
Route::post('/register/owner', [OwnerRegistrationController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('register.owner.store');
Route::get('/owner/login', [AuthController::class, 'ownerForm'])->name('owner.login');
Route::post('/owner/login', [AuthController::class, 'ownerLogin'])
    ->middleware('throttle:5,1')
    ->name('owner.login.store');

Route::middleware('auth:employee')->prefix('employee')->name('employee.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'employee'])->name('dashboard');
    Route::resource('pelanggan', PelangganController::class)->except(['show']);
    Route::get('/persediaan/{persediaan}/kartu-stok', [PersediaanController::class, 'stockCard'])
        ->name('persediaan.stock-card');
    Route::resource('persediaan', PersediaanController::class)->except(['show']);
    Route::resource('supplier', SupplierController::class)->except(['show']);
    Route::get('/penjualan', [PenjualanController::class, 'index'])->name('penjualan.index');
    Route::get('/penjualan/create', [PenjualanController::class, 'create'])->name('penjualan.create');
    Route::get('/penjualan/riwayat', [PenjualanController::class, 'history'])->name('penjualan.history');
    Route::post('/penjualan/kasir', [PenjualanController::class, 'cashierStore'])->name('penjualan.cashier.store');
    Route::post('/penjualan', [PenjualanController::class, 'store'])->name('penjualan.store');
    Route::get('/penjualan/{penjualan}', [PenjualanController::class, 'show'])->name('penjualan.show');
    Route::get('/pembelian', [PembelianController::class, 'index'])->name('pembelian.index');
    Route::get('/pembelian/create', [PembelianController::class, 'create'])->name('pembelian.create');
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
    Route::get('/pembelian/{pembelian}', [PembelianController::class, 'show'])->name('pembelian.show');
    Route::post('/logout', [AuthController::class, 'employeeLogout'])->name('logout');
});

Route::middleware('auth:owner')->prefix('owner')->name('owner.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'owner'])->name('dashboard');
    Route::get('/accounts', [OwnerAccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts', [OwnerAccountController::class, 'store'])->name('accounts.store');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/purchases', [ReportController::class, 'purchases'])->name('reports.purchases');
    Route::post('/logout', [AuthController::class, 'ownerLogout'])->name('logout');
});
