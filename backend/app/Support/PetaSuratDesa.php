<?php

namespace App\Support;

use App\Models\Pengajuan;
use Illuminate\Support\Carbon;

/**
 * Peta "titik-titik dokumen desa" untuk SETIAP kategori surat.
 *
 * Dokumen Word asli dari perangkat desa dipakai sebagai
 * kerangka. Semua format — kop, Bookman Old Style, ukuran huruf, tab, tabel
 * tanda tangan — dibiarkan apa adanya. Yang diubah hanya titik-titik pada
 * paragraf yang memang disediakan dokumen.
 *
 * Indeks paragraf mengacu pada urutan <w:p> langsung di dalam <w:body>
 * (paragraf di dalam tabel tidak dihitung). Setiap template punya susunan
 * sendiri:
 *
 *   domisili / belum-menikah / tidak-mampu / pekerjaan / meninggal
 *     identitas 13–19, alamat desa 20, kabupaten 21
 *   usaha
 *     identitas 13–18, alamat desa 19, kabupaten 20, usaha 22–23
 *   orang-tua-asuh
 *     identitas 12–17, alamat desa 18, kabupaten 19,
 *     identitas anak 21–26, alamat desa 27, kabupaten 28, sejak 29
 */
class PetaSuratDesa
{
    /**
     * Nama kepala desa pada dokumen resmi desa.
     *
     * Nama ini tercetak hitam pada semua dokumen Word (bukan merah seperti
     * titik-titik isian), jadi ia bagian tetap template — bukan data yang
     * boleh diganti nama pejabat lain.
     */
    public const NAMA_KEPALA_DESA = 'R O M S I, SH';

    /**
     * Lima kategori dengan blok identitas yang sama (13–21).
     *
     * @var array<int, array{paragraf: int, kunci: string}>
     */
    private const IDENTITAS_BAKU = [
        ['paragraf' => 13, 'kunci' => 'nama'],
        ['paragraf' => 14, 'kunci' => 'nik'],
        ['paragraf' => 15, 'kunci' => 'tempat_tanggal_lahir'],
        ['paragraf' => 16, 'kunci' => 'agama'],
        ['paragraf' => 17, 'kunci' => 'status_perkawinan'],
        ['paragraf' => 18, 'kunci' => 'pekerjaan'],
        ['paragraf' => 19, 'kunci' => 'dusun'],
    ];

