<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Models\Pegawai;
use App\Services\CodeGeneratorService;
use App\Support\DatabaseReadiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function create(): View|RedirectResponse
    {
        $this->ensureDatabaseReady();

        if (! $this->setupAvailable()) {
            return redirect()->route('home');
        }

        return view('auth.setup');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureDatabaseReady();

        $data = $request->validate([
            'owner_name' => ['required', 'string', 'max:100'],
            'owner_username' => ['required', 'string', 'max:50', 'unique:owner,username'],
            'owner_password' => ['required', 'string', 'min:8', 'confirmed'],
            'employee_name' => ['required', 'string', 'max:100'],
            'employee_username' => ['required', 'string', 'max:50', 'unique:pegawai,username'],
            'employee_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($data): void {
            DB::table('sys_sequences')->insertOrIgnore([
                'sequence_key' => 'initial_setup',
                'current_value' => 0,
                'updated_at' => now(),
            ]);

            DB::table('sys_sequences')
                ->where('sequence_key', 'initial_setup')
                ->lockForUpdate()
                ->first();

            if (! $this->setupAvailable()) {
                abort(409, 'Penyiapan awal sudah dilakukan.');
            }

            Owner::query()->create([
                'nama_owner' => $data['owner_name'],
                'username' => $data['owner_username'],
                'password' => $data['owner_password'],
            ]);

            Pegawai::query()->create([
                'id_pegawai' => CodeGeneratorService::generate('pegawai'),
                'nama_pegawai' => $data['employee_name'],
                'username' => $data['employee_username'],
                'password' => $data['employee_password'],
                'jabatan' => 'Pegawai',
            ]);
        });

        return redirect()
            ->route('login')
            ->with('status', 'Akun awal berhasil dibuat. Silakan masuk sebagai pegawai atau owner.');
    }

    private function ensureDatabaseReady(): void
    {
        $missingTables = DatabaseReadiness::missingTables();

        if ($missingTables !== []) {
            abort(503, 'Skema database belum siap. Tabel yang belum tersedia: '.implode(', ', $missingTables));
        }
    }

    private function setupAvailable(): bool
    {
        return ! Owner::query()->exists() && ! Pegawai::query()->exists();
    }
}
