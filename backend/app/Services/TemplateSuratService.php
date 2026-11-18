<?php

namespace App\Services;

use App\Models\JenisSurat;
use App\Models\Pengajuan;
use Illuminate\Support\Str;

/**
 * Membaca template surat desa dan mengubahnya menjadi kolom isian warga.
 *
 * Template ditulis admin memakai penanda {{nama_penanda}}. Setiap penanda yang
 * BUKAN data otomatis desa akan menjadi satu kolom isian pada formulir warga,
 * lalu nilainya disuntingkan kembali ke dalam surat saat pratinjau/ cetak.
 *
 * Contoh template:
 *
 *   Yang bertanda tangan di bawah ini menerangkan bahwa
 *   {{nama}} berdomisili di {{alamat}} dan memerlukan surat ini untuk
 *   {{keperluan}}. Penghasilan per bulan sekitar {{penghasilan}}.
 *
 * Penanda otomatis (nama, alamat, keperluan, nomor_referensi, tanggal, ...)
 * TIDAK ditanyakan lagi karena sudah ada di formulir utama.
 */
class TemplateSuratService
{
    /**
     * Penanda yang terisi otomatis dari data desa / pengajuan.
     * Kunci = penanda, nilai = label yang ditampilkan pada pratinjau.
     *
     * @var array<string, string>
     */
    public const PENANDA_OTOMATIS = [
        'nama' => 'Nama pemohon',
        'alamat' => 'Alamat pemohon',
        'dusun' => 'Dusun pemohon (tanpa kata Dusun)',
        'keperluan' => 'Keperluan',
        'whatsapp' => 'Nomor WhatsApp',
        'nik' => 'NIK',
        'tempat_lahir' => 'Tempat lahir',
        'tanggal_lahir' => 'Tanggal lahir',
        'jenis_kelamin' => 'Jenis kelamin',
        'nomor_referensi' => 'Nomor referensi',
        'nomor_surat' => 'Nomor surat',
        'tanggal' => 'Tanggal cetak',
        'tanggal_surat' => 'Tanggal surat (tanpa nama desa)',
        'tempat_tanggal_surat' => 'Kota & tanggal surat',
        'tempat_tanggal_lahir' => 'Tempat & tanggal lahir',
        'kop_surat' => 'Kop surat lengkap (4 baris)',
        'nama_desa' => 'Nama desa (dengan awalan Desa)',
        'desa' => 'Nama desa tanpa awalan Desa',
        'kecamatan' => 'Kecamatan',
        'kabupaten' => 'Kabupaten',
        'provinsi' => 'Provinsi',
        'alamat_kantor' => 'Alamat kantor desa',
        'alamat_desa' => 'Baris "Desa ... Kecamatan ..."',
        'alamat_kabupaten' => 'Baris "Kabupaten ..."',
        'kepala_desa' => 'Nama kepala desa pada dokumen',
        'jenis_surat' => 'Nama jenis surat',
    ];

    /** Pola penanda: {{ nama_penanda }} dengan huruf/angka/underscore. */
    private const POLA = '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/';