    /**
     * Peta paragraf untuk satu kategori.
     *
     * Kunci yang dikembalikan:
     *  - identitas          : baris berlabel pada blok identitas pemohon
     *  - alamat_desa        : paragraf ": Desa ... Kecamatan ..."
     *  - alamat_kabupaten   : paragraf ": Kabupaten ..."
     *  - identitas_kedua    : blok identitas kedua (orang tua asuh)
     *  - alamat_desa_kedua  : ": Desa ..." blok kedua
     *  - alamat_kabupaten_kedua : ": Kabupaten ..." blok kedua
     *  - titik              : titik-titik di baris tambahan
     *  - frasa              : kata tetap yang diganti nilai (pekerjaan)
     *
     * @return array<string, mixed>
     */
    public static function peta(string $slug): array
    {
        return match ($slug) {
            'usaha' => [
                'identitas' => [
                    ['paragraf' => 13, 'kunci' => 'nama'],
                    ['paragraf' => 14, 'kunci' => 'nik'],
                    ['paragraf' => 15, 'kunci' => 'tempat_tanggal_lahir'],
                    ['paragraf' => 16, 'kunci' => 'agama'],
                    ['paragraf' => 17, 'kunci' => 'pekerjaan'],
                    ['paragraf' => 18, 'kunci' => 'dusun'],
                ],
                'alamat_desa' => [19],
                'alamat_kabupaten' => [20],
                'titik' => [
                    ['paragraf' => 22, 'kunci' => 'nama_usaha'],
                    ['paragraf' => 23, 'kunci' => 'alamat_usaha'],
                ],
            ],
            'orang-tua-asuh' => [
                'identitas' => [
                    ['paragraf' => 12, 'kunci' => 'nama'],
                    ['paragraf' => 13, 'kunci' => 'nik'],
                    ['paragraf' => 14, 'kunci' => 'tempat_tanggal_lahir'],
                    ['paragraf' => 15, 'kunci' => 'agama'],
                    ['paragraf' => 16, 'kunci' => 'pekerjaan'],
                    ['paragraf' => 17, 'kunci' => 'dusun'],
                ],
                'alamat_desa' => [18],
                'alamat_kabupaten' => [19],
                'identitas_kedua' => [
                    ['paragraf' => 21, 'kunci' => 'nama_anak'],
                    ['paragraf' => 22, 'kunci' => 'nik_anak'],
                    ['paragraf' => 23, 'kunci' => 'tempat_tanggal_lahir_anak'],
                    ['paragraf' => 24, 'kunci' => 'agama_anak'],
                    ['paragraf' => 25, 'kunci' => 'pekerjaan_anak'],
                    ['paragraf' => 26, 'kunci' => 'alamat_anak'],
                ],
                'alamat_desa_kedua' => [27],
                'alamat_kabupaten_kedua' => [28],
                'titik' => [
                    ['paragraf' => 29, 'kunci' => 'sejak'],
                ],
            ],
            'kematian' => [
                'identitas' => self::IDENTITAS_BAKU,
                'alamat_desa' => [20],
                'alamat_kabupaten' => [21],
                'titik' => [
                    ['paragraf' => 23, 'kunci' => 'tanggal_meninggal'],
                    ['paragraf' => 24, 'kunci' => 'tempat_makam'],
                ],
            ],
            'pekerjaan' => [
                'identitas' => self::IDENTITAS_BAKU,
                'alamat_desa' => [20],
                'alamat_kabupaten' => [21],
                'frasa' => [
                    ['paragraf' => 23, 'cari' => 'wiraswasta', 'kunci' => 'pekerjaan'],
                    ['paragraf' => 23, 'cari' => 'Buruh Harian Lepas', 'kunci' => 'pekerjaan_sebenarnya'],
                ],
            ],
            'belum-menikah', 'tidak-mampu' => [
                'identitas' => self::IDENTITAS_BAKU,
                'alamat_desa' => [20],
                'alamat_kabupaten' => [21],
            ],
            default => [
                'identitas' => self::IDENTITAS_BAKU,
                'alamat_desa' => [20],
                'alamat_kabupaten' => [21],
                'titik' => [
                    ['paragraf' => 22, 'kunci' => 'dusun'],
                ],
            ],
        };
    }

