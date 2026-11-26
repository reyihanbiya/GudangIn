<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\Pegawai;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OwnerAccountManagementTest extends TestCase
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
    }

    public function test_only_an_authenticated_owner_can_create_owner_accounts(): void
    {
        $this->get(route('owner.accounts.index'))
            ->assertRedirect('/owner/login');

        $owner = Owner::query()->create([
            'nama_owner' => 'Owner Utama',
            'username' => 'owner-utama',
            'password' => 'password-lama',
        ]);

        $this->actingAs($owner, 'owner')
            ->get(route('owner.accounts.index'))
            ->assertOk()
            ->assertSee('Akun Owner')
            ->assertSee('Owner Utama');

        $this->actingAs($owner, 'owner')
            ->post(route('owner.accounts.store'), [
                'nama_owner' => 'Owner Kedua',
                'username' => 'owner-kedua',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertRedirect(route('owner.accounts.index'));

        $this->assertDatabaseHas('owner', [
            'nama_owner' => 'Owner Kedua',
            'username' => 'owner-kedua',
        ]);
        $this->assertTrue(Hash::check('password-baru', Owner::query()->where('username', 'owner-kedua')->value('password')));

    }

    public function test_employee_cannot_access_owner_account_management(): void
    {
        $employee = Pegawai::query()->create([
            'id_pegawai' => 'PGW0001',
            'nama_pegawai' => 'Pegawai Uji',
            'username' => 'pegawai-uji',
            'password' => 'password-lama',
            'jabatan' => 'Pegawai',
        ]);

        $this->actingAs($employee, 'employee')
            ->get(route('owner.accounts.index'))
            ->assertRedirect('/owner/login');
    }

    public function test_owner_can_assign_the_courier_role_to_an_employee(): void
    {
        $owner = Owner::query()->create([
            'nama_owner' => 'Owner Utama',
            'username' => 'owner-utama',
            'password' => 'password-lama',
        ]);
        $employee = Pegawai::query()->create([
            'id_pegawai' => 'PGW0001',
            'nama_pegawai' => 'Pegawai Uji',
            'username' => 'pegawai-uji',
            'password' => 'password-lama',
            'jabatan' => 'Pegawai',
        ]);

        $this->actingAs($owner, 'owner')
            ->patch(route('owner.accounts.employee-role', $employee), ['jabatan' => 'Kurir'])
            ->assertRedirect(route('owner.accounts.index'));

        $this->assertDatabaseHas('pegawai', [
            'id_pegawai' => $employee->id_pegawai,
            'jabatan' => 'Kurir',
        ]);

        $this->actingAs($owner, 'owner')
            ->get(route('owner.accounts.index'))
            ->assertOk()
            ->assertSee('Kurir');
    }
}
