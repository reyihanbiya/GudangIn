<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

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
    }

    public function test_employee_can_create_customer_with_generated_id(): void
    {
        $employee = Pegawai::query()->create([
            'id_pegawai' => 'PGW0001',
            'nama_pegawai' => 'Pegawai Uji',
            'username' => 'pegawai-uji',
            'password' => 'secret-password',
            'jabatan' => 'Pegawai',
        ]);

        $response = $this->actingAs($employee, 'employee')
            ->post(route('employee.pelanggan.store'), [
                'nama_pelanggan' => 'Pelanggan Uji',
                'no_hp' => '08123456789',
                'alamat' => 'Bandung',
            ]);

        $response->assertRedirect(route('employee.pelanggan.index'));
        $this->assertDatabaseHas('pelanggan', [
            'id_pelanggan' => 'PLG0001',
            'nama_pelanggan' => 'Pelanggan Uji',
            'no_hp' => '08123456789',
        ]);

        $this->actingAs($employee, 'employee')
            ->get(route('employee.pelanggan.index'))
            ->assertOk()
            ->assertSee('app-layout')
            ->assertSee('Pengiriman')
            ->assertSee('Menu utama')
            ->assertSee('Nama pelanggan')
            ->assertSee('No. telepon')
            ->assertSee('Alamat')
            ->assertDontSee('<th>ID</th>', false);
    }

    public function test_employee_can_create_inventory_with_fractional_stock_and_unit(): void
    {
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
            $table->date('tanggal');
        });
        Schema::create('detail_penjualan', function (Blueprint $table): void {
            $table->increments('id_detail');
            $table->string('id_penjualan', 10);
            $table->string('id_persediaan', 10);
            $table->decimal('harga_satuan', 15, 2);
        });

        $employee = Pegawai::query()->create([
            'id_pegawai' => 'PGW0001',
            'nama_pegawai' => 'Pegawai Uji',
            'username' => 'pegawai-uji',
            'password' => 'secret-password',
            'jabatan' => 'Pegawai',
        ]);

        $this->actingAs($employee, 'employee')
            ->post(route('employee.persediaan.store'), [
                'nama_barang' => 'Pasir',
                'kategori_barang' => 'Material',
                'satuan' => 'kg',
                'stok' => '12.750',
            ])
            ->assertRedirect(route('employee.persediaan.index'));

        $this->assertDatabaseHas('persediaan', [
            'id_persediaan' => 'BRG0001',
            'nama_barang' => 'Pasir',
            'satuan' => 'kg',
            'stok' => '12.750',
        ]);

        $this->actingAs($employee, 'employee')
            ->get(route('employee.persediaan.create'))
            ->assertOk()
            ->assertSee('data-custom-select', false)
            ->assertSee('Kilogram (kg)')
            ->assertSee('Lainnya');

        $this->actingAs($employee, 'employee')
            ->get(route('employee.persediaan.index'))
            ->assertOk()
            ->assertSee('12,75 kg')
            ->assertSee('Harga jual / satuan')
            ->assertSee('Stok menipis')
            ->assertDontSee('Kartu stok');

        $this->actingAs($employee, 'employee')
            ->post(route('employee.persediaan.store'), [
                'nama_barang' => 'Tali',
                'kategori_barang' => 'Aksesoris',
                'satuan' => '__custom__',
                'satuan_custom' => 'ikat',
                'stok' => '1',
            ])
            ->assertRedirect(route('employee.persediaan.index'));

        $this->assertDatabaseHas('persediaan', [
            'nama_barang' => 'Tali',
            'satuan' => 'ikat',
        ]);

        $this->actingAs($employee, 'employee')
            ->get(route('employee.persediaan.index', ['category' => 'Aksesoris']))
            ->assertOk()
            ->assertSee('Tali')
            ->assertDontSee('Pasir')
            ->assertDontSee('Kartu stok');
    }
}