    /**
     * Nilai tiap kunci isian untuk sebuah pengajuan.
     *
     * @param  array<string, string>  $isian
     * @return array<string, string>
     */
    public static function nilai(?Pengajuan $pengajuan, array $isian = []): array
    {
        $isian = array_change_key_case($isian, CASE_LOWER);
        $profil = SiteProfile::current();

        $desa = self::rapikanKata(self::tanpaAwalan((string) $profil->nama_desa, ['Desa', 'Kelurahan']));
        $kecamatan = self::rapikanKata(self::tanpaAwalan((string) $profil->kecamatan, ['Kecamatan', 'Kec.']));
        $kabupaten = self::rapikanKata(self::tanpaAwalan((string) $profil->kabupaten, ['Kabupaten', 'Kab.']));

        $tempatLahir = trim((string) ($isian['tempat_lahir'] ?? $pengajuan?->tempat_lahir ?? ''));
        $tanggalLahir = trim((string) ($isian['tanggal_lahir'] ?? ''));

        if ($tanggalLahir === '' && $pengajuan?->tanggal_lahir !== null) {
            $tanggalLahir = $pengajuan->tanggal_lahir->translatedFormat('j F Y');
        } elseif ($tanggalLahir !== '') {
            $tanggalLahir = self::tanggalIndonesia($tanggalLahir);
        }

        // Alamat pada dokumen selalu dicetak setelah kata "Dusun". Bila warga
        // menuliskan "Dusun Sukamaju", awalan itu dibuang agar tidak dobel.
        $alamat = trim((string) ($isian['alamat'] ?? $pengajuan?->alamat ?? ''));
        $dusun = self::tanpaDusun(trim((string) ($isian['dusun'] ?? $alamat)));

        $alamatUsaha = self::tanpaDusun(trim((string) ($isian['alamat_usaha'] ?? '')));
        $alamatAnak = self::tanpaDusun(trim((string) ($isian['alamat_anak'] ?? '')));

        return [
            'nama' => (string) ($isian['nama'] ?? $pengajuan?->nama ?? ''),
            'nik' => (string) ($isian['nik'] ?? $pengajuan?->nik ?? ''),
            'tempat_tanggal_lahir' => trim(implode(', ', array_filter([$tempatLahir, $tanggalLahir]))),
            'agama' => trim((string) ($isian['agama'] ?? '')),
            'status_perkawinan' => trim((string) ($isian['status_perkawinan'] ?? '')),
            'pekerjaan' => trim((string) ($isian['pekerjaan'] ?? '')),
            'dusun' => $dusun,
            'alamat_desa' => trim('Desa '.$desa.' Kecamatan '.$kecamatan),
            'alamat_kabupaten' => trim('Kabupaten '.$kabupaten),

            // Surat Keterangan Usaha.
            'nama_usaha' => trim((string) ($isian['nama_usaha'] ?? '')),
            'alamat_usaha' => $alamatUsaha,

            // Surat Keterangan Meninggal.
            'tanggal_meninggal' => self::tanggalIndonesia(trim((string) ($isian['tanggal_meninggal'] ?? ''))),
            'tempat_makam' => trim((string) ($isian['tempat_makam'] ?? '')),

            // Surat Keterangan Orang Tua Asuh/Wali.
            'nama_anak' => trim((string) ($isian['nama_anak'] ?? '')),
            'nik_anak' => trim((string) ($isian['nik_anak'] ?? '')),
            'tempat_tanggal_lahir_anak' => trim(implode(', ', array_filter([
                trim((string) ($isian['tempat_lahir_anak'] ?? '')),
                self::tanggalIndonesia(trim((string) ($isian['tanggal_lahir_anak'] ?? ''))),
            ]))),
            'agama_anak' => trim((string) ($isian['agama_anak'] ?? '')),
            'pekerjaan_anak' => trim((string) ($isian['pekerjaan_anak'] ?? '')),
            'alamat_anak' => $alamatAnak,
            'sejak' => trim((string) ($isian['sejak'] ?? '')),

            // Surat Keterangan Pekerjaan.
            'pekerjaan_sebenarnya' => trim((string) ($isian['pekerjaan_sebenarnya'] ?? '')),
        ];
    }

    /**
     * Ubah tanggal ISO (dari input type=date) menjadi "10 November 2026".
     * Nilai teks lama dibiarkan apa adanya.
     */
    public static function tanggalIndonesia(string $nilai): string
    {
        if ($nilai === '') {
            return '';
        }

        try {
            return Carbon::parse($nilai)->translatedFormat('j F Y');
        } catch (\Throwable) {
            return $nilai;
        }
    }

    /**
     * Rapikan penulisan nama wilayah.
     *
     * Data lama bisa menulis "KarangSari" (dua kata kapital menempel tanpa
     * spasi). Dirapikan menjadi "Karang Sari" supaya kalimat surat enak dibaca
     * dan sama dengan dokumen desa.
     */
    public static function rapikanKata(string $teks): string
    {
        return trim((string) preg_replace('/(?<=[a-z])(?=[A-Z])/u', ' ', $teks));
    }

    /** Buang awalan "Dusun " karena dokumen sudah mencetak kata itu. */
    public static function tanpaDusun(string $teks): string
    {
        return trim((string) preg_replace('/^dusun\s+/iu', '', trim($teks)));
    }

    /** Buang awalan "Desa"/"Kabupaten" agar tidak tercetak dua kali. */
    private static function tanpaAwalan(string $teks, array $awalan): string
    {
        $teks = trim($teks);

        foreach ($awalan as $a) {
            if (mb_strtolower(mb_substr($teks, 0, mb_strlen($a) + 1)) === mb_strtolower($a).' ') {
                return trim(mb_substr($teks, mb_strlen($a) + 1));
            }
        }

        return $teks;
    }
}