    /**
     * Penanda FRASA: bukan data, melainkan potongan kalimat yang kehadirannya
     * bergantung pada data lain. Template memakai ini agar tidak pernah
     * mencetak kalimat janggal seperti "yang berdomisili di ." saat warga
     * belum mengisi dusun.
     *
     * Kunci => [penanda syarat, teks saat syarat terisi]
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const PENANDA_FRASA = [
        'yang_berdomisili_di' => ['dusun', ' yang berdomisili di dusun %s'],
    ];

    /**
     * Daftar penanda kustom pada template, berurutan sesuai kemunculan.
     * Penanda otomatis dan duplikat dibuang.
     *
     * Bila admin sudah mengonfigurasi field formulir secara eksplisit
     * (kolom field_formulir), konfigurasi itulah yang dipakai dan penanda
     * dari template hanya melengkapinya. Ini penting agar admin bisa mengatur
     * label, tipe input, dan status wajib tanpa harus menulis ulang template.
     *
     * @return array<int, array{kunci: string, label: string, tipe?: string, wajib?: bool}>
     */
    public function penandaKustom(JenisSurat $jenisSurat): array
    {
        $eksplisit = $jenisSurat->fieldFormulir();
        $template = (string) $jenisSurat->template_surat;

        $dilihat = [];
        $hasil = [];

        // 1. Field yang dikonfigurasi admin lebih dulu (urutan & label dihormati).
        foreach ($eksplisit as $field) {
            $kunci = strtolower($field['kunci']);

            if (isset($dilihat[$kunci]) || array_key_exists($kunci, self::PENANDA_OTOMATIS) || isset(self::PENANDA_FRASA[$kunci])) {
                continue;
            }

            $dilihat[$kunci] = true;
            $hasil[] = [
                'kunci' => $kunci,
                'label' => $field['label'],
                'tipe' => $field['tipe'],
                'wajib' => $field['wajib'],
            ];
        }

        // 2. Lengkapi dengan penanda yang hanya muncul di template.
        if (trim($template) !== '') {
            preg_match_all(self::POLA, $template, $cocok);

            foreach ($cocok[1] ?? [] as $kunci) {
                $kunci = strtolower($kunci);

                if (isset($dilihat[$kunci]) || array_key_exists($kunci, self::PENANDA_OTOMATIS) || isset(self::PENANDA_FRASA[$kunci])) {
                    continue;
                }

                $dilihat[$kunci] = true;
                $hasil[] = [
                    'kunci' => $kunci,
                    'label' => $this->labelDariKunci($kunci),
                    'tipe' => 'text',
                    'wajib' => false,
                ];
            }
        }

        return $hasil;
    }

    /**
     * Template mentah (masih berisi penanda) untuk pratinjau live di browser.
     * Bila template kosong, dipakai susunan baku agar pratinjau tetap berarti.
     */
    public function mentah(JenisSurat $jenisSurat): string
    {
        $template = (string) $jenisSurat->template_surat;

        return trim($template) === '' ? $this->templateBaku($jenisSurat) : $template;
    }

    /**
     * Surat jadi dengan seluruh penanda tersunting.
     *
     * Penanda yang datanya kosong diganti titik-titik pada baris berlabel,
     * meniru dokumen Word desa ("NIK : ...................."), supaya surat
     * tidak mencetak ": " kosong. Di tengah kalimat, penanda kosong dibiarkan
     * kosong agar kalimatnya tidak rusak.
     *
     * @param  array<string, string>  $isian  isian kustom dari warga
     */
    public function susun(JenisSurat $jenisSurat, ?Pengajuan $pengajuan = null, array $isian = []): string
    {
        $template = $this->mentah($jenisSurat);

        $nilai = $this->nilaiOtomatis($pengajuan) + $isian;

        // Frasa pengisi disusun lebih dulu: potongan kalimat yang hanya muncul
        // bila data syaratnya terisi.
        foreach (self::PENANDA_FRASA as $kunci => [$syarat, $cetak]) {
            $isi = trim((string) ($nilai[$syarat] ?? ''));
            $nilai[$kunci] = $isi === '' ? '' : sprintf($cetak, $isi);
        }

        $baris = preg_split('/\r\n|\r|\n/', $template) ?: [];

        foreach ($baris as $i => $satu) {
            // Hanya baris berlabel ("Nama : {{nama}}") yang diberi titik-titik.
            $berlabel = (bool) preg_match('/^\s*[A-Za-z][A-Za-z .\/]*\s*:/', $satu);

            $baris[$i] = (string) preg_replace_callback(self::POLA, function (array $m) use ($nilai, $berlabel) {
                $isi = trim((string) ($nilai[strtolower($m[1])] ?? ''));

                if ($isi !== '') {
                    return $isi;
                }

                return $berlabel ? '....................' : '';
            }, $satu);
        }

        return $this->rapikanSpasi(implode("\n", $baris));
    }

