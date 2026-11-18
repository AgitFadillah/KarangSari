<?php

namespace Tests\Feature;

use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Models\PerangkatDesa;
use App\Models\ProfilDesa;
use App\Services\SuratWordService;
use App\Support\SiteProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Surat jadi dalam format Word asli desa (.docx).
 *
 * Keluhan perangkat desa: pratinjau surat berbasis teks biasa "tidak sama
 * banget templatenya". Perbaikannya: dokumen resmi desa dipakai sebagai
 * kerangka, lalu hanya isi teksnya yang diganti. Test di bawah ini mengunci
 * janji itu — format asli tidak boleh berubah sedikit pun.
 */
class SuratWordTest extends TestCase
{
    use RefreshDatabase;

    /** Folder kerangka dokumen desa. */
    private const KERANGKA = 'surat/template-domisili';

    protected function setUp(): void
    {
        parent::setUp();
        SiteProfile::lupakan();
    }

    protected function jenisSurat(): JenisSurat
    {
        // Migrasi sudah memasang tujuh kategori resmi. Test memakai baris yang
        // sama dengan yang dilihat warga, bukan membuat kategori tiruan.
        return JenisSurat::query()->firstOrCreate(
            ['slug' => 'domisili'],
            [
                'kode' => 'SKD',
                'nama' => 'Surat Keterangan Domisili',
                'kode_layanan' => '470',
                'estimasi_hari' => '1 hari kerja',
                'deskripsi' => 'Keterangan tempat tinggal.',
                'template_surat' => 'Yang bertanda tangan di bawah ini menerangkan bahwa {{nama}}.',
                'is_aktif' => true,
            ]
        );
    }

