<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\Pegawai;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('owner', function (Blueprint $table): void {
            $table->increments('id_owner');
            $table->string('nama_owner', 100);
            $table->string('username', 50)->unique();
            $table->string('password');
        });

        Schema::create('pegawai', function (Blueprint $table): void {
            $table->string('id_pegawai', 10)->primary();
            $table->string('nama_pegawai', 100);
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('jabatan', 50)->nullable();
        });

        Schema::create('persediaan', function (Blueprint $table): void {
            $table->string('id_persediaan', 10)->primary();
            $table->string('id_pegawai', 10);
            $table->string('nama_barang', 100);
            $table->string('kategori_barang', 50)->nullable();
            $table->string('satuan', 20)->default('pcs');
            $table->decimal('stok', 15, 3)->default(0);
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
            $table->string('satuan', 20)->default('pcs');
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('jumlah_barang', 15, 3);
            $table->decimal('subtotal', 15, 2);
        });

        Schema::create('pembelian', function (Blueprint $table): void {
            $table->string('id_pembelian', 10)->primary();
            $table->date('tanggal');
        });

        Schema::create('detail_pembelian', function (Blueprint $table): void {
            $table->increments('id_detail');
            $table->string('id_pembelian', 10);
            $table->decimal('subtotal', 15, 2);
        });

        DB::statement(
            'CREATE VIEW view_laporanpenjualan AS
            SELECT sale.id_penjualan, sale.tanggal, COALESCE(SUM(detail.subtotal), 0) AS total_penjualan
            FROM penjualan AS sale
            LEFT JOIN detail_penjualan AS detail ON detail.id_penjualan = sale.id_penjualan
            GROUP BY sale.id_penjualan, sale.tanggal'
        );
        DB::statement(
            'CREATE VIEW view_laporanpembelian AS
            SELECT purchase.id_pembelian, purchase.tanggal, COALESCE(SUM(detail.subtotal), 0) AS total_pembelian
            FROM pembelian AS purchase
            LEFT JOIN detail_pembelian AS detail ON detail.id_pembelian = purchase.id_pembelian
            GROUP BY purchase.id_pembelian, purchase.tanggal'
        );
    }

    public function test_dashboard_shows_seven_day_sales_and_predicts_stock_coverage(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 12:00:00'));

        try {
            $employee = Pegawai::query()->create([
                'id_pegawai' => 'PGW0001',
                'nama_pegawai' => 'Pegawai Uji',
                'username' => 'pegawai-uji',
                'password' => 'password-lama',
                'jabatan' => 'Pegawai',
            ]);

            DB::table('persediaan')->insert([
                [
                    'id_persediaan' => 'BRG0001',
                    'id_pegawai' => 'PGW0001',
                    'nama_barang' => 'Paku Beton',
                    'satuan' => 'dus',
                    'stok' => 5,
                ],
                [
                    'id_persediaan' => 'BRG0002',
                    'id_pegawai' => 'PGW0001',
                    'nama_barang' => 'Pipa PVC',
                    'satuan' => 'batang',
                    'stok' => 100,
                ],
                [
                    'id_persediaan' => 'BRG0003',
                    'id_pegawai' => 'PGW0001',
                    'nama_barang' => 'Barang dengan satuan lama',
                    'satuan' => 'kg',
                    'stok' => 20,
                ],
            ]);

            DB::table('penjualan')->insert([
                ['id_penjualan' => 'PJ-TODAY', 'id_pelanggan' => 'PLG0001', 'id_pegawai' => 'PGW0001', 'tanggal' => '2026-11-20'],
                ['id_penjualan' => 'PJ-YESTERDAY', 'id_pelanggan' => 'PLG0001', 'id_pegawai' => 'PGW0001', 'tanggal' => '2026-11-19'],
                ['id_penjualan' => 'PJ-OLD', 'id_pelanggan' => 'PLG0001', 'id_pegawai' => 'PGW0001', 'tanggal' => '2026-11-12'],
            ]);

            DB::table('detail_penjualan')->insert([
                ['id_penjualan' => 'PJ-TODAY', 'id_persediaan' => 'BRG0001', 'satuan' => 'dus', 'harga_satuan' => 7500, 'jumlah_barang' => 4, 'subtotal' => 30000],
                ['id_penjualan' => 'PJ-YESTERDAY', 'id_persediaan' => 'BRG0001', 'satuan' => 'dus', 'harga_satuan' => 10000, 'jumlah_barang' => 2, 'subtotal' => 20000],
                ['id_penjualan' => 'PJ-OLD', 'id_persediaan' => 'BRG0001', 'satuan' => 'dus', 'harga_satuan' => 10000, 'jumlah_barang' => 10, 'subtotal' => 100000],
                ['id_penjualan' => 'PJ-YESTERDAY', 'id_persediaan' => 'BRG0003', 'satuan' => 'unit', 'harga_satuan' => 1000, 'jumlah_barang' => 100, 'subtotal' => 100000],
            ]);

            DB::table('pembelian')->insert([
                ['id_pembelian' => 'PB-TODAY', 'tanggal' => '2026-11-20'],
            ]);
            DB::table('detail_pembelian')->insert([
                ['id_pembelian' => 'PB-TODAY', 'subtotal' => 15000],
            ]);

            $this->actingAs($employee, 'employee')
                ->get(route('employee.dashboard'))
                ->assertOk()
                ->assertSee('Rp30.000')
                ->assertSee('Jumlah transaksi')
                ->assertSee('Rp150.000')
                ->assertSee('Paku Beton')
                ->assertSee('Diperkirakan habis dalam')
                ->assertDontSee('Pipa PVC')
                ->assertDontSee('Barang dengan satuan lama');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_owner_dashboard_links_sales_and_purchase_reports(): void
    {
        $owner = Owner::query()->create([
            'nama_owner' => 'Owner Uji',
            'username' => 'owner-uji',
            'password' => 'password-lama',
        ]);

        $this->actingAs($owner, 'owner')
            ->get(route('owner.dashboard'))
            ->assertOk()
            ->assertSee(route('owner.reports.sales'), false)
            ->assertSee(route('owner.reports.purchases'), false);
    }
}
