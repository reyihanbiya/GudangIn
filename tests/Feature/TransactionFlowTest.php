<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\Pelanggan;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\Persediaan;
use App\Models\Supplier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransactionFlowTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $employee;
    private Pelanggan $customer;
    private Supplier $supplier;
    private Persediaan $product;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('pegawai', function (Blueprint $table): void {
            $table->string('id_pegawai', 10)->primary();
            $table->string('nama_pegawai', 100);
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('jabatan', 50)->nullable();
        });

        Schema::create('pelanggan', function (Blueprint $table): void {
            $table->string('id_pelanggan', 10)->primary();
            $table->string('nama_pelanggan', 100);
            $table->string('alamat', 255)->nullable();
            $table->string('no_hp', 20)->nullable();
        });

        Schema::create('supplier', function (Blueprint $table): void {
            $table->string('id_supplier', 10)->primary();
            $table->string('nama_supplier', 100);
            $table->string('alamat', 255)->nullable();
            $table->string('no_hp', 20)->nullable();
        });

        Schema::create('persediaan', function (Blueprint $table): void {
            $table->string('id_persediaan', 10)->primary();
            $table->string('id_pegawai', 10);
            $table->string('nama_barang', 100);
            $table->string('kategori_barang', 50)->nullable();
            $table->string('satuan', 20)->default('unit');
            $table->decimal('stok', 15, 3)->default(0);
        });

        Schema::create('penjualan', function (Blueprint $table): void {
            $table->string('id_penjualan', 10)->primary();
            $table->string('id_pengiriman', 10)->nullable()->unique();
            $table->string('id_pelanggan', 10);
            $table->string('id_pegawai', 10);
            $table->date('tanggal');
        });

        Schema::create('detail_penjualan', function (Blueprint $table): void {
            $table->increments('id_detail');
            $table->string('id_penjualan', 10);
            $table->string('id_persediaan', 10);
            $table->string('satuan', 20)->default('unit');
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('jumlah_barang', 15, 3);
            $table->decimal('subtotal', 15, 2)->storedAs('harga_satuan * jumlah_barang');
        });

        Schema::create('pembelian', function (Blueprint $table): void {
            $table->string('id_pembelian', 10)->primary();
            $table->string('id_supplier', 10);
            $table->string('id_pegawai', 10);
            $table->date('tanggal');
            $table->string('status_pengiriman', 30)->default('Diproses');
        });

        Schema::create('detail_pembelian', function (Blueprint $table): void {
            $table->increments('id_detail');
            $table->string('id_pembelian', 10);
            $table->string('id_persediaan', 10);
            $table->string('satuan', 20)->default('unit');
            $table->decimal('harga_beli', 15, 2);
            $table->decimal('jumlah_barang', 15, 3);
            $table->decimal('subtotal', 15, 2)->storedAs('harga_beli * jumlah_barang');
        });

        $this->employee = Pegawai::query()->create([
            'id_pegawai' => 'PGW0001',
            'nama_pegawai' => 'Pegawai Uji',
            'username' => 'pegawai-uji',
            'password' => 'secret-password',
            'jabatan' => 'Pegawai',
        ]);
        $this->customer = Pelanggan::query()->create([
            'id_pelanggan' => 'PLG0001',
            'nama_pelanggan' => 'Pelanggan Uji',
        ]);
        $this->supplier = Supplier::query()->create([
            'id_supplier' => 'SUP0001',
            'nama_supplier' => 'Supplier Uji',
        ]);
        $this->product = Persediaan::query()->create([
            'id_persediaan' => 'BRG0001',
            'id_pegawai' => $this->employee->id_pegawai,
            'nama_barang' => 'Semen',
            'satuan' => 'sak',
            'stok' => 5,
        ]);
    }

    public function test_sale_reduces_stock_and_persists_line_items(): void
    {
        $response = $this->actingAs($this->employee, 'employee')
            ->post(route('employee.penjualan.store'), [
                'id_pelanggan' => $this->customer->id_pelanggan,
                'tanggal' => today()->toDateString(),
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => 2,
                    'harga_satuan' => 75000,
                ]],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('persediaan', [
            'id_persediaan' => $this->product->id_persediaan,
            'stok' => 3,
        ]);
        $this->assertDatabaseHas('detail_penjualan', [
            'id_persediaan' => $this->product->id_persediaan,
            'jumlah_barang' => 2,
            'satuan' => 'sak',
            'harga_satuan' => 75000,
        ]);
        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.penjualan.show', Penjualan::query()->firstOrFail()))
            ->assertSee('2 sak');
    }

    public function test_each_sale_gets_a_unique_shipping_id_and_appears_in_shipping_list(): void
    {
        foreach ([1, 2] as $quantity) {
            $this->actingAs($this->employee, 'employee')
                ->post(route('employee.penjualan.store'), [
                    'id_pelanggan' => $this->customer->id_pelanggan,
                    'tanggal' => today()->toDateString(),
                    'items' => [[
                        'id_persediaan' => $this->product->id_persediaan,
                        'jumlah_barang' => $quantity,
                        'harga_satuan' => 75000,
                    ]],
                ])
                ->assertRedirect();
        }

        $transactions = Penjualan::query()->orderBy('id_penjualan')->get();
        $shippingIds = $transactions->pluck('id_pengiriman');

        $this->assertCount(2, $shippingIds->unique());
        $this->assertStringStartsWith('PNG', $shippingIds[0]);
        $this->assertStringStartsWith('PNG', $shippingIds[1]);

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.pengiriman.index'))
            ->assertOk()
            ->assertSee($shippingIds[0])
            ->assertSee($shippingIds[1])
            ->assertSee('action-print', false)
            ->assertSee('action-edit', false)
            ->assertSee('action-delete', false)
            ->assertSee('M3 6h11v11H3z', false)
            ->assertSee('aria-label="Hapus transaksi"', false);

        $response = $this->actingAs($this->employee, 'employee')
            ->get(route('employee.pengiriman.pdf', $transactions[0]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_migration_assigns_shipping_ids_to_existing_sales(): void
    {
        Penjualan::query()->create([
            'id_penjualan' => 'PJ260001',
            'id_pelanggan' => $this->customer->id_pelanggan,
            'id_pegawai' => $this->employee->id_pegawai,
            'tanggal' => today()->toDateString(),
        ]);

        $migration = require database_path('migrations/2026_11_20_000000_add_id_pengiriman_to_penjualan_table.php');
        $migration->up();

        $this->assertDatabaseHas('penjualan', [
            'id_penjualan' => 'PJ260001',
            'id_pengiriman' => 'PNG0001',
        ]);
    }

    public function test_sale_accepts_fractional_quantities_in_inventory_unit(): void
    {
        $this->product->update(['satuan' => 'kg', 'stok' => 2.5]);

        $response = $this->actingAs($this->employee, 'employee')
            ->post(route('employee.penjualan.store'), [
                'id_pelanggan' => $this->customer->id_pelanggan,
                'tanggal' => today()->toDateString(),
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => '0.750',
                    'harga_satuan' => 12500,
                ]],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('detail_penjualan', [
            'id_persediaan' => $this->product->id_persediaan,
            'jumlah_barang' => '0.750',
            'satuan' => 'kg',
        ]);
        $this->assertDatabaseHas('persediaan', [
            'id_persediaan' => $this->product->id_persediaan,
            'stok' => '1.750',
            'satuan' => 'kg',
        ]);
        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.penjualan.show', Penjualan::query()->firstOrFail()))
            ->assertSee('0,75 kg');
    }

    public function test_sale_rejects_more_than_three_decimal_places(): void
    {
        $response = $this->actingAs($this->employee, 'employee')
            ->post(route('employee.penjualan.store'), [
                'id_pelanggan' => $this->customer->id_pelanggan,
                'tanggal' => today()->toDateString(),
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => '0.0001',
                    'harga_satuan' => 75000,
                ]],
            ]);

        $response->assertSessionHasErrors('items.0.jumlah_barang');
        $this->assertDatabaseCount('penjualan', 0);
        $this->assertDatabaseHas('persediaan', [
            'id_persediaan' => $this->product->id_persediaan,
            'stok' => 5,
        ]);
    }

    public function test_employee_can_download_customer_shipping_pdf_with_a_sale(): void
    {
        $penjualan = Penjualan::query()->create([
            'id_penjualan' => 'PJL260001',
            'id_pelanggan' => $this->customer->id_pelanggan,
            'id_pegawai' => $this->employee->id_pegawai,
            'tanggal' => today()->toDateString(),
        ]);
        \App\Models\DetailPenjualan::query()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_persediaan' => $this->product->id_persediaan,
            'satuan' => $this->product->satuan,
            'harga_satuan' => 75000,
            'jumlah_barang' => 2,
        ]);

        $response = $this->actingAs($this->employee, 'employee')
            ->get(route('employee.pelanggan.pdf', $this->customer));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_employee_can_edit_shipment_and_recalculate_stock(): void
    {
        $this->actingAs($this->employee, 'employee')
            ->post(route('employee.penjualan.store'), [
                'id_pelanggan' => $this->customer->id_pelanggan,
                'tanggal' => today()->toDateString(),
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => 2,
                    'harga_satuan' => 75000,
                ]],
            ])
            ->assertRedirect();

        $transaction = Penjualan::query()->firstOrFail();

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.pengiriman.edit', $transaction))
            ->assertOk()
            ->assertSee('Ubah penjualan')
            ->assertSee($transaction->id_pengiriman);

        $this->actingAs($this->employee, 'employee')
            ->put(route('employee.pengiriman.update', $transaction), [
                'id_pelanggan' => $this->customer->id_pelanggan,
                'tanggal' => today()->toDateString(),
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => 3,
                    'harga_satuan' => 80000,
                ]],
            ])
            ->assertRedirect(route('employee.pengiriman.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('detail_penjualan', [
            'id_penjualan' => $transaction->id_penjualan,
            'jumlah_barang' => 3,
            'harga_satuan' => 80000,
        ]);
        $this->assertDatabaseHas('persediaan', [
            'id_persediaan' => $this->product->id_persediaan,
            'stok' => 2,
        ]);
        $this->assertDatabaseHas('penjualan', [
            'id_penjualan' => $transaction->id_penjualan,
            'id_pengiriman' => $transaction->id_pengiriman,
        ]);
    }

    public function test_failed_shipment_edit_keeps_original_details_and_stock(): void
    {
        $this->actingAs($this->employee, 'employee')
            ->post(route('employee.penjualan.store'), [
                'id_pelanggan' => $this->customer->id_pelanggan,
                'tanggal' => today()->toDateString(),
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => 2,
                    'harga_satuan' => 75000,
                ]],
            ])
            ->assertRedirect();

        $transaction = Penjualan::query()->firstOrFail();

        $this->actingAs($this->employee, 'employee')
            ->put(route('employee.pengiriman.update', $transaction), [
                'id_pelanggan' => $this->customer->id_pelanggan,
                'tanggal' => today()->toDateString(),
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => 6,
                    'harga_satuan' => 80000,
                ]],
            ])
            ->assertSessionHasErrors('items');

        $this->assertDatabaseHas('detail_penjualan', [
            'id_penjualan' => $transaction->id_penjualan,
            'jumlah_barang' => 2,
            'harga_satuan' => 75000,
        ]);
        $this->assertDatabaseHas('persediaan', [
            'id_persediaan' => $this->product->id_persediaan,
            'stok' => 3,
        ]);
    }

    public function test_employee_can_delete_shipment_and_restore_stock(): void
    {
        $this->actingAs($this->employee, 'employee')
            ->post(route('employee.penjualan.store'), [
                'id_pelanggan' => $this->customer->id_pelanggan,
                'tanggal' => today()->toDateString(),
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => 2,
                    'harga_satuan' => 75000,
                ]],
            ])
            ->assertRedirect();

        $transaction = Penjualan::query()->firstOrFail();

        $this->actingAs($this->employee, 'employee')
            ->delete(route('employee.pengiriman.destroy', $transaction))
            ->assertRedirect(route('employee.pengiriman.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('penjualan', [
            'id_penjualan' => $transaction->id_penjualan,
        ]);
        $this->assertDatabaseMissing('detail_penjualan', [
            'id_penjualan' => $transaction->id_penjualan,
        ]);
        $this->assertDatabaseHas('persediaan', [
            'id_persediaan' => $this->product->id_persediaan,
            'stok' => 5,
        ]);
    }

    public function test_employee_can_download_customer_shipping_pdf_without_sales(): void
    {
        $response = $this->actingAs($this->employee, 'employee')
            ->get(route('employee.pelanggan.pdf', $this->customer));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_cashier_shows_stocked_products_and_last_selling_price(): void
    {
        $sale = Penjualan::query()->create([
            'id_penjualan' => 'PJL0001',
            'id_pelanggan' => $this->customer->id_pelanggan,
            'id_pegawai' => $this->employee->id_pegawai,
            'tanggal' => today()->toDateString(),
        ]);
        $sale->detailPenjualan()->create([
            'id_persediaan' => $this->product->id_persediaan,
            'harga_satuan' => 75000,
            'jumlah_barang' => 1,
        ]);

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.penjualan.index'))
            ->assertOk()
            ->assertSee('Kasir')
            ->assertSee('Semen')
            ->assertSee('Stok: 5 sak')
            ->assertSee('Rp75.000')
            ->assertSee('Keranjang')
            ->assertSee('Pilih pelanggan');
    }

    public function test_sale_with_insufficient_stock_is_rolled_back(): void
    {
        $response = $this->actingAs($this->employee, 'employee')
            ->post(route('employee.penjualan.store'), [
                'id_pelanggan' => $this->customer->id_pelanggan,
                'tanggal' => today()->toDateString(),
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => 6,
                    'harga_satuan' => 75000,
                ]],
            ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('penjualan', 0);
        $this->assertDatabaseHas('persediaan', [
            'id_persediaan' => $this->product->id_persediaan,
            'stok' => 5,
        ]);
    }

    public function test_purchase_adds_stock_only_after_receipt_is_confirmed(): void
    {
        $response = $this->actingAs($this->employee, 'employee')
            ->post(route('employee.pembelian.store'), [
                'id_supplier' => $this->supplier->id_supplier,
                'tanggal' => today()->toDateString(),
                'status_pengiriman' => 'Diproses',
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => 4,
                    'harga_beli' => 60000,
                ]],
            ]);

        $purchase = Pembelian::query()->firstOrFail();
        $response->assertRedirect(route('employee.pembelian.show', $purchase));
        $this->assertDatabaseHas('persediaan', [
            'id_persediaan' => $this->product->id_persediaan,
            'stok' => 5,
        ]);
        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.persediaan.stock-card', $this->product))
            ->assertDontSee($purchase->id_pembelian);

        $this->actingAs($this->employee, 'employee')
            ->patch(route('employee.pembelian.status', $purchase), ['status_pengiriman' => 'Diterima'])
            ->assertRedirect(route('employee.pembelian.show', $purchase));

        $this->assertDatabaseHas('persediaan', [
            'id_persediaan' => $this->product->id_persediaan,
            'stok' => 9,
        ]);
        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.persediaan.stock-card', $this->product))
            ->assertSee($purchase->id_pembelian)
            ->assertSee('Pembelian')
            ->assertSee('4 sak');

        $this->actingAs($this->employee, 'employee')
            ->patch(route('employee.pembelian.status', $purchase), ['status_pengiriman' => 'Diterima'])
            ->assertSessionHasErrors('status_pengiriman');

        $this->assertDatabaseHas('persediaan', [
            'id_persediaan' => $this->product->id_persediaan,
            'stok' => 9,
        ]);
    }

    public function test_received_purchase_adds_fractional_stock_in_item_unit(): void
    {
        $this->product->update(['satuan' => 'meter', 'stok' => 3.25]);

        $response = $this->actingAs($this->employee, 'employee')
            ->post(route('employee.pembelian.store'), [
                'id_supplier' => $this->supplier->id_supplier,
                'tanggal' => today()->toDateString(),
                'status_pengiriman' => 'Diterima',
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => '1.125',
                    'harga_beli' => 60000,
                ]],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('detail_pembelian', [
            'id_persediaan' => $this->product->id_persediaan,
            'jumlah_barang' => '1.125',
            'satuan' => 'meter',
        ]);
        $this->assertDatabaseHas('persediaan', [
            'id_persediaan' => $this->product->id_persediaan,
            'stok' => '4.375',
            'satuan' => 'meter',
        ]);
    }

    public function test_inventory_unit_cannot_change_after_transactions_exist(): void
    {
        $sale = Penjualan::query()->create([
            'id_penjualan' => 'PJL0001',
            'id_pelanggan' => $this->customer->id_pelanggan,
            'id_pegawai' => $this->employee->id_pegawai,
            'tanggal' => today()->toDateString(),
        ]);
        $sale->detailPenjualan()->create([
            'id_persediaan' => $this->product->id_persediaan,
            'harga_satuan' => 75000,
            'jumlah_barang' => 1,
        ]);

        $this->actingAs($this->employee, 'employee')
            ->put(route('employee.persediaan.update', $this->product), [
                'nama_barang' => 'Semen',
                'kategori_barang' => '',
                'satuan' => 'kg',
                'stok' => 5,
            ])
            ->assertSessionHasErrors('satuan');

        $this->assertDatabaseHas('persediaan', [
            'id_persediaan' => $this->product->id_persediaan,
            'satuan' => 'sak',
        ]);
    }

    public function test_ocr_review_is_saved_with_the_purchase_and_private_receipt(): void
    {
        Storage::fake('local');
        $token = (string) Str::uuid();
        $path = "receipts/{$token}.png";
        Storage::disk('local')->put($path, 'private receipt');

        $response = $this->actingAs($this->employee, 'employee')
            ->withSession([
                'receipt_drafts' => [
                    $token => [
                        'employee_id' => $this->employee->id_pegawai,
                        'file_path' => $path,
                        'hasil_ocr' => 'Teks OCR awal',
                    ],
                ],
            ])
            ->post(route('employee.pembelian.ocr.store'), [
                'receipt_token' => $token,
                'hasil_ocr' => 'Teks OCR yang sudah diperiksa',
                'id_supplier' => $this->supplier->id_supplier,
                'tanggal' => today()->toDateString(),
                'status_pengiriman' => 'Diproses',
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => 2,
                    'harga_beli' => 60000,
                ]],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('nota_pembelian', [
            'file_path' => $path,
            'hasil_ocr' => 'Teks OCR yang sudah diperiksa',
        ]);
        Storage::disk('local')->assertExists($path);
    }
}
