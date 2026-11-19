<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\Pegawai;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OwnerRegistrationTest extends TestCase
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

        foreach ([
            'pelanggan',
            'supplier',
            'persediaan',
            'penjualan',
            'detail_penjualan',
            'pembelian',
            'detail_pembelian',
        ] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->increments('id');
            });
        }

        foreach (['view_kartu_stok', 'view_laporanpembelian', 'view_laporanpenjualan'] as $viewName) {
            DB::statement("CREATE VIEW {$viewName} AS SELECT 1 AS id");
        }
    }

    public function test_first_owner_can_register_once_when_employee_accounts_exist(): void
    {
        Pegawai::query()->create([
            'id_pegawai' => 'PGW0001',
            'nama_pegawai' => 'Pegawai Awal',
            'username' => 'pegawai-awal',
            'password' => 'password-pegawai',
            'jabatan' => 'Pegawai',
        ]);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Buat akun Owner pertama');

        $this->get(route('register.owner'))
            ->assertOk()
            ->assertSee('Buat akun Owner pertama');

        $this->post(route('register.owner.store'), [
            'nama_owner' => 'Owner Pertama',
            'username' => 'owner-pertama',
            'password' => 'password-owner',
            'password_confirmation' => 'password-owner',
        ])->assertRedirect(route('owner.login'));

        $this->assertDatabaseCount('owner', 1);
        $this->assertTrue(Hash::check('password-owner', Owner::query()->where('username', 'owner-pertama')->value('password')));

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Akun Owner tambahan hanya bisa dibuat oleh Owner yang sudah masuk.');
        $this->get(route('register.owner'))
            ->assertRedirect(route('owner.login'));

        $this->post(route('register.owner.store'), [
            'nama_owner' => 'Owner Kedua',
            'username' => 'owner-kedua',
            'password' => 'password-owner-dua',
            'password_confirmation' => 'password-owner-dua',
        ])->assertRedirect(route('owner.login'));

        $this->assertDatabaseCount('owner', 1);
    }

    public function test_first_owner_registration_redirects_to_setup_when_no_users_exist(): void
    {
        $this->get(route('register.owner'))
            ->assertRedirect(route('setup'));

        $this->assertDatabaseCount('owner', 0);
    }
}
