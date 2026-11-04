<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $kredensial = $request->validated();

        $user = User::query()
            ->where('username', $kredensial['username'])
            ->first();

        // Pesan seragam agar tidak membocorkan username mana yang terdaftar.
        if ($user === null || ! Hash::check($kredensial['password'], $user->password)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Nama pengguna atau kata sandi tidak cocok.']);
        }

        if (! $user->is_active) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Akun Anda tidak aktif. Hubungi administrator desa.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        LogAktivitas::catat(
            tindakan: 'Login ke dashboard',
            subjek: 'dashboard admin',
            tingkat: LogAktivitas::INFO,
            user: $user,
            request: $request,
        );

        return redirect()->intended(route('admin.ringkasan'));
    }

    public function logout(Request $request): RedirectResponse
    {
        LogAktivitas::catat(
            tindakan: 'Keluar dari dashboard',
            subjek: 'dashboard admin',
            tingkat: LogAktivitas::INFO,
        );

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'Anda telah keluar.');
    }
}
