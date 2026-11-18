<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\PotensiDesa;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Kelola potensi desa (UMKM, wisata, pertanian, kerajinan) yang tampil di
 * beranda dan halaman potensi.
 */
class PotensiDesaController extends Controller
{
    public function index(): View
    {
        abort_unless(request()->user()->role->bolehKelolaKonten(), 403);

        return view('admin.potensi.index', [
            'activeAdmin' => 'admin.potensi.index',
            'potensis' => PotensiDesa::query()->terurut()->get(),
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()->role->bolehKelolaKonten(), 403);

        return view('admin.potensi.form', [
            'activeAdmin' => 'admin.potensi.index',
            'potensi' => new PotensiDesa(['is_aktif' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaKonten(), 403);

        $data = $this->validasi($request);

        $potensi = PotensiDesa::create([
            'nama' => $data['nama'],
            'kategori' => $data['kategori'] ?? null,
            'deskripsi' => $data['deskripsi'] ?? null,
            'kontak' => $data['kontak'] ?? null,
            'urutan' => $data['urutan'] ?? 0,
            'is_aktif' => $request->boolean('is_aktif'),
        ]);

        LogAktivitas::catat(
            tindakan: 'Menambahkan potensi desa',
            subjek: $potensi->nama,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.potensi.index')
            ->with('status', 'Potensi desa berhasil ditambahkan.');
    }

    public function edit(PotensiDesa $potensi): View
    {
        abort_unless(request()->user()->role->bolehKelolaKonten(), 403);

        return view('admin.potensi.form', [
            'activeAdmin' => 'admin.potensi.index',
            'potensi' => $potensi,
        ]);
    }

    public function update(Request $request, PotensiDesa $potensi): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaKonten(), 403);

        $data = $this->validasi($request);

        $potensi->update([
            'nama' => $data['nama'],
            'kategori' => $data['kategori'] ?? null,
            'deskripsi' => $data['deskripsi'] ?? null,
            'kontak' => $data['kontak'] ?? null,
            'urutan' => $data['urutan'] ?? 0,
            'is_aktif' => $request->boolean('is_aktif'),
        ]);

        LogAktivitas::catat(
            tindakan: 'Memperbarui potensi desa',
            subjek: $potensi->nama,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.potensi.index')
            ->with('status', 'Potensi desa berhasil diperbarui.');
    }

    public function destroy(Request $request, PotensiDesa $potensi): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaKonten(), 403);

        $nama = $potensi->nama;
        $potensi->delete();

        LogAktivitas::catat(
            tindakan: 'Menghapus potensi desa',
            subjek: $nama,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.potensi.index')
            ->with('status', 'Potensi desa berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validasi(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:60'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'kontak' => ['nullable', 'string', 'max:120'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_aktif' => ['nullable', 'boolean'],
        ], [
            'nama.required' => 'Nama potensi wajib diisi.',
            'urutan.integer' => 'Urutan harus berupa angka.',
        ]);
    }
}
