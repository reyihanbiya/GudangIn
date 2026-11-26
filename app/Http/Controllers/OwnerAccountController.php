<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OwnerAccountController extends Controller
{
    public function index(): View
    {
        return view('owner.accounts', [
            'owners' => Owner::query()
                ->orderBy('nama_owner')
                ->get(['id_owner', 'nama_owner', 'username']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_owner' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', Rule::unique('owner', 'username')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Owner::query()->create($data);

        return redirect()
            ->route('owner.accounts.index')
            ->with('status', 'Akun Owner berhasil dibuat.');
    }
}
