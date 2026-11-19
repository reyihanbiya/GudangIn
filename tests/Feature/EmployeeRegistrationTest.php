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

class EmployeeRegistrationTest extends TestCase
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

    public function test_anyone_can_register_as_employee_after_initial_setup(): void
    {
        Owner::query()->create([
            'nama_owner' => 'Owner Utama',
            'username' => 'owner-utama',
            'password' => 'password-owner',
        ]);
        Pegawai::query()->create([
            'id_pegawai' => 'PGW0001',
            'nama_pegawai' => 'Pegawai Lama',
            'username' => 'pegawai-lama',
            'password' => 'password-pegawai',
            'jabatan' => 'Pegawai',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Buat akun baru');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Daftar sebagai Pegawai')
            ->assertSee('Akun Owner tambahan hanya bisa dibuat oleh Owner yang sudah masuk.')
            ->assertSee('Masuk sebagai Owner');

        $this->get(route('register.employee'))
            ->assertOk()
            ->assertSee('Buat akun Pegawai');

        $this->post(route('register.employee.store'), [
            'nama_pegawai' => 'Pegawai Baru',
            'username' => 'pegawai-baru',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
            'jenis_akun' => 'owner',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('pegawai', [
            'id_pegawai' => 'PGW0002',
            'nama_pegawai' => 'Pegawai Baru',
            'username' => 'pegawai-baru',
            'jabatan' => 'Pegawai',
        ]);
        $this->assertDatabaseCount('owner', 1);
        $this->assertTrue(Hash::check('password-baru', Pegawai::query()->where('username', 'pegawai-baru')->value('password')));
    }

    public function test_employee_registration_is_not_available_before_initial_setup(): void
    {
        $this->get(route('register'))
            ->assertRedirect(route('setup'));

        $this->post(route('register.employee.store'), [
            'nama_pegawai' => 'Pegawai Baru',
            'username' => 'pegawai-baru',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertRedirect(route('setup'));

        $this->assertDatabaseCount('pegawai', 0);
    }
}
