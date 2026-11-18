<?php

namespace App\Support;

use App\Models\JenisSurat;

/**
 * Template surat resmi desa, disalin dari dokumen Word yang diberikan
 * perangkat desa (folder docs/).
 *
 * Ada TUJUH dokumen resmi:
 *  - SURAT KET. DOMISILI.docx
 *  - SURAT KET. USAHA.docx
 *  - SURAT KET. TIDAK MAMPU.docx
 *  - SURAT KET. MENINGGAL.docx
 *  - SURAT KET. BELUM MENIKAH.docx
 *  - SURAT KET. ORANG TUA ASUH WALI.docx
 *  - SURAT KET. PEKERJAAN.docx
 *
 * Teks di bawah ini bukan karangan: susunan kalimat, urutan baris, judul,
 * penutup, dan nama kepala desa mengikuti dokumen aslinya. Setiap bagian yang
 * pada dokumen masih berupa titik-titik dijadikan penanda {{...}} agar terisi
 * otomatis dari data pemohon dan isian warga.
 *
 * Berkas Word yang dicetak memakai kerangka .docx asli di resources/surat,
 * sedangkan teks di sini menjadi sumber isian formulir dan pratinjau teks.
 */
class TemplateSuratDesa
{
    public const KUNCI_KATALOG = 'template_surat.catalog';

