<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataDesa;
use App\Models\LogAktivitas;
use App\Models\ProfilDesa;
use App\Support\SiteProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Profil & Kontak desa.
 *
 * Isian mengikuti dokumen "PROFIL desa.docx": identitas, kontak, kondisi umum,
 * sejarah (termasuk daftar dusun), demografi, dan keadaan sosial. Semua nilai
 * di sini langsung menjadi konfigurasi halaman publik (/profil, /kontak,
 * footer, dan kop surat).
 */
class ProfilController extends Controller
{
    public function edit(Request $request): View
    {
        abort_unless($request->user()->role->bolehKelolaKonten(), 403);

        return view('admin.profil.edit', [
            'activeAdmin' => 'admin.profil.edit',
            'profil' => SiteProfile::current(),
            'dataDesa' => DataDesa::query()->terurut()->get()->groupBy('kelompok'),
            'kelompokData' => DataDesa::KELOMPOK,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaKonten(), 403);

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
            'tahun_berdiri' => ['nullable', 'string', 'max:20'],
            'kueri_peta' => ['nullable', 'string', 'max:255'],
            'sejarah' => ['nullable', 'string', 'max:5000'],
            'visi' => ['nullable', 'string', 'max:2000'],
            'misi' => ['nullable', 'string', 'max:5000'],
            'batas_wilayah' => ['nullable', 'string', 'max:2000'],
            'penggunaan_lahan' => ['nullable', 'string', 'max:2000'],

            // Bagian "Kondisi Umum Desa" dan "Demografi" pada dokumen profil.
            'kondisi_umum' => ['nullable', 'string', 'max:5000'],
            'iklim' => ['nullable', 'string', 'max:2000'],
            'luas_wilayah' => ['nullable', 'string', 'max:30'],
            'daftar_dusun' => ['nullable', 'string', 'max:2000'],
            'jarak_provinsi' => ['nullable', 'string', 'max:20'],
            'jarak_kabupaten' => ['nullable', 'string', 'max:20'],
            'jarak_kecamatan' => ['nullable', 'string', 'max:20'],
            'penduduk_laki_laki' => ['nullable', 'string', 'max:20'],
            'penduduk_perempuan' => ['nullable', 'string', 'max:20'],
            'jumlah_kk' => ['nullable', 'string', 'max:20'],
            'keadaan_sosial' => ['nullable', 'string', 'max:5000'],

            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'foto_beranda' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'logo_hapus' => ['nullable', 'in:0,1'],
            'foto_beranda_hapus' => ['nullable', 'in:0,1'],

            // Baris tabel data desa (golongan umur, agama, pendidikan,
            // pencaharian, kesejahteraan, riwayat kepala desa).
            'data' => ['nullable', 'array'],
            'data.*.nilai' => ['nullable', 'string', 'max:40'],
            'data.*.nilai_l' => ['nullable', 'string', 'max:20'],
            'data.*.nilai_p' => ['nullable', 'string', 'max:20'],
            'data.*.keterangan' => ['nullable', 'string', 'max:120'],
        ], [
            'email.email' => 'Format email desa belum benar.',
            'logo.image' => 'Logo harus berupa gambar.',
            'foto_beranda.image' => 'Foto desa harus berupa gambar.',
        ]);

        // File publik desa boleh tampil di website -> disk public.
        //
        // Berkas LAMA tidak dihapus di sini. Penghapusan dilakukan setelah
        // transaksi database sukses (lihat di bawah): bila penyimpanan gagal
        // — koneksi putus, kolom terlalu panjang, dsb. — baris profil masih
        // menunjuk ke berkas lama, dan menghapusnya lebih dulu membuat logo
        // desa (atau foto beranda) hilang permanen dari situs padahal admin
        // hanya menerima pesan galat.
        $berkasLama = [];

        if ($request->hasFile('logo')) {
            if ($profil->logo_path) {
                $berkasLama[] = $profil->logo_path;
            }

            $data['logo_path'] = $request->file('logo')->store('profil', 'public');
        } elseif ($request->input('logo_hapus') === '1' && $profil->logo_path) {
            // Tombol "Hapus" pada kotak unggah ditekan tanpa memilih berkas baru.
            $berkasLama[] = $profil->logo_path;
            $data['logo_path'] = null;
        }

        if ($request->hasFile('foto_beranda')) {
            if ($profil->foto_beranda_path) {
                $berkasLama[] = $profil->foto_beranda_path;
            }

            $data['foto_beranda_path'] = $request->file('foto_beranda')->store('profil', 'public');
        } elseif ($request->input('foto_beranda_hapus') === '1' && $profil->foto_beranda_path) {
            $berkasLama[] = $profil->foto_beranda_path;
            $data['foto_beranda_path'] = null;
        }

        $barisData = $data['data'] ?? [];

        unset(
            $data['logo'], $data['foto_beranda'],
            $data['logo_hapus'], $data['foto_beranda_hapus'],
            $data['data'],
        );

        try {
            DB::transaction(function () use ($profil, $data, $barisData) {
                $profil->fill($data)->save();

                foreach ($barisData as $id => $nilai) {
                    // Nilai dari formulir bisa saja bukan array (permintaan
                    // yang dibuat manual). Lewati alih-alih melempar galat.
                    if (! is_array($nilai)) {
                        continue;
                    }

                    // Kunci array berasal dari query string, jadi bisa berisi
                    // apa saja ("abc", "1.5", string kosong). Di PostgreSQL
                    // `WHERE id = 'abc'` pada kolom bigint langsung melempar
                    // "invalid input syntax for type bigint" -> HTTP 500,
                    // sedangkan SQLite diam-diam mengembalikan null sehingga
                    // masalah ini hanya muncul setelah situs dipasang.
                    $id = filter_var($id, FILTER_VALIDATE_INT);

                    if ($id === false || $id === null) {
                        continue;
                    }

                    $baris = DataDesa::query()->find($id);

                    if (! $baris) {
                        continue;
                    }

                    // Baris bawaan dokumen tidak dihapus, hanya diisi/dikosongkan,
                    // supaya label tabel tetap sesuai dokumen resmi.
                    $baris->update([
                        'nilai' => $nilai['nilai'] ?? null,
                        'nilai_l' => $nilai['nilai_l'] ?? null,
                        'nilai_p' => $nilai['nilai_p'] ?? null,
                        'keterangan' => $nilai['keterangan'] ?? null,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            // Transaksi batal: berkas pengganti yang sudah terlanjur ditulis
            // dibuang agar tidak menumpuk sebagai sampah di disk.
            foreach ([$data['logo_path'] ?? null, $data['foto_beranda_path'] ?? null] as $baru) {
                if (filled($baru) && ! in_array($baru, $berkasLama, true)) {
                    Storage::disk('public')->delete($baru);
                }
            }

            throw $e;
        }

        // Baru sekarang berkas lama boleh dibuang: database sudah pasti
        // menunjuk ke berkas pengganti (atau ke null bila memang dihapus).
        foreach ($berkasLama as $lama) {
            if (filled($lama)) {
                Storage::disk('public')->delete($lama);
            }
        }

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