    /**
     * Rapikan sisa spasi dan tanda baca setelah penanda disunting.
     *
     * Spasi sebelum titik dua DIPERTAHANKAN karena dipakai untuk meratakan
     * baris berlabel ("Nama                : ...").
     */
    private function rapikanSpasi(string $teks): string
    {
        $teks = (string) preg_replace('/(?<![.,])\s+([.,;])(?!\.)/', '$1', $teks);
        $teks = (string) preg_replace('/,(\s*[.,;])/', '$1', $teks);
        $teks = (string) preg_replace("/[ \t]+$/m", '', $teks);

        return (string) preg_replace("/\n{4,}/", "\n\n\n", $teks);
    }

    /**
     * Nilai untuk penanda otomatis.
     *
     * @return array<string, string>
     */
    public function nilaiOtomatis(?Pengajuan $pengajuan = null): array
    {
        $profil = \App\Support\SiteProfile::current();

        // Nama kepala desa mengikuti dokumen resmi desa. Nama ini tercetak
        // hitam pada semua template Word, bukan titik-titik isian.
        $kepala = \App\Support\PetaSuratDesa::NAMA_KEPALA_DESA;

        // Tanggal surat memakai tanggal pengajuan bila sudah ada, sehingga
        // surat lama tetap tercetak dengan tanggal saat warga mengajukan.
        $tanggal = $pengajuan?->created_at ?? now();

        $namaDesa = (string) $profil->nama_desa;
        $desaTanpaAwalan = $this->tanpaAwalan($namaDesa, ['Desa', 'Kelurahan']);
        $kecamatan = $this->tanpaAwalan((string) $profil->kecamatan, ['Kecamatan', 'Kec.']);
        $kabupaten = $this->tanpaAwalan((string) $profil->kabupaten, ['Kabupaten', 'Kab.']);

        $alamat = trim((string) ($pengajuan?->alamat ?? ''));

        $nilai = [
            'nama_desa' => $namaDesa,
            'desa' => $desaTanpaAwalan,
            'kecamatan' => $kecamatan,
            'kabupaten' => $kabupaten,
            'provinsi' => (string) $profil->provinsi,
            'alamat_kantor' => (string) $profil->alamat_kantor,
            'alamat_desa' => trim('Desa '.$desaTanpaAwalan.' Kecamatan '.$kecamatan),
            'alamat_kabupaten' => trim('Kabupaten '.$kabupaten),
            'dusun' => \App\Support\PetaSuratDesa::tanpaDusun($alamat),
            'kepala_desa' => $kepala,
            'tanggal' => $tanggal->translatedFormat('j F Y'),
            'tanggal_surat' => $tanggal->translatedFormat('j F Y'),
            'tempat_tanggal_surat' => $this->tempatTanggalSurat($namaDesa, $tanggal),
            'kop_surat' => $this->kopSurat($profil),
        ];

        if ($pengajuan !== null) {
            $nilai += [
                'nama' => (string) $pengajuan->nama,
                'alamat' => $alamat,
                'keperluan' => (string) $pengajuan->keperluan,
                'whatsapp' => (string) $pengajuan->whatsapp,
                'nik' => (string) ($pengajuan->nik ?? ''),
                'tempat_lahir' => (string) ($pengajuan->tempat_lahir ?? ''),
                'tanggal_lahir' => $pengajuan->tanggal_lahir?->translatedFormat('j F Y') ?? '',
                'jenis_kelamin' => (string) ($pengajuan->jenis_kelamin ?? ''),
                'nomor_referensi' => (string) $pengajuan->nomor_referensi,
                'nomor_surat' => (string) ($pengajuan->nomor_surat ?? ''),
                'jenis_surat' => (string) ($pengajuan->jenisSurat?->nama ?? ''),
                'tempat_tanggal_lahir' => $this->tempatTanggalLahir($pengajuan),
            ];
        }

        return $nilai;
    }

