<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Models\Pegawai;
use App\Support\DatabaseReadiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OwnerRegistrationController extends Controller
{
    public function create(): View|RedirectResponse
    {
        $this->ensureDatabaseReady();

        if ($redirect = $this->redirectIfAuthenticated()) {
            return $redirect;
        }

        if (! Owner::query()->exists() && ! Pegawai::query()->exists()) {
            return redirect()->route('setup');
        }

        if (Owner::query()->exists()) {
            return redirect()
                ->route('owner.login')
                ->with('status', 'Akun Owner sudah tersedia. Masuk sebagai Owner untuk menambahkan akun berikutnya.');
        }

        return view('auth.register-owner');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureDatabaseReady();

        if ($redirect = $this->redirectIfAuthenticated()) {
            return $redirect;
        }

        if (! Owner::query()->exists() && ! Pegawai::query()->exists()) {
            return redirect()->route('setup');
        }

        $data = $request->validate([
            'nama_owner' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', Rule::unique('owner', 'username')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $created = DB::transaction(function () use ($data): bool {
            DB::table('sys_sequences')->insertOrIgnore([
                'sequence_key' => 'owner_bootstrap',
                'current_value' => 0,
                'updated_at' => now(),
            ]);

            DB::table('sys_sequences')
                ->where('sequence_key', 'owner_bootstrap')
                ->lockForUpdate()
                ->first();

            if (Owner::query()->exists()) {
                return false;
            }

            Owner::query()->create($data);

            return true;
        });

        if (! $created) {
            return redirect()
                ->route('owner.login')
                ->with('status', 'Akun Owner sudah tersedia. Masuk sebagai Owner untuk menambahkan akun berikutnya.');
        }

        return redirect()
            ->route('owner.login')
            ->with('status', 'Akun Owner pertama berhasil dibuat. Silakan masuk.');
    }

    private function redirectIfAuthenticated(): ?RedirectResponse
    {
        if (Auth::guard('employee')->check()) {
            return redirect()->route('employee.dashboard');
        }

        if (Auth::guard('owner')->check()) {
            return redirect()->route('owner.dashboard');
        }

        return null;
    }

    private function ensureDatabaseReady(): void
    {
        $missingTables = DatabaseReadiness::missingTables();

        if ($missingTables !== []) {
            abort(503, 'Skema database belum siap. Tabel yang belum tersedia: '.implode(', ', $missingTables));
        }
    }
}
