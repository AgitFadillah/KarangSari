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
use Illuminate\Validation\ValidationException;

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
            // Riwayat kepala desa disunting dengan editor baris tersendiri
            // (tambah/hapus), bukan sebagai tabel angka seperti kelompok lain.
            'riwayatKades' => DataDesa::query()
                ->kelompok(DataDesa::RIWAYAT_KADES)
                ->terurut()
                ->get(),
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
            // Koordinat peta: angka desimal, titik atau koma sebagai pemisah.
            // Batasnya mengikuti jangkauan lintang (-90..90) dan bujur (-180..180)
            // supaya salah ketik tidak menghasilkan pin di tempat yang mustahil.
            'peta_lat' => ['nullable', 'string', 'max:30', $this->aturanKoordinat(90)],
            'peta_lng' => ['nullable', 'string', 'max:30', $this->aturanKoordinat(180)],
            'sejarah' => ['nullable', 'string', 'max:5000'],
            'visi' => ['nullable', 'string', 'max:2000'],
            'misi' => ['nullable', 'string', 'max:5000'],

            // Batas wilayah & penggunaan lahan dikirim sebagai baris
            // terpisah (tambah/hapus baris di dashboard). Format teks lama
            // tetap diterima agar tautan/bookmark formulir lama tidak rusak.
            'batas' => ['nullable', 'array'],
            'batas.*.arah' => ['nullable', 'string', 'max:60'],
            'batas.*.tetangga' => ['nullable', 'string', 'max:160'],
            'lahan' => ['nullable', 'array'],
            'lahan.*.label' => ['nullable', 'string', 'max:60'],
            'lahan.*.persen' => ['nullable', 'string', 'max:10'],
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

            // Riwayat kepala desa dikirim sebagai baris tersendiri: kunci
            // angka = baris lama yang diedit, kunci "baru_*" = baris baru.
            // Baris yang dihapus dikirim terpisah lewat riwayat_hapus[].
            'riwayat' => ['nullable', 'array'],
            'riwayat.*.label' => ['nullable', 'string', 'max:120'],
            'riwayat.*.nilai' => ['nullable', 'string', 'max:40'],
            'riwayat.*.keterangan' => ['nullable', 'string', 'max:120'],
            'riwayat_hapus' => ['nullable', 'array'],
            'riwayat_hapus.*' => ['integer'],
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

        // Riwayat kepala desa: nama, masa jabatan, dan keterangan. Baris
        // baru boleh dikirim tanpa id; baris lama memakai id sebagai kunci.
        $riwayat = $data['riwayat'] ?? null;
        $riwayatHapus = [];

        if (isset($data['riwayat_hapus']) && is_array($data['riwayat_hapus'])) {
            foreach ($data['riwayat_hapus'] as $id) {
                $id = filter_var($id, FILTER_VALIDATE_INT);

                if ($id !== false && $id !== null && $id > 0) {
                    $riwayatHapus[] = $id;
                }
            }
        }

        // Baris baru yang sudah diisi masa jabatan/keterangan tetapi belum
        // diberi nama akan tampak "hilang" setelah disimpan. Tolak dengan
        // pesan yang jelas alih-alih membuang isian admin diam-diam.
        if (is_array($riwayat)) {
            foreach ($riwayat as $baris) {
                if (! is_array($baris)) {
                    continue;
                }

                $label = trim((string) ($baris['label'] ?? ''));
                $nilai = trim((string) ($baris['nilai'] ?? ''));
                $keterangan = trim((string) ($baris['keterangan'] ?? ''));

                if ($label === '' && ($nilai !== '' || $keterangan !== '')) {
                    throw ValidationException::withMessages([
                        'riwayat' => 'Nama kepala desa wajib diisi pada setiap baris riwayat.',
                    ]);
                }
            }
        }

        unset($data['riwayat'], $data['riwayat_hapus']);

        // Baris "batas wilayah" dan "penggunaan lahan" dirangkai kembali
        // menjadi teks "Arah: nama" / "Label: persen" per baris — bentuk
        // yang sudah dibaca oleh model dan halaman publik. Baris yang
        // dikosongkan admin otomatis hilang dari hasil.
        //
        // Penting: hanya disentuh bila formulir benar-benar mengirim baris
        // (atau teks lamanya). Kalau tidak, kolom dibiarkan apa adanya —
        // permintaan lain yang tidak memuat field ini tidak boleh menghapus
        // data wilayah yang sudah tersimpan.
        if (array_key_exists('batas', $data) || array_key_exists('batas_wilayah', $data)) {
            $data['batas_wilayah'] = $this->rangkaiBaris(
                $data['batas'] ?? null,
                'arah',
                'tetangga',
                $data['batas_wilayah'] ?? null,
            );
        }

        if (array_key_exists('lahan', $data) || array_key_exists('penggunaan_lahan', $data)) {
            $data['penggunaan_lahan'] = $this->rangkaiBaris(
                $data['lahan'] ?? null,
                'label',
                'persen',
                $data['penggunaan_lahan'] ?? null,
            );
        }

        unset($data['batas'], $data['lahan']);

        // Koordinat disimpan dalam bentuk baku ("-5.358421") walau admin
        // mengetik dengan koma desimal, supaya sematan Google Maps valid.
        foreach (['peta_lat', 'peta_lng'] as $kolom) {
            if (! array_key_exists($kolom, $data)) {
                continue;
            }

            $nilai = trim(str_replace(',', '.', (string) $data[$kolom]));
            $data[$kolom] = $nilai === '' ? null : $nilai;
        }

        unset(
            $data['logo'], $data['foto_beranda'],
            $data['logo_hapus'], $data['foto_beranda_hapus'],
            $data['data'],
        );

        try {
            DB::transaction(function () use ($profil, $data, $barisData, $riwayat, $riwayatHapus) {
                $profil->fill($data)->save();

                // Hapus lebih dulu, lalu simpan baris yang masih ada. Dengan
                // begitu baris yang tombol hapusnya ditekan tidak ikut
                // diperbarui bila id-nya kebetulan masih terkirim.
                if ($riwayatHapus !== []) {
                    DataDesa::query()
                        ->kelompok(DataDesa::RIWAYAT_KADES)
                        ->whereKey($riwayatHapus)
                        ->delete();
                }

                if (is_array($riwayat)) {
                    $urutan = (int) DataDesa::query()
                        ->kelompok(DataDesa::RIWAYAT_KADES)
                        ->max('urutan');

                    foreach ($riwayat as $kunci => $barisRiwayat) {
                        if (! is_array($barisRiwayat)) {
                            continue;
                        }

                        $label = trim((string) ($barisRiwayat['label'] ?? ''));
                        $masaJabatan = trim((string) ($barisRiwayat['nilai'] ?? ''));
                        $keterangan = trim((string) ($barisRiwayat['keterangan'] ?? ''));

                        // Baris kosong (mis. baris baru yang tidak jadi
                        // diisi) diabaikan, bukan disimpan sebagai data
                        // tanpa nama.
                        if ($label === '') {
                            continue;
                        }

                        $id = filter_var($kunci, FILTER_VALIDATE_INT);

                        if ($id !== false && $id !== null) {
                            $baris = DataDesa::query()
                                ->kelompok(DataDesa::RIWAYAT_KADES)
                                ->find($id);

                            if ($baris) {
                                $baris->update([
                                    'label' => $label,
                                    'nilai' => $masaJabatan !== '' ? $masaJabatan : null,
                                    'keterangan' => $keterangan !== '' ? $keterangan : null,
                                ]);
                            }

                            continue;
                        }

                        DataDesa::query()->create([
                            'kelompok' => DataDesa::RIWAYAT_KADES,
                            'label' => $label,
                            'nilai' => $masaJabatan !== '' ? $masaJabatan : null,
                            'keterangan' => $keterangan !== '' ? $keterangan : null,
                            'urutan' => ++$urutan,
                        ]);
                    }
                }

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

    /**
     * Aturan validasi untuk satu nilai koordinat peta.
     *
     * Menerima titik maupun koma sebagai pemisah desimal (kebiasaan mengetik
     * di Indonesia) dan menolak nilai di luar jangkauan bumi.
     */
    protected function aturanKoordinat(float $batas): \Closure
    {
        return function (string $atribut, mixed $nilai, \Closure $gagal) use ($batas) {
            $teks = trim(str_replace(',', '.', (string) $nilai));

            if ($teks === '') {
                return;
            }

            if (! is_numeric($teks) || abs((float) $teks) > $batas) {
                $gagal("Koordinat peta tidak valid. Isi angka antara -{$batas} dan {$batas}.");
            }
        };
    }

    /**
     * Rangkai baris isian menjadi teks "kunci: nilai" per baris.
     *
     * Bila formulir tidak mengirim baris sama sekali (mis. permintaan lama
     * atau tes yang masih memakai field teks), nilai teks aslinya dipakai
     * apa adanya supaya data tidak terhapus tanpa sengaja.
     *
     * @param  mixed  $baris  Array baris dari formulir.
     */
    protected function rangkaiBaris(mixed $baris, string $kunciField, string $nilaiField, ?string $teksLama): ?string
    {
        if (! is_array($baris)) {
            return $teksLama;
        }

        $hasil = [];

        foreach ($baris as $satu) {
            if (! is_array($satu)) {
                continue;
            }

            $kunci = trim((string) ($satu[$kunciField] ?? ''));
            $nilai = trim((string) ($satu[$nilaiField] ?? ''));

            // Baris yang salah satu kolomnya kosong diabaikan: itulah cara
            // admin "menghapus" baris tanpa menghapus field-nya.
            if ($kunci === '' || $nilai === '') {
                continue;
            }

            // Titik dua pada kunci akan memecah pembacaan "kunci: nilai",
            // jadi diganti tanda hubung.
            $kunci = str_replace(':', ' -', $kunci);

            $hasil[] = $kunci.': '.$nilai;
        }

        return $hasil === [] ? null : implode("\n", $hasil);
    }
}
