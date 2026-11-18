<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Models\Pegawai;
use App\Support\DatabaseReadiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View|RedirectResponse|Response
    {
        $missingTables = DatabaseReadiness::missingTables();

        if ($missingTables !== []) {
            return response()->view('setup.database', compact('missingTables'), 503);
        }

        if (Auth::guard('employee')->check()) {
            return redirect()->route('employee.dashboard');
        }

        if (Auth::guard('owner')->check()) {
            return redirect()->route('owner.dashboard');
        }

        return view('auth.portal', [
            'setupAvailable' => ! Owner::query()->exists() && ! Pegawai::query()->exists(),
        ]);
    }
}
