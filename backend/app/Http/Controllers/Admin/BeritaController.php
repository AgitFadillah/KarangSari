<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use App\Models\LogAktivitas;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Kelola berita, pengumuman, dan agenda yang tampil di halaman publik.
 *
 * Isi halaman informasi dan blok "Informasi Desa" di beranda berasal dari
 * tabel ini, sehingga perangkat desa tidak perlu menyentuh kode.
 */
class BeritaController extends Controller
{
    public function index(): View
    {
        abort_unless(request()->user()->role->bolehKelolaKonten(), 403);

        // Filter kategori (?kategori=berita|pengumuman|agenda). Kategori asing
        // diabaikan agar URL sembarang tidak menampilkan daftar kosong.
        $kategori = request('kategori');
        $kategoriAktif = in_array($kategori, Berita::KATEGORI, true) ? $kategori : null;

        $beritas = Berita::query()
            ->when($kategoriAktif, fn ($q) => $q->where('kategori', $kategoriAktif))
            ->orderByDesc('terbit_at')
            ->orderByDesc('id')
            ->get();

        // Jumlah per kategori untuk kartu ringkasan di atas tabel.
        $jumlah = ['semua' => Berita::query()->count()];
        foreach (Berita::KATEGORI as $kat) {
            $jumlah[$kat] = Berita::query()->where('kategori', $kat)->count();
        }

        return view('admin.berita.index', [
            'activeAdmin' => 'admin.berita.index',
            'beritas' => $beritas,
            'kategoriAktif' => $kategoriAktif,
            'jumlah' => $jumlah,
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()->role->bolehKelolaKonten(), 403);

        return view('admin.berita.form', [
            'activeAdmin' => 'admin.berita.index',
            'berita' => new Berita(['kategori' => 'berita']),
            'kategori' => Berita::KATEGORI,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaKonten(), 403);

        $data = $this->validasi($request);

        $berita = Berita::create([
            'judul' => $data['judul'],
            'slug' => Berita::slugUnik($data['judul']),
            'kategori' => $data['kategori'],
            'is_highlight' => (bool) ($data['is_highlight'] ?? false),
            'ringkasan' => $data['ringkasan'] ?? null,
            'isi' => $data['isi'] ?? null,
            'penulis' => $data['penulis'] ?? null,
            'terbit_at' => $data['terbit_at'] ?? null,
            'dibuat_oleh' => $request->user()->id,
        ]);

        $this->jagaSatuSorotan($berita);

        LogAktivitas::catat(
            tindakan: 'Menambahkan berita',
            subjek: $berita->judul,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.berita.index')
            ->with('status', 'Berita berhasil ditambahkan.');
    }

    public function edit(Berita $berita): View
    {
        abort_unless(request()->user()->role->bolehKelolaKonten(), 403);

        return view('admin.berita.form', [
            'activeAdmin' => 'admin.berita.index',
            'berita' => $berita,
            'kategori' => Berita::KATEGORI,
        ]);
    }

    public function update(Request $request, Berita $berita): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaKonten(), 403);

        $data = $this->validasi($request);

        $berita->update([
            'judul' => $data['judul'],
            'slug' => $data['judul'] === $berita->judul
                ? $berita->slug
                : Berita::slugUnik($data['judul'], $berita->id),
            'kategori' => $data['kategori'],
            'is_highlight' => (bool) ($data['is_highlight'] ?? false),
            'ringkasan' => $data['ringkasan'] ?? null,
            'isi' => $data['isi'] ?? null,
            'penulis' => $data['penulis'] ?? null,
            'terbit_at' => $data['terbit_at'] ?? null,
        ]);

        $this->jagaSatuSorotan($berita);

        LogAktivitas::catat(
            tindakan: 'Memperbarui berita',
            subjek: $berita->judul,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.berita.index')
            ->with('status', 'Berita berhasil diperbarui.');
    }

    public function destroy(Request $request, Berita $berita): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaKonten(), 403);

        $judul = $berita->judul;
        $berita->delete();

        LogAktivitas::catat(
            tindakan: 'Menghapus berita',
            subjek: $judul,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.berita.index')
            ->with('status', 'Berita berhasil dihapus.');
    }

    /**
     * Sorotan bersifat tunggal: menandai satu berita otomatis melepas
     * sorotan berita lain agar banner Informasi tidak berganti-ganti.
     */
    protected function jagaSatuSorotan(Berita $berita): void
    {
        if (! $berita->is_highlight) {
            return;
        }

        Berita::query()
            ->whereKeyNot($berita->getKey())
            ->where('is_highlight', true)
            ->update(['is_highlight' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validasi(Request $request): array
    {
        return $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'kategori' => ['required', Rule::in(Berita::KATEGORI)],
            'is_highlight' => ['nullable', 'boolean'],
            'ringkasan' => ['nullable', 'string', 'max:1000'],
            'isi' => ['nullable', 'string', 'max:65000'],
            'penulis' => ['nullable', 'string', 'max:120'],
            'terbit_at' => ['nullable', 'date'],
        ], [
            'judul.required' => 'Judul berita wajib diisi.',
            'kategori.required' => 'Kategori wajib dipilih.',
            'kategori.in' => 'Kategori tidak dikenal.',
            'terbit_at.date' => 'Tanggal terbit tidak valid.',
        ]);
    }
}
