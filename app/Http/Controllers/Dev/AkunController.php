<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Ganti akun tanpa kata sandi, khusus pengembangan lokal.
 * Route hanya terdaftar kalau config('titipanak.dev_switcher') aktif.
 */
class AkunController extends Controller
{
    public function index(): View
    {
        return view('dev.akun');
    }

    public function masuk(Request $request, User $user): RedirectResponse
    {
        abort_unless(config('titipanak.dev_switcher'), 404);
        abort_unless($user->aktif, 403, 'Akun nonaktif.');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function keluar(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('dev.akun');
    }
}
