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
}