    /**
     * Template resmi per slug jenis surat, lengkap dengan metadata layanan.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function katalog(): array
    {
        return [
            'domisili' => [
                'sumber' => 'docs/SURAT KET. DOMISILI.docx',
                'template' => self::domisili(),
                'kode' => 'SKD',
                'kode_layanan' => '470',
                'nama' => 'Surat Keterangan Domisili',
                'deskripsi' => 'Keterangan resmi tempat tinggal warga yang digunakan untuk keperluan sekolah, pekerjaan, perbankan, atau administrasi kependudukan.',
                'estimasi_hari' => '1 hari kerja',
                'urutan' => 1,
                'persyaratan' => [
                    ['label' => 'Surat pengantar RT/RW', 'petunjuk' => 'Pengantar permohonan dari lingkungan warga (bila ada).'],
                ],
                'isian' => [
                    ['kunci' => 'agama', 'label' => 'Agama', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'status_perkawinan', 'label' => 'Status perkawinan', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'pekerjaan', 'label' => 'Pekerjaan', 'tipe' => 'text', 'wajib' => true],
                ],
            ],

            'usaha' => [
                'sumber' => 'docs/SURAT KET. USAHA.docx',
                'template' => self::usaha(),
                'kode' => 'SKU',
                'kode_layanan' => '471',
                'nama' => 'Surat Keterangan Usaha',
                'deskripsi' => 'Keterangan kepemilikan dan lokasi usaha untuk pengajuan kredit, izin usaha mikro, atau kemitraan dengan pihak ketiga.',
                'estimasi_hari' => '2 hari kerja',
                'urutan' => 2,
                'persyaratan' => [
                    ['label' => 'Foto tempat usaha', 'petunjuk' => 'Tampak depan lokasi usaha yang dijalankan.'],
                ],
                'isian' => [
                    ['kunci' => 'agama', 'label' => 'Agama', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'pekerjaan', 'label' => 'Pekerjaan', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'nama_usaha', 'label' => 'Nama/jenis usaha', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'alamat_usaha', 'label' => 'Alamat usaha (dusun, RT/RW)', 'tipe' => 'text', 'wajib' => true],
                ],
            ],

            'tidak-mampu' => [
                'sumber' => 'docs/SURAT KET. TIDAK MAMPU.docx',
                'template' => self::tidakMampu(),
                'kode' => 'SKTM',
                'kode_layanan' => '472',
                'nama' => 'Surat Keterangan Tidak Mampu',
                'deskripsi' => 'Keterangan kondisi ekonomi untuk keperluan beasiswa, bantuan sosial, atau keringanan biaya layanan kesehatan.',
                'estimasi_hari' => '2 hari kerja',
                'urutan' => 3,
                'persyaratan' => [
                    ['label' => 'Surat pengantar RT/RW', 'petunjuk' => 'Pengantar permohonan dari lingkungan warga.'],
                ],
                'isian' => [
                    ['kunci' => 'agama', 'label' => 'Agama', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'status_perkawinan', 'label' => 'Status perkawinan', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'pekerjaan', 'label' => 'Pekerjaan', 'tipe' => 'text', 'wajib' => true],
                ],
            ],

            'kematian' => [
                'sumber' => 'docs/SURAT KET. MENINGGAL.docx',
                'template' => self::meninggal(),
                'kode' => 'SKK',
                'kode_layanan' => '474',
                'nama' => 'Surat Keterangan Meninggal',
                'deskripsi' => 'Keterangan resmi peristiwa kematian warga untuk pengurusan akta kematian dan administrasi warisan.',
                'estimasi_hari' => '1 hari kerja',
                'urutan' => 4,
                'persyaratan' => [
                    ['label' => 'Surat keterangan fasilitas kesehatan', 'petunjuk' => 'Keterangan kematian dari bidan, dokter, atau rumah sakit.'],
                ],
                'isian' => [
                    ['kunci' => 'agama', 'label' => 'Agama', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'status_perkawinan', 'label' => 'Status perkawinan', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'pekerjaan', 'label' => 'Pekerjaan', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'tanggal_meninggal', 'label' => 'Tanggal meninggal', 'tipe' => 'date', 'wajib' => true],
                    ['kunci' => 'tempat_makam', 'label' => 'Tempat dimakamkan', 'tipe' => 'text', 'wajib' => true],
                ],
            ],

            'belum-menikah' => [
                'sumber' => 'docs/SURAT KET. BELUM MENIKAH.docx',
                'template' => self::belumMenikah(),
                'kode' => 'SKBM',
                'kode_layanan' => '476',
                'nama' => 'Surat Keterangan Belum Menikah',
                'deskripsi' => 'Keterangan status belum pernah menikah untuk keperluan administrasi pernikahan, sekolah, atau pekerjaan.',
                'estimasi_hari' => '1 hari kerja',
                'urutan' => 5,
                'persyaratan' => [],
                'isian' => [
                    ['kunci' => 'agama', 'label' => 'Agama', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'status_perkawinan', 'label' => 'Status perkawinan', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'pekerjaan', 'label' => 'Pekerjaan', 'tipe' => 'text', 'wajib' => true],
                ],
            ],

            'orang-tua-asuh' => [
                'sumber' => 'docs/SURAT KET. ORANG TUA ASUH WALI.docx',
                'template' => self::orangTuaAsuh(),
                'kode' => 'SKOTA',
                'kode_layanan' => '477',
                'nama' => 'Surat Keterangan Orang Tua Asuh/Wali',
                'deskripsi' => 'Keterangan hubungan orang tua asuh/wali dengan anak asuh untuk keperluan administrasi sekolah atau kependudukan.',
                'estimasi_hari' => '1 hari kerja',
                'urutan' => 6,
                'persyaratan' => [],
                'isian' => [
                    ['kunci' => 'agama', 'label' => 'Agama', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'pekerjaan', 'label' => 'Pekerjaan', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'nama_anak', 'label' => 'Nama anak asuh', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'nik_anak', 'label' => 'NIK anak asuh', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'tempat_lahir_anak', 'label' => 'Tempat lahir anak asuh', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'tanggal_lahir_anak', 'label' => 'Tanggal lahir anak asuh', 'tipe' => 'date', 'wajib' => true],
                    ['kunci' => 'agama_anak', 'label' => 'Agama anak asuh', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'pekerjaan_anak', 'label' => 'Pekerjaan anak asuh', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'alamat_anak', 'label' => 'Alamat anak asuh (dusun, RT/RW)', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'sejak', 'label' => 'Menjadi orang tua asuh sejak', 'tipe' => 'text', 'wajib' => true],
                ],
            ],

            'pekerjaan' => [
                'sumber' => 'docs/SURAT KET. PEKERJAAN.docx',
                'template' => self::pekerjaan(),
                'kode' => 'SKP',
                'kode_layanan' => '478',
                'nama' => 'Surat Keterangan Pekerjaan',
                'deskripsi' => 'Keterangan pekerjaan sebenarnya warga untuk keperluan administrasi pekerjaan, perbankan, atau instansi lain.',
                'estimasi_hari' => '1 hari kerja',
                'urutan' => 7,
                'persyaratan' => [],
                'isian' => [
                    ['kunci' => 'agama', 'label' => 'Agama', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'status_perkawinan', 'label' => 'Status perkawinan', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'pekerjaan', 'label' => 'Pekerjaan pada KTP/KK', 'tipe' => 'text', 'wajib' => true],
                    ['kunci' => 'pekerjaan_sebenarnya', 'label' => 'Pekerjaan sebenarnya', 'tipe' => 'text', 'wajib' => true],
                ],
            ],
        ];
    }

    /**
     * Template resmi untuk sebuah jenis surat, atau null bila dokumen desa
     * belum tersedia.
     */
    public static function untuk(string $slug): ?string
    {
        return self::katalog()[$slug]['template'] ?? null;
    }

