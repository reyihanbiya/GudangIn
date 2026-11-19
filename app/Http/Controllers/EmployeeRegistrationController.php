<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Models\Pegawai;
use App\Services\CodeGeneratorService;
use App\Support\DatabaseReadiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmployeeRegistrationController extends Controller
{
    public function chooseRole(): View|RedirectResponse
    {
        $this->ensureDatabaseReady();

        if ($redirect = $this->redirectIfAuthenticated()) {
            return $redirect;
        }

        if ($redirect = $this->redirectToSetupIfNeeded()) {
            return $redirect;
        }

        return view('auth.register-choice', [
            'ownerAccountExists' => Owner::query()->exists(),
        ]);
    }

    public function create(): View|RedirectResponse
    {
        $this->ensureDatabaseReady();

        if ($redirect = $this->redirectIfAuthenticated()) {
            return $redirect;
        }

        if ($redirect = $this->redirectToSetupIfNeeded()) {
            return $redirect;
        }

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureDatabaseReady();

        if ($redirect = $this->redirectIfAuthenticated()) {
            return $redirect;
        }

        if ($redirect = $this->redirectToSetupIfNeeded()) {
            return $redirect;
        }

        $data = $request->validate([
            'nama_pegawai' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:pegawai,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Pegawai::query()->create([
            'id_pegawai' => CodeGeneratorService::generate('pegawai'),
            'nama_pegawai' => $data['nama_pegawai'],
            'username' => $data['username'],
            'password' => $data['password'],
            'jabatan' => 'Pegawai',
        ]);

        return redirect()
            ->route('login')
            ->with('status', 'Akun Pegawai berhasil dibuat. Silakan masuk.');
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

    private function redirectToSetupIfNeeded(): ?RedirectResponse
    {
        if (! Owner::query()->exists() && ! Pegawai::query()->exists()) {
            return redirect()->route('setup');
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