    /**
     * Kategori resmi sesuai dokumen desa, dibuat bila migrasi belum mengisi.
     */
    private function jenisUntuk(string $slug): JenisSurat
    {
        $data = \App\Support\TemplateSuratDesa::katalog()[$slug];

        return JenisSurat::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'kode' => $data['kode'],
                'nama' => $data['nama'],
                'kode_layanan' => $data['kode_layanan'],
                'estimasi_hari' => $data['estimasi_hari'],
                'deskripsi' => $data['deskripsi'],
                'urutan' => $data['urutan'],
                'template_surat' => $data['template'],
                'field_formulir' => $data['isian'],
                'is_aktif' => true,
            ]
        );
    }

    protected function pengajuan(?JenisSurat $jenis = null, array $isian = []): Pengajuan
    {
        $pengajuan = new Pengajuan;
        $pengajuan->forceFill([
            'jenis_surat_id' => ($jenis ?? $this->jenisSurat())->id,
            'nomor_referensi' => 'KRS-2026-'.str_pad((string) (Pengajuan::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT),
            'nama' => 'Faiz Ridho',
            'nik' => '1801010101010001',
            'tempat_lahir' => 'Lampung Selatan',
            'tanggal_lahir' => '1990-03-14',
            'alamat' => 'Karang Sari',
            'keperluan' => 'Perpindahan Domisili',
            'whatsapp' => '08123456789',
            'isian_surat' => $isian,
            'persetujuan_data' => true,
            'persetujuan_at' => now(),
        ]);
        $pengajuan->save();

        return $pengajuan->refresh();
    }

    /**
     * Isian contoh per kategori, mengikuti titik-titik pada dokumen resmi.
     *
     * @return array<string, string>
     */
    private function isianContoh(string $slug): array
    {
        $umum = [
            'agama' => 'Islam',
            'status_perkawinan' => 'Kawin',
            'pekerjaan' => 'Petani',
        ];

        return match ($slug) {
            'usaha' => $umum + [
                'pekerjaan' => 'Wiraswasta',
                'nama_usaha' => 'Warung Kelontong Bu Siti',
                'alamat_usaha' => 'Sukamaju RT 03/RW 05',
            ],
            'kematian' => $umum + [
                'tanggal_meninggal' => '2026-10-02',
                'tempat_makam' => 'TPU Desa Karang Sari',
            ],
            'belum-menikah' => $umum + [
                'status_perkawinan' => 'Belum Menikah',
            ],
            'orang-tua-asuh' => $umum + [
                'nama_anak' => 'Salsabila Putri',
                'nik_anak' => '1801010101010106',
                'tempat_lahir_anak' => 'Lampung Selatan',
                'tanggal_lahir_anak' => '2012-03-17',
                'agama_anak' => 'Islam',
                'pekerjaan_anak' => 'Pelajar/Mahasiswa',
                'alamat_anak' => 'Sukamaju RT 01/RW 02',
                'sejak' => '2018',
            ],
            'pekerjaan' => $umum + [
                'pekerjaan' => 'Buruh Harian Lepas',
                'pekerjaan_sebenarnya' => 'Tukang Bangunan',
            ],
            default => $umum,
        };
    }

    /** Isi teks seluruh paragraf dokumen, digabung per baris. */
    private function teksSurat(Pengajuan $pengajuan, array $isian = []): string
    {
        $xml = app(SuratWordService::class)->documentXml($pengajuan->jenisSurat, $pengajuan, $isian);

        $doc = new \DOMDocument;
        $doc->loadXML($xml);

        $xp = new \DOMXPath($doc);
        $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $baris = [];

        foreach ($xp->query('//w:body//w:p') as $p) {
            $t = '';

            foreach ($xp->query('.//w:t', $p) as $tt) {
                $t .= $tt->textContent;
            }

            $baris[] = $t;
        }

        return implode("\n", $baris);
    }

    /**
     * Isi seluruh bagian paket .docx (nama bagian => isi).
     *
     * Dipakai untuk memeriksa bahwa gambar kop benar-benar ikut terpaket dan
     * relasinya tercatat, bukan hanya tampil di XML.
     *
     * @return array<string, string>
     */
    private function paket(JenisSurat $jenis, ?Pengajuan $pengajuan = null): array
    {
        $docx = app(SuratWordService::class)->isi($jenis, $pengajuan);

        $sementara = tempnam(sys_get_temp_dir(), 'uji').'.docx';
        file_put_contents($sementara, $docx);

        $zip = new \ZipArchive;
        $zip->open($sementara);

        $bagian = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nama = (string) $zip->getNameIndex($i);
            $bagian[$nama] = (string) $zip->getFromName($nama);
        }

        $zip->close();
        @unlink($sementara);

        return $bagian;
    }

    public function test_kerangka_dokumen_desa_tersedia(): void
    {
        // Tanpa kerangka asli, surat tidak mungkin "sama persis".
        $this->assertFileExists(resource_path(self::KERANGKA.'/word/document.xml'));
        $this->assertFileExists(resource_path(self::KERANGKA.'/word/styles.xml'));
        $this->assertFileExists(resource_path(self::KERANGKA.'/word/media/image1.png'));
    }

    public function test_bagian_format_dokumen_tidak_diubah(): void
    {
        // styles.xml, settings.xml, fontTable.xml, tema, dan rels adalah
        // sumber seluruh format (Bookman Old Style, ukuran huruf, spasi).
        // Semuanya harus disalin apa adanya.
        $jenis = $this->jenisSurat();
        $pengajuan = $this->pengajuan($jenis);

        $docx = app(SuratWordService::class)->isi($jenis, $pengajuan);

        $sementara = tempnam(sys_get_temp_dir(), 'uji').'.docx';
        file_put_contents($sementara, $docx);

        $zip = new \ZipArchive;
        $zip->open($sementara);

        foreach (['word/styles.xml', 'word/settings.xml', 'word/fontTable.xml', 'word/theme/theme1.xml', 'word/webSettings.xml'] as $bagian) {
            $this->assertSame(
                file_get_contents(resource_path(self::KERANGKA.'/'.$bagian)),
                $zip->getFromName($bagian),
                "Bagian {$bagian} harus identik dengan dokumen desa."
            );
        }

        $zip->close();
        @unlink($sementara);
    }

    public function test_surat_memuat_judul_dan_nomor(): void
    {
        $teks = $this->teksSurat($this->pengajuan());

        $this->assertStringContainsString('SURAT KETERANGAN DOMISILI', $teks);
        $this->assertStringContainsString('Nomor :', $teks);
    }

    public function test_kop_resmi_terpasang_paling_atas_sebagai_gambar(): void
    {
        // Kop bukan lagi teks yang dirakit, melainkan satu gambar resmi desa
        // (docs/kop surat.png) yang sudah memuat lambang, tiga baris nama
        // pemerintah, alamat, dan garis ganda.
        $pengajuan = $this->pengajuan();
        $xml = app(SuratWordService::class)->documentXml($pengajuan->jenisSurat, $pengajuan);

        $doc = new \DOMDocument;
        $doc->loadXML($xml);

        $xp = new \DOMXPath($doc);
        $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xp->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
        $xp->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
        $xp->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        // Anak pertama <w:body> adalah paragraf kop.
        $anak = $xp->query('/w:document/w:body/*');
        $this->assertSame('w:p', $anak->item(0)->nodeName);

        // Paragraf itu berisi gambar, tanpa teks kop (tidak boleh dobel).
        $this->assertSame(1, $xp->query('.//wp:inline', $anak->item(0))->length);
        $this->assertSame(0, $xp->query('.//w:t', $anak->item(0))->length);

        // Gambar memakai relasi kop dan selebar garis kop dokumen desa.
        $blip = $xp->query('.//a:blip', $anak->item(0))->item(0);
        $this->assertNotNull($blip);
        $this->assertSame('rId100', $blip->getAttribute('r:embed'));

        $extent = $xp->query('.//wp:extent', $anak->item(0))->item(0);
        $this->assertNotNull($extent);

        $gambar = getimagesize(public_path('images/kop-surat.png'));

        $this->assertSame(7019778, (int) $extent->getAttribute('cx'));
        $this->assertSame(
            (int) round(7019778 * $gambar[1] / $gambar[0]),
            (int) $extent->getAttribute('cy')
        );
    }

    public function test_teks_kop_lama_tidak_tercetak_lagi(): void
    {
        // Empat paragraf kop pada kerangka (lambang + tiga baris teks + dua
        // garis) harus benar-benar diganti, bukan ditambah. Kalau tidak,
        // kop akan tercetak dua kali.
        $teks = $this->teksSurat($this->pengajuan());

        foreach ([
            'PEMERINTAH KABUPATEN',
            'KECAMATAN JATI AGUNG',
            'DESA KARANG SARI',
            'Jl. RA. Basyid',
        ] as $lama) {
            $this->assertStringNotContainsString($lama, $teks, "Teks kop lama \"{$lama}\" tidak boleh tersisa.");
        }
    }

    public function test_gambar_kop_ikut_dalam_paket_docx(): void
    {
        $jenis = $this->jenisSurat();
        $bagian = $this->paket($jenis, $this->pengajuan($jenis));

        $this->assertArrayHasKey('word/media/kop-surat.png', $bagian);
        $this->assertSame(
            file_get_contents(public_path('images/kop-surat.png')),
            $bagian['word/media/kop-surat.png'],
            'Gambar kop di dalam .docx harus identik dengan kop resmi desa.'
        );
    }

    public function test_relasi_gambar_kop_ada_di_document_rels(): void
    {
        $jenis = $this->jenisSurat();
        $bagian = $this->paket($jenis, $this->pengajuan($jenis));

        $this->assertStringContainsString(
            'Target="media/kop-surat.png"',
            $bagian['word/_rels/document.xml.rels']
        );
        $this->assertStringContainsString(
            'Id="rId100"',
            $bagian['word/_rels/document.xml.rels']
        );
    }

    public function test_data_warga_tercetak_pada_baris_isian(): void
    {
        $teks = $this->teksSurat($this->pengajuan(), [
            'agama' => 'Islam',
            'status_perkawinan' => 'Belum Kawin',
            'pekerjaan' => 'Pelajar/Mahasiswa',
            'dusun' => 'Sukamaju',
        ]);

        $this->assertStringContainsString('Nama', $teks);
        $this->assertStringContainsString('Faiz Ridho', $teks);
        $this->assertStringContainsString('1801010101010001', $teks);
        $this->assertStringContainsString('Islam', $teks);
        $this->assertStringContainsString('Belum Kawin', $teks);
        $this->assertStringContainsString('Pelajar/Mahasiswa', $teks);
        $this->assertStringContainsString('Sukamaju', $teks);
    }

    public function test_isian_kosong_dicetak_sebagai_titik_titik(): void
    {
        // Dokumen desa yang belum diisi memakai titik-titik, bukan ": " kosong.
        $teks = $this->teksSurat($this->pengajuan());

        $this->assertStringContainsString('…………', $teks);
        // Titik dua tidak boleh tercetak ganda ("Agama: : ...").
        $this->assertStringNotContainsString(': :', $teks);
    }

    public function test_nama_kepala_desa_pada_dokumen_selalu_tercetak(): void
    {
        // "R O M S I, SH" tercetak hitam pada seluruh dokumen resmi desa,
        // jadi ia bagian tetap template — bukan isian yang boleh kosong.
        $teks = $this->teksSurat($this->pengajuan());

        $this->assertStringContainsString('R O M S I, SH', $teks);
    }

    public function test_tanggal_surat_memakai_tanggal_pengajuan(): void
    {
        // Warga mengisi formulir hari ini, maka tanggal surat = tanggal
        // pengajuan dibuat, bukan tanggal cetak/unduh.
        $pengajuan = $this->pengajuan();
        $pengajuan->forceFill(['created_at' => '2026-08-17 09:00:00'])->save();

        $teks = $this->teksSurat($pengajuan->refresh());

        $this->assertStringContainsString('17 Agustus 2026', $teks);
    }

    public function test_nomor_surat_tersimpan_tercetak(): void
    {
        $pengajuan = $this->pengajuan();
        $pengajuan->nomor_surat = '470/012/18.01.13.2019/XI/2026';
        $pengajuan->save();

        $this->assertStringContainsString(
            'Nomor : 470/012/18.01.13.2019/XI/2026',
            $this->teksSurat($pengajuan->refresh())
        );
    }

    public function test_nama_kepala_desa_tidak_diganti_data_perangkat_desa(): void
    {
        // Nama pejabat di database tidak boleh menimpa nama pada dokumen.
        PerangkatDesa::query()->create([
            'nama' => 'Pejabat Lain',
            'jabatan' => 'Kepala Desa',
            'urutan' => 1,
            'is_aktif' => true,
        ]);
        SiteProfile::lupakan();

        $teks = $this->teksSurat($this->pengajuan());

        $this->assertStringContainsString('R O M S I, SH', $teks);
        $this->assertStringNotContainsString('Pejabat Lain', $teks);
    }

    public function test_blok_tanda_tangan_memakai_nama_desa_dari_profil(): void
    {
        ProfilDesa::query()->updateOrCreate(['id' => 1], [
            'nama_desa' => 'Desa Karang Sari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => 'Lampung Selatan',
        ]);
        SiteProfile::lupakan();

        $teks = $this->teksSurat($this->pengajuan());

        $this->assertStringContainsString('Kepala Desa Karang Sari', $teks);
    }

    public function test_nama_desa_menempel_dirapikan(): void
    {
        // Data lama bisa menulis "Desa KarangSari"; kalimat pembuka tetap
        // terbaca "Kepala Desa Karang Sari".
        ProfilDesa::query()->updateOrCreate(['id' => 1], [
            'nama_desa' => 'Desa KarangSari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => 'Lampung Selatan',
        ]);
        SiteProfile::lupakan();

        $this->assertStringContainsString(
            'Kepala Desa Karang Sari',
            $this->teksSurat($this->pengajuan())
        );
    }

    public function test_berkas_docx_adalah_paket_zip_yang_sah(): void
    {
        $jenis = $this->jenisSurat();
        $path = app(SuratWordService::class)->berkas($jenis, $this->pengajuan($jenis));

        $this->assertFileExists($path);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $this->assertNotFalse($zip->getFromName('word/document.xml'));
        $this->assertNotFalse($zip->getFromName('[Content_Types].xml'));

        $zip->close();
        @unlink($path);
    }

    public function test_lambang_unggahan_admin_tidak_mengubah_kop_surat(): void
    {
        // Logo unggahan admin dipakai untuk logo situs (navbar, login,
        // favicon). Kop surat tetap memakai banner resmi desa, karena banner
        // itu sudah memuat lambang Kabupaten yang benar.
        \Illuminate\Support\Facades\Storage::fake('public');

        $logo = \Illuminate\Http\UploadedFile::fake()->image('lambang.png', 90, 116);
        \Illuminate\Support\Facades\Storage::disk('public')->putFileAs('profil', $logo, 'lambang.png');

        ProfilDesa::query()->updateOrCreate(['id' => 1], [
            'nama_desa' => 'Desa Karang Sari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => 'Lampung Selatan',
            'logo_path' => 'profil/lambang.png',
        ]);
        SiteProfile::lupakan();

        $jenis = $this->jenisSurat();
        $bagian = $this->paket($jenis, $this->pengajuan($jenis));

        // Lambang pada kerangka tidak diganti logo situs…
        $this->assertSame(
            file_get_contents(resource_path(self::KERANGKA.'/word/media/image1.png')),
            $bagian['word/media/image1.png']
        );

        // …dan kop surat tetap banner resmi desa.
        $this->assertSame(
            file_get_contents(public_path('images/kop-surat.png')),
            $bagian['word/media/kop-surat.png']
        );
    }

    public function test_alamat_desa_pada_baris_isian_tidak_dobel(): void
    {
        ProfilDesa::query()->updateOrCreate(['id' => 1], [
            'nama_desa' => 'Desa Karang Sari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => 'Lampung Selatan',
        ]);
        SiteProfile::lupakan();

        $teks = $this->teksSurat($this->pengajuan());

        // ": Desa Desa Karang Sari" adalah kesalahan yang harus dihindari.
        $this->assertStringNotContainsString('Desa Desa', $teks);
        $this->assertStringContainsString('Desa Karang Sari Kecamatan Jati Agung', $teks);
    }

    public function test_tujuh_kategori_resmi_memakai_isi_dokumen_desa(): void
    {
        ProfilDesa::query()->updateOrCreate(['id' => 1], [
            'nama_desa' => 'Desa Karang Sari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => 'Lampung Selatan',
        ]);
        SiteProfile::lupakan();

        // Judul dan ciri khas tiap dokumen resmi; semuanya harus tercetak
        // bersama identitas pemohon dan nama kepala desa pada template.
        $harapan = [
            'domisili' => ['SURAT KETERANGAN DOMISILI', 'yang berdomisili di dusun Karang Sari.'],
            'usaha' => ['SURAT KETERANGAN USAHA', 'Warung Kelontong Bu Siti'],
            'tidak-mampu' => ['SURAT KETERANGAN TIDAK MAMPU', 'benar-benar tidak mampu'],
            'kematian' => ['SURAT KETERANGAN MENINGGAL', 'TPU Desa Karang Sari'],
            'belum-menikah' => ['SURAT KETERANGAN BELUM MENIKAH', 'belum pernah menikah'],
            'orang-tua-asuh' => ['SURAT KETERANGAN ORANG TUA ASUH', 'Salsabila Putri'],
            'pekerjaan' => ['SURAT KETERANGAN PEKERJAAN', 'Tukang Bangunan'],
        ];

        foreach ($harapan as $slug => [$judul, $ciri]) {
            $jenis = $this->jenisUntuk($slug);
            $isian = $this->isianContoh($slug);
            $pengajuan = $this->pengajuan($jenis, $isian);

            $teks = $this->teksSurat($pengajuan, $isian);

            $this->assertStringContainsString($judul, $teks, "Judul {$slug} harus dari dokumen desa.");
            $this->assertStringContainsString('Faiz Ridho', $teks, "Nama pemohon {$slug} tidak tercetak.");
            $this->assertStringContainsString('1801010101010001', $teks, "NIK pemohon {$slug} tidak tercetak.");
            $this->assertStringContainsString('Islam', $teks, "Agama pemohon {$slug} tidak tercetak.");
            $this->assertStringContainsString($ciri, $teks, "Ciri khas {$slug} tidak tercetak.");
            $this->assertStringContainsString('R O M S I, SH', $teks, "Nama kepala desa {$slug} tidak tercetak.");

            // Tidak ada penanda template yang bocor ke surat jadi.
            $this->assertStringNotContainsString('{{', $teks);
            $this->assertStringNotContainsString('}}', $teks);
        }
    }

    public function test_nama_berkas_unduhan_enak_dibaca(): void
    {
        $jenis = $this->jenisSurat();
        $nama = app(SuratWordService::class)->namaUnduhan($jenis, $this->pengajuan($jenis));

        $this->assertSame('Surat-Keterangan-Domisili-Faiz-Ridho-KRS-2026-0001.docx', $nama);
    }
}