    /** Nama berkas sumber dokumen desa untuk sebuah jenis surat. */
    public static function sumber(string $slug): ?string
    {
        return self::katalog()[$slug]['sumber'] ?? null;
    }

    /** Judul surat persis seperti dokumen resmi desa. */
    public static function judul(string $slug): ?string
    {
        $nama = self::katalog()[$slug]['nama'] ?? null;

        return $nama === null ? null : mb_strtoupper($nama);
    }

    /**
     * Konfigurasi kolom isian bawaan untuk sebuah jenis surat.
     *
     * @return array<int, array{kunci: string, label: string, tipe: string, wajib: bool}>
     */
    public static function isian(string $slug): array
    {
        return self::katalog()[$slug]['isian'] ?? [];
    }

    /**
     * Apakah jenis surat ini masih memakai template bawaan sistem (belum
     * diganti dokumen resmi desa).
     */
    public static function perluDokumenDesa(JenisSurat $jenisSurat): bool
    {
        return self::untuk((string) $jenisSurat->slug) === null;
    }

    /** Kop dan identitas desa mengikuti profil yang tersimpan. */
    private static function pembuka(): string
    {
        return 'Yang bertanda tangan dibawah ini, Kepala {{nama_desa}} Kecamatan {{kecamatan}} Kabupaten {{kabupaten}} menerangkan dengan sebenar-benarnya bahwa :';
    }

    /** Blok tanda tangan: tanggal pengajuan dan nama kepala desa pada dokumen. */
    private static function penutup(): array
    {
        return [
            '',
            '',
            '{{tempat_tanggal_surat}}',
            'Kepala {{nama_desa}}',
            '',
            '',
            '',
            PetaSuratDesa::NAMA_KEPALA_DESA,
        ];
    }

    /**
     * Susun template dari baris-baris. Baris pembuka, identitas, dan penutup
     * dipakai berulang agar tidak ada perbedaan penulisan antar surat.
     *
     * @param  array<int, string>  $baris
     */
    private static function susun(array $baris): string
    {
        return implode("\n", $baris);
    }

    private static function domisili(): string
    {
        return self::susun([
            '{{kop_surat}}',
            '',
            'SURAT KETERANGAN DOMISILI',
            'Nomor : {{nomor_surat}}',
            '',
            '',
            self::pembuka(),
            '',
            'Nama                : {{nama}}',
            'NIK                 : {{nik}}',
            'Tempat Tanggal Lahir: {{tempat_tanggal_lahir}}',
            'Agama               : {{agama}}',
            'Status Perkawinan   : {{status_perkawinan}}',
            'Pekerjaan           : {{pekerjaan}}',
            'Alamat              : Dusun {{dusun}}',
            '',
            'Bahwa orang tersebut diatas pada saat ini adalah benar warga {{nama_desa}} Kecamatan {{kecamatan}} Kabupaten {{kabupaten}} yang berdomisili di dusun {{dusun}}.',
            '',
            'Demikian Surat Keterangan Domisili ini dibuat dengan sebenarnya dan untuk dapat dipergunakan sebagaimana mestinya.',
            ...self::penutup(),
        ]);
    }

