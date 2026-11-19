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

class InitialAccountSetupTest extends TestCase
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
            if (! Schema::hasTable($tableName)) {
                Schema::create($tableName, function (Blueprint $table): void {
                    $table->increments('id');
                });
            }
        }

        foreach (['view_kartu_stok', 'view_laporanpembelian', 'view_laporanpenjualan'] as $viewName) {
            DB::statement("CREATE VIEW {$viewName} AS SELECT 1 AS id");
        }
    }

    public function test_initial_setup_can_create_multiple_accounts_for_both_roles(): void
    {
        $this->get(route('setup'))
            ->assertOk()
            ->assertSee('Tambah Owner')
            ->assertSee('Tambah Pegawai')
            ->assertSee('owners[0][name]', false)
            ->assertSee('employees[0][name]', false);

        $this->post(route('setup.store'), [
            'owners' => [
                [
                    'name' => 'Owner Utama',
                    'username' => 'owner-utama',
                    'password' => 'password-owner-1',
                    'password_confirmation' => 'password-owner-1',
                ],
                [
                    'name' => 'Owner Kedua',
                    'username' => 'owner-kedua',
                    'password' => 'password-owner-2',
                    'password_confirmation' => 'password-owner-2',
                ],
            ],
            'employees' => [
                [
                    'name' => 'Pegawai Satu',
                    'username' => 'pegawai-satu',
                    'password' => 'password-staff-1',
                    'password_confirmation' => 'password-staff-1',
                ],
                [
                    'name' => 'Pegawai Dua',
                    'username' => 'pegawai-dua',
                    'password' => 'password-staff-2',
                    'password_confirmation' => 'password-staff-2',
                ],
            ],
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('owner', 2);
        $this->assertDatabaseCount('pegawai', 2);
        $this->assertDatabaseHas('pegawai', [
            'id_pegawai' => 'PGW0001',
            'nama_pegawai' => 'Pegawai Satu',
            'username' => 'pegawai-satu',
            'jabatan' => 'Pegawai',
        ]);
        $this->assertDatabaseHas('pegawai', [
            'id_pegawai' => 'PGW0002',
            'nama_pegawai' => 'Pegawai Dua',
            'username' => 'pegawai-dua',
            'jabatan' => 'Pegawai',
        ]);
        $this->assertTrue(Hash::check('password-owner-1', Owner::query()->where('username', 'owner-utama')->value('password')));
        $this->assertTrue(Hash::check('password-staff-1', Pegawai::query()->where('username', 'pegawai-satu')->value('password')));

        $this->get(route('setup'))->assertRedirect(route('home'));
    }

    public function test_initial_setup_rejects_duplicate_usernames_and_requires_both_roles(): void
    {
        $this->from(route('setup'))
            ->post(route('setup.store'), [
                'owners' => [
                    [
                        'name' => 'Owner Satu',
                        'username' => 'owner-sama',
                        'password' => 'password-owner-1',
                        'password_confirmation' => 'password-owner-1',
                    ],
                    [
                        'name' => 'Owner Dua',
                        'username' => 'owner-sama',
                        'password' => 'password-owner-2',
                        'password_confirmation' => 'password-owner-2',
                    ],
                ],
                'employees' => [
                    [
                        'name' => 'Pegawai Satu',
                        'username' => 'pegawai-satu',
                        'password' => 'password-staff-1',
                        'password_confirmation' => 'password-staff-1',
                    ],
                ],
            ])
            ->assertRedirect(route('setup'))
            ->assertSessionHasErrors(['owners.1.username']);

        $this->assertDatabaseCount('owner', 0);
        $this->assertDatabaseCount('pegawai', 0);

        $this->from(route('setup'))
            ->post(route('setup.store'), [
                'owners' => [],
                'employees' => [
                    [
                        'name' => 'Pegawai Satu',
                        'username' => 'pegawai-satu',
                        'password' => 'password-staff-1',
                        'password_confirmation' => 'password-staff-1',
                    ],
                ],
            ])
            ->assertRedirect(route('setup'))
            ->assertSessionHasErrors(['owners']);

        $this->assertDatabaseCount('owner', 0);
        $this->assertDatabaseCount('pegawai', 0);
    }
}
