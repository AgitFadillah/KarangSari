<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleAdmin;
use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Pengelolaan akun admin. Hanya role Administrator yang boleh mengelola.
 */
class AkunController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role->bolehKelolaAkun(), 403);

        return view('admin.akun.index', [
            'activeAdmin' => 'admin.akun.index',
            'akuns' => User::query()->orderBy('name')->get(),
            'roles' => RoleAdmin::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaAkun(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:120', 'unique:users,email'],
            'role' => ['required', Rule::enum(RoleAdmin::class)],
            'password' => ['required', 'confirmed', Password::min(12)],
        ], [
            'username.unique' => 'Nama pengguna sudah dipakai.',
            'password.confirmed' => 'Ulangi kata sandi tidak sama.',
        ]);

        $akun = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'role' => $data['role'],
            'password' => $data['password'],
            'is_active' => true,
        ]);

        LogAktivitas::catat(
            tindakan: 'Menambahkan akun admin',
            subjek: $akun->username,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.akun.index')
            ->with('status', 'Akun '.$akun->name.' berhasil dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaAkun(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::enum(RoleAdmin::class)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $aktif = $request->boolean('is_active');

        if (! $aktif && $user->id === $request->user()->id) {
            return back()->withErrors(['is_active' => 'Anda tidak dapat menonaktifkan akun yang sedang dipakai.']);
        }

        // Jangan sampai sistem kehilangan Administrator aktif terakhir.
        if ($user->role === RoleAdmin::Administrator) {
            $tetapAdministratorAktif = $aktif && $data['role'] === RoleAdmin::Administrator->value;

            if (! $tetapAdministratorAktif) {
                $adminAktifLain = User::query()
                    ->where('role', RoleAdmin::Administrator->value)
                    ->where('is_active', true)
                    ->whereKeyNot($user->id)
                    ->exists();

                if (! $adminAktifLain) {
                    return back()->withErrors([
                        'role' => 'Minimal harus ada satu Administrator aktif. Ubah peran akun lain lebih dulu.',
                    ]);
                }
            }
        }

        $user->update([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'role' => $data['role'],
            'is_active' => $aktif,
        ]);

        LogAktivitas::catat(
            tindakan: $aktif ? 'Memperbarui akun admin' : 'Menonaktifkan akun admin',
            subjek: $user->username,
            tingkat: $aktif ? LogAktivitas::SUKSES : LogAktivitas::PERINGATAN,
        );

        return redirect()
            ->route('admin.akun.index')
            ->with('status', 'Akun '.$user->name.' diperbarui.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaAkun(), 403);

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        $user->forceFill(['password' => Hash::make($data['password'])])->save();

        LogAktivitas::catat(
            tindakan: 'Mengatur ulang kata sandi akun',
            subjek: $user->username,
            tingkat: LogAktivitas::PERINGATAN,
        );

        return redirect()
            ->route('admin.akun.index')
            ->with('status', 'Kata sandi '.$user->name.' telah diatur ulang.');
    }
}