    private static function usaha(): string
    {
        return self::susun([
            '{{kop_surat}}',
            '',
            'SURAT KETERANGAN USAHA',
            'Nomor : {{nomor_surat}}',
            '',
            '',
            self::pembuka(),
            '',
            'Nama                : {{nama}}',
            'NIK                 : {{nik}}',
            'Tempat Tanggal Lahir: {{tempat_tanggal_lahir}}',
            'Agama               : {{agama}}',
            'Pekerjaan           : {{pekerjaan}}',
            'Alamat              : Dusun {{dusun}}',
            '',
            'Bahwa orang tersebut diatas Adalah benar-benar memiliki usaha yaitu :',
            '',
            'Nama/Jenis Usaha    : {{nama_usaha}}',
            'Alamat Usaha        : Dusun {{alamat_usaha}} : Desa {{desa}} Kecamatan {{kecamatan}}',
            '                    : Kabupaten {{kabupaten}}',
            '',
            'Demikian Surat Keterangan Usaha ini dibuat dengan sebenarnya dan untuk dapat dipergunakan sebagaimana mestinya.',
            ...self::penutup(),
        ]);
    }

    private static function tidakMampu(): string
    {
        return self::susun([
            '{{kop_surat}}',
            '',
            'SURAT KETERANGAN TIDAK MAMPU',
            'Nomor : {{nomor_surat}}',
            '',
            '',
            self::pembuka(),
            '',
            'Nama                : {{nama}}',
            'NIK                 : {{nik}}',
            'Tempat Tanggal Lahir: {{tempat_tanggal_lahir}}',
            'Agama               : {{agama}}',
            'Status Perkawinan   : {{status_perkawinan}}',
            'Pekerjaan           : {{pekerjaan}}',
            'Alamat              : Dusun {{dusun}}',
            '',
            'Bahwa orang tersebut diatas adalah warga {{nama_desa}} Kecamatan {{kecamatan}} Kabupaten {{kabupaten}} dan benar bahwa orang tersebut adalah keluarga yang benar-benar tidak mampu dan berpenghasilan tidak tetap.',
            'Selanjutnya Surat keterangan ini dibuat untuk mengurus BPJS/KIP KULIAH/PENURUNAN DESIL.',
            'Demikian Surat Keterangan Tidak Mampu ini dibuat dengan sebenarnya dan untuk dapat dipergunakan sebagaimana mestinya.',
            ...self::penutup(),
        ]);
    }

    private static function meninggal(): string
    {
        return self::susun([
            '{{kop_surat}}',
            '',
            'SURAT KETERANGAN MENINGGAL',
            'Nomor : {{nomor_surat}}',
            '',
            '',
            self::pembuka(),
            '',
            'Nama                : {{nama}}',
            'NIK                 : {{nik}}',
            'Tempat Tanggal Lahir: {{tempat_tanggal_lahir}}',
            'Agama               : {{agama}}',
            'Status Perkawinan   : {{status_perkawinan}}',
            'Pekerjaan           : {{pekerjaan}}',
            'Alamat              : Dusun {{dusun}}',
            '',
            'Bahwa orang tersebut diatas pada saat ini adalah benar warga {{nama_desa}} Kecamatan {{kecamatan}} Kabupaten {{kabupaten}}, dan orang tersebut telah meninggal dunia,',
            'Pada tanggal        : {{tanggal_meninggal}}',
            'Telah dimakamkan di : {{tempat_makam}}',
            'Demikian Surat Keterangan Meninggal Dunia ini dibuat dengan sebenarnya dan untuk dapat dipergunakan sebagaimana mestinya.',
            ...self::penutup(),
        ]);
    }

