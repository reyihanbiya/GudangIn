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
            'owners' => ['required', 'array', 'min:1'],
            'owners.*.name' => ['required', 'string', 'max:100'],
            'owners.*.username' => ['required', 'string', 'max:50', 'distinct:ignore_case', 'unique:owner,username'],
            'owners.*.password' => ['required', 'string', 'min:8', 'confirmed'],
            'employees' => ['required', 'array', 'min:1'],
            'employees.*.name' => ['required', 'string', 'max:100'],
            'employees.*.username' => ['required', 'string', 'max:50', 'distinct:ignore_case', 'unique:pegawai,username'],
            'employees.*.password' => ['required', 'string', 'min:8', 'confirmed'],
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

            foreach ($data['owners'] as $owner) {
                Owner::query()->create([
                    'nama_owner' => $owner['name'],
                    'username' => $owner['username'],
                    'password' => $owner['password'],
                ]);
            }

            foreach ($data['employees'] as $employee) {
                Pegawai::query()->create([
                    'id_pegawai' => CodeGeneratorService::generate('pegawai'),
                    'nama_pegawai' => $employee['name'],
                    'username' => $employee['username'],
                    'password' => $employee['password'],
                    'jabatan' => 'Pegawai',
                ]);
            }
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
