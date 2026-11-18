<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function employeeForm(): View|RedirectResponse
    {
        if (Auth::guard('employee')->check()) {
            return redirect()->route('employee.dashboard');
        }

        return view('auth.login', [
            'role' => 'Pegawai',
            'action' => route('login.store'),
            'portalRoute' => route('home'),
        ]);
    }

    public function ownerForm(): View|RedirectResponse
    {
        if (Auth::guard('owner')->check()) {
            return redirect()->route('owner.dashboard');
        }

        return view('auth.login', [
            'role' => 'Owner',
            'action' => route('owner.login.store'),
            'portalRoute' => route('home'),
        ]);
    }

    public function employeeLogin(Request $request): RedirectResponse
    {
        return $this->authenticate($request, 'employee', 'employee.dashboard');
    }

    public function ownerLogin(Request $request): RedirectResponse
    {
        return $this->authenticate($request, 'owner', 'owner.dashboard');
    }

    public function employeeLogout(Request $request): RedirectResponse
    {
        return $this->logout($request, 'employee');
    }

    public function ownerLogout(Request $request): RedirectResponse
    {
        return $this->logout($request, 'owner');
    }

    private function authenticate(Request $request, string $guard, string $destination): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard($guard)->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['username' => 'Username atau password tidak sesuai.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();

        return redirect()->intended(route($destination));
    }

    private function logout(Request $request, string $guard): RedirectResponse
    {
        Auth::guard($guard)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