    private static function belumMenikah(): string
    {
        return self::susun([
            '{{kop_surat}}',
            '',
            'SURAT KETERANGAN BELUM MENIKAH',
            'Nomor : {{nomor_surat}}',
            '',
            '',
            self::pembuka(),
            '',
            'Nama                : {{nama}}',
            'NIK                 : {{nik}}',
            'Tempat Tanggal Lahir: {{tempat_tanggal_lahir}}',
            'Agama               : {{agama}}',
            'Status Perkawinan   : {{status_perkawinan}}',
            'Pekerjaan           : {{pekerjaan}}',
            'Alamat              : Dusun {{dusun}}',
            '',
            'Bahwa orang tersebut diatas pada saat ini adalah benar warga {{nama_desa}} Kecamatan {{kecamatan}} Kabupaten {{kabupaten}}.',
            'Selanjutnya orang tersebut diatas benar-benar belum pernah menikah.',
            'Demikian Surat Keterangan Belum Menikah ini dibuat dengan sebenarnya dan untuk dapat dipergunakan sebagaimana mestinya.',
            ...self::penutup(),
        ]);
    }

    private static function orangTuaAsuh(): string
    {
        return self::susun([
            '{{kop_surat}}',
            '',
            'SURAT KETERANGAN ORANG TUA ASUH/WALI',
            'Nomor : {{nomor_surat}}',
            '',
            '',
            self::pembuka(),
            '',
            'Nama                : {{nama}}',
            'NIK                 : {{nik}}',
            'Tempat Tanggal Lahir: {{tempat_tanggal_lahir}}',
            'Agama               : {{agama}}',
            'Pekerjaan           : {{pekerjaan}}',
            'Alamat              : Dusun {{dusun}}',
            '',
            'Bahwa orang tersebut diatas Adalah benar Orang Tua Asuh dari :',
            '',
            'Nama                : {{nama_anak}}',
            'NIK                 : {{nik_anak}}',
            'Tempat Tanggal Lahir: {{tempat_tanggal_lahir_anak}}',
            'Agama               : {{agama_anak}}',
            'Pekerjaan           : {{pekerjaan_anak}}',
            'Alamat              : Dusun {{alamat_anak}}',
            '',
            'Dan menjadi orang tua asuh sejak {{sejak}}',
            'Demikian Surat Keterangan Orang Tua Asuh ini dibuat dengan sebenarnya dan untuk dapat dipergunakan sebagaimana mestinya.',
            ...self::penutup(),
        ]);
    }

    private static function pekerjaan(): string
    {
        return self::susun([
            '{{kop_surat}}',
            '',
            'SURAT KETERANGAN PEKERJAAN',
            'Nomor : {{nomor_surat}}',
            '',
            '',
            self::pembuka(),
            '',
            'Nama                : {{nama}}',
            'NIK                 : {{nik}}',
            'Tempat Tanggal Lahir: {{tempat_tanggal_lahir}}',
            'Agama               : {{agama}}',
            'Status Perkawinan   : {{status_perkawinan}}',
            'Pekerjaan           : {{pekerjaan}}',
            'Alamat              : Dusun {{dusun}}',
            '',
            'Bahwa orang tersebut diatas pada saat ini adalah benar warga {{nama_desa}} Kecamatan {{kecamatan}} Kabupaten {{kabupaten}}.',
            'Selanjutnya Identitas orang tersebut yang tertera pada Kartu Tanda Penduduk (KTP) dan Kartu Keluarga (KK) Status pekerjaan sebagai {{pekerjaan}}, namun sesungguhnya yang benar pada saat ini pekerjaan yang sebenarnya Adalah sebagai {{pekerjaan_sebenarnya}}.',
            'Demikian Surat Keterangan Pekerjaan ini dibuat dengan sebenarnya dan untuk dapat dipergunakan sebagaimana mestinya.',
            ...self::penutup(),
        ]);
    }
}
