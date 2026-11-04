<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\ProfilDesa;
use App\Support\SiteProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfilController extends Controller
{
    public function edit(): View
    {
        return view('admin.profil.edit', [
            'activeAdmin' => 'admin.profil.edit',
            'profil' => SiteProfile::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $profil = ProfilDesa::query()->first() ?? new ProfilDesa;

        $data = $request->validate([
            'nama_desa' => ['required', 'string', 'max:120'],
            'kecamatan' => ['nullable', 'string', 'max:120'],
            'kabupaten' => ['nullable', 'string', 'max:120'],
            'provinsi' => ['nullable', 'string', 'max:120'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'alamat_kantor' => ['nullable', 'string', 'max:500'],
            'telepon' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'jam_layanan' => ['nullable', 'string', 'max:150'],
            'jam_kunjungan' => ['nullable', 'string', 'max:255'],
            'kueri_peta' => ['nullable', 'string', 'max:255'],
            'sejarah' => ['nullable', 'string', 'max:5000'],
            'visi' => ['nullable', 'string', 'max:2000'],
            'misi' => ['nullable', 'string', 'max:5000'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'foto_beranda' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        // File publik desa boleh tampil di website -> disk public.
        if ($request->hasFile('logo')) {
            if ($profil->logo_path) {
                Storage::disk('public')->delete($profil->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('profil', 'public');
        }

        if ($request->hasFile('foto_beranda')) {
            if ($profil->foto_beranda_path) {
                Storage::disk('public')->delete($profil->foto_beranda_path);
            }

            $data['foto_beranda_path'] = $request->file('foto_beranda')->store('profil', 'public');
        }

        unset($data['logo'], $data['foto_beranda']);

        $profil->fill($data)->save();

        SiteProfile::lupakan();

        LogAktivitas::catat(
            tindakan: 'Memperbarui profil dan kontak desa',
            subjek: 'profil-kontak',
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.profil.edit')
            ->with('status', 'Profil desa tersimpan.');
    }
}
