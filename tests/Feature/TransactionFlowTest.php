<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\Pelanggan;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\Persediaan;
use App\Models\Supplier;
use App\Services\ReceiptOcrService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
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
            $table->decimal('harga_jual', 15, 2)->nullable();
        });

        Schema::create('penjualan', function (Blueprint $table): void {
            $table->string('id_penjualan', 10)->primary();
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
            'harga_jual' => 75000,
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

    public function test_purchase_form_does_not_show_delivery_status_field(): void
    {
        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.pembelian.create'))
            ->assertOk()
            ->assertDontSee('Status pengiriman');
    }

    public function test_purchase_actions_support_edit_delete_and_receive_without_detail_action(): void
    {
        $purchase = Pembelian::query()->create([
            'id_pembelian' => 'PB000001',
            'id_supplier' => $this->supplier->id_supplier,
            'id_pegawai' => $this->employee->id_pegawai,
            'tanggal' => today()->toDateString(),
            'status_pengiriman' => 'Diproses',
        ]);
        $purchase->detailPembelian()->create([
            'id_persediaan' => $this->product->id_persediaan,
            'satuan' => 'sak',
            'harga_beli' => 60000,
            'jumlah_barang' => 4,
        ]);

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.pembelian.index'))
            ->assertOk()
            ->assertSee('Ubah')
            ->assertSee('Hapus')
            ->assertSee('Terima')
            ->assertSee('Detail pembelian')
            ->assertDontSee('>Detail<');

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.pembelian.details'))
            ->assertOk()
            ->assertSee('Detail pembelian')
            ->assertSee('Semen')
            ->assertSee('PB000001');

        $this->actingAs($this->employee, 'employee')
            ->put(route('employee.pembelian.update', $purchase), [
                'id_supplier' => $this->supplier->id_supplier,
                'tanggal' => today()->toDateString(),
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => 6,
                    'harga_beli' => 55000,
                ]],
            ])
            ->assertRedirect(route('employee.pembelian.index'));

        $this->assertDatabaseHas('detail_pembelian', [
            'id_pembelian' => $purchase->id_pembelian,
            'jumlah_barang' => 6,
            'harga_beli' => 55000,
        ]);

        $this->actingAs($this->employee, 'employee')
            ->delete(route('employee.pembelian.destroy', $purchase))
            ->assertRedirect(route('employee.pembelian.index'));

        $this->assertDatabaseMissing('pembelian', [
            'id_pembelian' => $purchase->id_pembelian,
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

    public function test_cashier_can_create_customer_with_shipping_address_during_checkout(): void
    {
        $response = $this->actingAs($this->employee, 'employee')
            ->post(route('employee.penjualan.cashier.store'), [
                'pelanggan_baru' => [
                    'nama_pelanggan' => 'Pelanggan Kasir',
                    'no_hp' => '08123456789',
                    'alamat' => 'Jl. Contoh No. 10',
                ],
                'tanggal' => today()->toDateString(),
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => 1,
                    'harga_satuan' => 75000,
                ]],
            ]);

        $customer = Pelanggan::query()->where('nama_pelanggan', 'Pelanggan Kasir')->firstOrFail();
        $response->assertRedirect(route('employee.penjualan.show', Penjualan::query()->firstOrFail()));
        $this->assertSame('08123456789', $customer->no_hp);
        $this->assertSame('Jl. Contoh No. 10', $customer->alamat);
        $this->assertDatabaseHas('penjualan', [
            'id_pelanggan' => $customer->id_pelanggan,
        ]);
        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.pelanggan.index'))
            ->assertSee('Pelanggan Kasir')
            ->assertSee('Jl. Contoh No. 10');
    }

    public function test_sale_requires_customer_details(): void
    {
        $response = $this->actingAs($this->employee, 'employee')
            ->post(route('employee.penjualan.cashier.store'), [
                'tanggal' => today()->toDateString(),
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => 1,
                    'harga_satuan' => 75000,
                ]],
            ]);

        $response->assertSessionHasErrors('id_pelanggan');
        $this->assertDatabaseCount('penjualan', 0);
    }

    public function test_new_cashier_customer_requires_a_shipping_address(): void
    {
        $response = $this->actingAs($this->employee, 'employee')
            ->post(route('employee.penjualan.cashier.store'), [
                'pelanggan_baru' => [
                    'nama_pelanggan' => 'Pelanggan Tanpa Alamat',
                ],
                'tanggal' => today()->toDateString(),
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => 1,
                    'harga_satuan' => 75000,
                ]],
            ]);

        $response->assertSessionHasErrors('pelanggan_baru.alamat');
        $this->assertDatabaseCount('pelanggan', 1);
        $this->assertDatabaseCount('penjualan', 0);
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

    public function test_cashier_shows_inventory_selling_price(): void
    {
        $sale = Penjualan::query()->create([
            'id_penjualan' => 'PJL0001',
            'id_pelanggan' => $this->customer->id_pelanggan,
            'id_pegawai' => $this->employee->id_pegawai,
            'tanggal' => today()->toDateString(),
        ]);
        $sale->detailPenjualan()->create([
            'id_persediaan' => $this->product->id_persediaan,
            'harga_satuan' => 60000,
            'jumlah_barang' => 1,
        ]);

        $this->actingAs($this->employee, 'employee')
            ->get(route('employee.penjualan.index'))
            ->assertOk()
            ->assertSee('Kasir')
            ->assertSee('Semen')
            ->assertSee('Stok: 5 sak')
            ->assertSee('Rp75.000')
            ->assertDontSee('Rp60.000')
            ->assertSee('Keranjang')
            ->assertDontSee('Pilih pelanggan')
            ->assertSee('Nama pelanggan')
            ->assertSee('Alamat pengiriman')
            ->assertSee('css/cashier.css');
    }

    public function test_cashier_uses_inventory_price_instead_of_submitted_price(): void
    {
        $response = $this->actingAs($this->employee, 'employee')
            ->post(route('employee.penjualan.cashier.store'), [
                'pelanggan_baru' => [
                    'nama_pelanggan' => 'Pelanggan Kasir',
                    'alamat' => 'Jl. Contoh No. 10',
                ],
                'tanggal' => today()->toDateString(),
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => 1,
                    'harga_satuan' => 100,
                ]],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('detail_penjualan', [
            'id_persediaan' => $this->product->id_persediaan,
            'harga_satuan' => 75000,
        ]);
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
                'items' => [[
                    'id_persediaan' => $this->product->id_persediaan,
                    'jumlah_barang' => 4,
                    'harga_beli' => 60000,
                ]],
            ]);

        $purchase = Pembelian::query()->firstOrFail();
        $response->assertRedirect(route('employee.pembelian.index'));
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

        $response->assertRedirect(route('employee.pembelian.index'));
        $purchase = Pembelian::query()->firstOrFail();
        $this->assertSame('Diproses', $purchase->status_pengiriman);
        $this->assertDatabaseHas('detail_pembelian', [
            'id_persediaan' => $this->product->id_persediaan,
            'jumlah_barang' => '1.125',
            'satuan' => 'meter',
        ]);
        $this->assertDatabaseHas('persediaan', [
            'id_persediaan' => $this->product->id_persediaan,
            'stok' => '3.250',
            'satuan' => 'meter',
        ]);

        $this->actingAs($this->employee, 'employee')
            ->patch(route('employee.pembelian.status', $purchase), ['status_pengiriman' => 'Diterima'])
            ->assertRedirect(route('employee.pembelian.show', $purchase));

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
                'harga_jual' => 75000,
                'stok' => 5,
            ])
            ->assertSessionHasErrors('satuan');

        $this->assertDatabaseHas('persediaan', [
            'id_persediaan' => $this->product->id_persediaan,
            'satuan' => 'sak',
        ]);
    }

    public function test_purchase_ocr_api_returns_json_and_receipt_token(): void
    {
        Storage::fake('local');

        $this->mock(ReceiptOcrService::class, function ($mock): void {
            $mock->shouldReceive('extract')->once()->andReturn('NOTA PEMBELIAN');
        });

        $response = $this->actingAs($this->employee, 'employee')
            ->postJson(route('employee.pembelian.ocr.api'), [
                'receipt' => UploadedFile::fake()->createWithContent(
                    'nota.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
                )->mimeType('image/png'),
            ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Nota berhasil diproses.',
                'data' => [
                    'hasil_ocr' => 'NOTA PEMBELIAN',
                ],
            ])
            ->assertJsonStructure([
                'data' => ['receipt_token', 'hasil_ocr', 'file_path'],
            ]);

        $token = $response->json('data.receipt_token');
        $this->assertTrue(is_string($token) && Str::isUuid($token));
        $this->assertArrayHasKey(
            $token,
            session('receipt_drafts', [])
        );
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