    /**
     * Kop surat empat baris seperti pada dokumen Word desa:
     *
     *   PEMERINTAH KABUPATEN <kabupaten>
     *   KECAMATAN <kecamatan>
     *   DESA <nama desa>
     *   <alamat kantor>
     *
     * Baris pemerintah / kecamatan dilewati bila datanya belum diisi admin,
     * supaya kop tidak mencetak "KABUPATEN" kosong.
     */
    public function kopSurat(?object $profil = null): string
    {
        $profil ??= \App\Support\SiteProfile::current();

        $kabupaten = $this->tanpaAwalan((string) $profil->kabupaten, ['Kabupaten', 'Kab.']);
        $kecamatan = $this->tanpaAwalan((string) $profil->kecamatan, ['Kecamatan', 'Kec.']);
        $desa = $this->tanpaAwalan((string) $profil->nama_desa, ['Desa', 'Kelurahan']);

        $baris = [];

        if ($kabupaten !== '') {
            $baris[] = 'PEMERINTAH KABUPATEN '.Str::upper($kabupaten);
        }

        if ($kecamatan !== '') {
            $baris[] = 'KECAMATAN '.Str::upper($kecamatan);
        }

        if ($desa !== '') {
            $baris[] = 'DESA '.Str::upper($desa);
        }

        if (filled($profil->alamat_kantor)) {
            $baris[] = trim((string) $profil->alamat_kantor);
        }

        return implode("\n", $baris);
    }

    /** "Karang Sari, 05 November 2026" untuk blok tanda tangan surat. */
    public function tempatTanggalSurat(string $namaDesa, ?\DateTimeInterface $tanggal = null): string
    {
        $tanggal ??= now();

        $tempat = $this->tanpaAwalan($namaDesa, ['Desa', 'Kelurahan']);

        return $tempat === ''
            ? $tanggal->translatedFormat('j F Y')
            : $tempat.', '.$tanggal->translatedFormat('j F Y');
    }

    /**
     * "Karang Sari, 14 Maret 1990". Nama tempat lahir tidak disimpan lagi pada
     * formulir warga, jadi baris ini hanya terisi bila data lamanya ada.
     */
    public function tempatTanggalLahir(?Pengajuan $pengajuan): string
    {
        if ($pengajuan === null) {
            return '';
        }

        $tempat = trim((string) ($pengajuan->tempat_lahir ?? ''));
        $tanggal = $pengajuan->tanggal_lahir?->translatedFormat('j F Y') ?? '';

        return trim(implode(', ', array_filter([$tempat, $tanggal])), ", \t");
    }

    /** Buang awalan "Desa"/"Kabupaten" agar tidak tercetak dua kali. */
    private function tanpaAwalan(string $teks, array $awalan): string
    {
        $teks = trim($teks);

        foreach ($awalan as $a) {
            if (Str::startsWith(Str::lower($teks), Str::lower($a).' ')) {
                return trim(Str::substr($teks, Str::length($a)));
            }
        }

        return $teks;
    }

    /** Template baku bila admin belum mengisi template surat. */
    public function templateBaku(JenisSurat $jenisSurat): string
    {
        return 'Yang bertanda tangan di bawah ini, Kepala {{nama_desa}}, menerangkan bahwa '
            .'{{nama}}, berdomisili di {{alamat}}, benar-benar memerlukan '
            .'{{jenis_surat}} untuk keperluan {{keperluan}}.'.PHP_EOL.PHP_EOL
            .'Demikian surat keterangan ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.';
    }

    /** "penghasilan_per_bulan" -> "Penghasilan per bulan". */
    public function labelDariKunci(string $kunci): string
    {
        $teks = str_replace('_', ' ', $kunci);

        return ucfirst(trim($teks));
    }
}
