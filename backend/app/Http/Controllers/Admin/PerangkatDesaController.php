<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\PerangkatDesa;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Kelola daftar perangkat desa (termasuk Kepala Desa) untuk halaman profil.
 *
 * Foto disimpan pada disk "public" (berbeda dari dokumen pengajuan warga)
 * karena foto pejabat memang untuk ditampilkan di halaman Profil publik.
 */
class PerangkatDesaController extends Controller
{
    /** Folder penyimpanan foto perangkat pada disk public. */
    protected const FOLDER_FOTO = 'perangkat';

    public function index(): View
    {
        abort_unless(request()->user()->role->bolehKelolaKonten(), 403);

        return view('admin.perangkat.index', [
            'activeAdmin' => 'admin.perangkat.index',
            'perangkats' => PerangkatDesa::query()->terurut()->get(),
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()->role->bolehKelolaKonten(), 403);

        return view('admin.perangkat.form', [
            'activeAdmin' => 'admin.perangkat.index',
            'perangkat' => new PerangkatDesa(['is_aktif' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaKonten(), 403);

        $data = $this->validasi($request);

        $perangkat = PerangkatDesa::create([
            'nama' => $data['nama'],
            'jabatan' => $data['jabatan'],
            'nip' => $data['nip'] ?? null,
            'foto_path' => $this->simpanFoto($request),
            'urutan' => $data['urutan'] ?? 0,
            'is_aktif' => $request->boolean('is_aktif'),
        ]);

        LogAktivitas::catat(
            tindakan: 'Menambahkan perangkat desa',
            subjek: $perangkat->nama,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.perangkat.index')
            ->with('status', 'Perangkat desa berhasil ditambahkan.');
    }

    public function edit(PerangkatDesa $perangkat): View
    {
        abort_unless(request()->user()->role->bolehKelolaKonten(), 403);

        return view('admin.perangkat.form', [
            'activeAdmin' => 'admin.perangkat.index',
            'perangkat' => $perangkat,
        ]);
    }

    public function update(Request $request, PerangkatDesa $perangkat): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaKonten(), 403);

        $data = $this->validasi($request);

        $lama = $perangkat->foto_path;
        // Urutan penting: unggahan baru menang atas centang "hapus foto".
        $baru = $this->simpanFoto($request) ?? ($request->boolean('hapus_foto') ? null : $lama);

        $perangkat->update([
            'nama' => $data['nama'],
            'jabatan' => $data['jabatan'],
            'nip' => $data['nip'] ?? null,
            'foto_path' => $baru,
            'urutan' => $data['urutan'] ?? 0,
            'is_aktif' => $request->boolean('is_aktif'),
        ]);

        // Foto lama dihapus hanya setelah baris DB tersimpan, agar tidak
        // meninggalkan data yatim bila penyimpanan gagal.
        if ($baru !== $lama && filled($lama)) {
            Storage::disk('public')->delete($lama);
        }

        LogAktivitas::catat(
            tindakan: 'Memperbarui perangkat desa',
            subjek: $perangkat->nama,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.perangkat.index')
            ->with('status', 'Perangkat desa berhasil diperbarui.');
    }

    public function destroy(Request $request, PerangkatDesa $perangkat): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaKonten(), 403);

        $nama = $perangkat->nama;
        $foto = $perangkat->foto_path;
        $perangkat->delete();

        if (filled($foto)) {
            Storage::disk('public')->delete($foto);
        }

        LogAktivitas::catat(
            tindakan: 'Menghapus perangkat desa',
            subjek: $nama,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.perangkat.index')
            ->with('status', 'Perangkat desa berhasil dihapus.');
    }

    /**
     * Simpan foto perangkat bila ada. Mengembalikan path relatif atau null.
     *
     * Nama berkas dibuat sendiri (UUID) dan ekstensinya diambil dari TIPE ISI
     * berkas, bukan dari nama kiriman pengguna. `getClientOriginalExtension()`
     * bisa berisi apa saja — berkas PNG sah yang dinamai "foto.html" akan
     * tersimpan sebagai "uuid.html" di disk PUBLIC, dan karena disk itu
     * dilayani langsung oleh web server, berkas tersebut menjadi halaman HTML
     * di domain situs desa (celah stored XSS: penyerang dapat menjalankan
     * JavaScript di origin yang sama dengan sesi admin).
     */
    protected function simpanFoto(Request $request): ?string
    {
        $berkas = $request->file('foto');

        if (! $berkas instanceof \Illuminate\Http\UploadedFile || ! $berkas->isValid()) {
            return null;
        }

        $nama = Str::uuid()->toString().'.'.$this->ekstensiAman($berkas);
        $path = $berkas->storeAs(self::FOLDER_FOTO, $nama, 'public');

        return $path === false ? null : $path;
    }

    /**
     * Ekstensi berdasarkan isi berkas, bukan nama kiriman.
     *
     * Daftar putih sengaja sempit: hanya format gambar yang memang dipakai
     * halaman publik. Format lain jatuh ke "jpg" — aman, karena berkasnya
     * sudah lolos aturan validasi "image".
     */
    protected function ekstensiAman(\Illuminate\Http\UploadedFile $berkas): string
    {
        $mime = strtolower((string) $berkas->getMimeType());

        return match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function validasi(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'jabatan' => ['required', 'string', 'max:120'],
            'nip' => ['nullable', 'string', 'max:40'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_aktif' => ['nullable', 'boolean'],
        ], [
            'nama.required' => 'Nama perangkat wajib diisi.',
            'jabatan.required' => 'Jabatan wajib diisi.',
            'urutan.integer' => 'Urutan harus berupa angka.',
            'foto.image' => 'Foto harus berupa gambar.',
            'foto.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
        ]);
    }
}
