<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JenisSurat;
use App\Models\LogAktivitas;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JenisSuratController extends Controller
{
    public function index(): View
    {
        abort_unless(request()->user()->role->bolehKelolaJenisSurat(), 403);

        return view('admin.jenis-surat.index', [
            'activeAdmin' => 'admin.jenis-surat.index',
            'jenisSurats' => JenisSurat::query()->terurut()->withCount('persyaratan')->get(),
        ]);
    }

    public function edit(JenisSurat $jenisSurat): View
    {
        abort_unless(request()->user()->role->bolehKelolaJenisSurat(), 403);

        $jenisSurat->load('persyaratan');

        return view('admin.jenis-surat.edit', [
            'activeAdmin' => 'admin.jenis-surat.index',
            'jenisSurat' => $jenisSurat,
        ]);
    }

    public function update(Request $request, JenisSurat $jenisSurat): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaJenisSurat(), 403);

        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:jenis_surats,kode,'.$jenisSurat->id],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'estimasi_hari' => ['nullable', 'string', 'max:30'],
            'template_surat' => ['nullable', 'string', 'max:20000'],
            'persyaratan' => ['nullable', 'string', 'max:5000'],
            'is_aktif' => ['nullable', 'boolean'],
        ], [
            'kode.unique' => 'Kode jenis surat sudah dipakai jenis lain.',
        ]);

        $jenisSurat->update([
            'kode' => $data['kode'],
            'nama' => $data['nama'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'estimasi_hari' => $data['estimasi_hari'] ?? null,
            'template_surat' => $data['template_surat'] ?? null,
            'is_aktif' => $request->boolean('is_aktif'),
        ]);

        // Persyaratan disimpan satu per baris (format textarea admin).
        $baris = collect(preg_split('/\r\n|\r|\n/', (string) ($data['persyaratan'] ?? '')))
            ->map(fn (string $label) => trim($label))
            ->filter()
            ->values();

        $jenisSurat->persyaratan()->delete();

        foreach ($baris as $urutan => $label) {
            $jenisSurat->persyaratan()->create([
                'label' => $label,
                'urutan' => $urutan + 1,
            ]);
        }

        LogAktivitas::catat(
            tindakan: 'Memperbarui konfigurasi jenis surat',
            subjek: $jenisSurat->nama,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.jenis-surat.edit', $jenisSurat)
            ->with('status', 'Konfigurasi jenis surat tersimpan.');
    }
}
