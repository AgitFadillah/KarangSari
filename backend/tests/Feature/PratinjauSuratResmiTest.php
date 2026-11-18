<?php

namespace Tests\Feature;

use App\Models\JenisSurat;
use App\Models\ProfilDesa;
use App\Support\SiteProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pratinjau surat untuk formulir warga.
 *
 * Keluhan perangkat desa: pratinjau surat berbasis teks template "tidak sama
 * banget templatenya". Formulir warga kini meminta teks surat ke server, dan
 * server menyusunnya dari dokumen Word desa yang sama dengan berkas unduhan.
 * Test ini mengunci perilaku itu: apa yang dilihat warga = apa yang tercetak.
 */
class PratinjauSuratResmiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ProfilDesa::query()->updateOrCreate(['id' => 1], [
            'nama_desa' => 'Desa Karang Sari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => 'Lampung Selatan',
            'provinsi' => 'Lampung',
            'alamat_kantor' => 'Jl. RA. Basyid Desa Karang Sari Kecamatan Jati Agung Kabupaten Lampung Selatan',
            'kode_pos' => '35365',
        ]);

        SiteProfile::lupakan();
    }

    protected function jenisSurat(): JenisSurat
    {
        // Migrasi memasang tujuh kategori resmi; test memakai baris yang sama
        // agar kerangka Word dan template yang diuji memang yang dipakai warga.
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

    public function test_pratinjau_mengembalikan_teks_surat_resmi(): void
    {
        $respons = $this->postJson(
            route('pengajuan.pratinjau', $this->jenisSurat()),
            [
                'nama' => 'Faiz Ridho',
                'nik' => '1801010101010001',
                'tempat_lahir' => 'Lampung Selatan',
                'tanggal_lahir' => '1990-03-14',
                'alamat' => 'Dusun Sukamaju',
                'isian' => [
                    'agama' => 'Islam',
                    'status_perkawinan' => 'Belum Kawin',
                    'pekerjaan' => 'Pelajar/Mahasiswa',
                    'dusun' => 'Sukamaju',
                ],
            ]
        );

        $respons->assertOk()->assertJsonStructure(['teks']);

        $teks = (string) $respons->json('teks');

        // Judul surat berasal dari dokumen desa. Kop tidak ikut sebagai teks:
        // kop adalah gambar resmi yang ditampilkan di atas pratinjau.
        $this->assertStringContainsString('SURAT KETERANGAN DOMISILI', $teks);
        $this->assertStringNotContainsString('PEMERINTAH KABUPATEN', $teks);

        // Isian warga yang dikirim ikut tercetak.
        $this->assertStringContainsString('Faiz Ridho', $teks);
        $this->assertStringContainsString('1801010101010001', $teks);
        $this->assertStringContainsString('Lampung Selatan, 14 Maret 1990', $teks);
        $this->assertStringContainsString('Islam', $teks);
        $this->assertStringContainsString('Belum Kawin', $teks);
        $this->assertStringContainsString('Pelajar/Mahasiswa', $teks);
        $this->assertStringContainsString('Sukamaju', $teks);

        // Nama kepala desa mengikuti dokumen resmi, bukan data perangkat desa.
        $this->assertStringContainsString('R O M S I, SH', $teks);
    }

    public function test_pratinjau_tanpa_isian_tetap_memuat_titik_titik(): void
    {
        // Warga yang baru membuka formulir tetap melihat bentuk surat aslinya.
        $respons = $this->postJson(
            route('pengajuan.pratinjau', $this->jenisSurat()),
            ['isian' => []]
        );

        $respons->assertOk();

        $this->assertStringContainsString('…………', (string) $respons->json('teks'));
    }

    public function test_isian_bukan_array_ditolak(): void
    {
        // Payload aneh tidak boleh memicu galat 500.
        $this->postJson(
            route('pengajuan.pratinjau', $this->jenisSurat()),
            ['isian' => 'bukan-array']
        )->assertStatus(422);
    }

    public function test_slug_jenis_surat_tidak_dikenal_menghasilkan_404(): void
    {
        $this->postJson('/pengajuan/pratinjau/tidak-ada', ['isian' => []])
            ->assertNotFound();
    }

    public function test_isian_terlalu_panjang_dipotong(): void
    {
        // Batas 200 karakter mencegah payload besar membanjiri pratinjau.
        $respons = $this->postJson(
            route('pengajuan.pratinjau', $this->jenisSurat()),
            ['isian' => ['pekerjaan' => str_repeat('A', 500)]]
        );

        $respons->assertOk();

        $teks = (string) $respons->json('teks');

        $this->assertStringContainsString(str_repeat('A', 200), $teks);
        $this->assertStringNotContainsString(str_repeat('A', 201), $teks);
    }

    public function test_formulir_warga_memuat_kop_dan_pratinjau_surat_resmi(): void
    {
        $this->jenisSurat();

        // Kop resmi tampil sebagai gambar di atas pratinjau, memakai berkas
        // yang sama dengan yang disisipkan ke .docx.
        $this->assertFileExists(public_path('images/kop-surat.png'));

        $this->get(route('pengajuan.create', ['jenis' => 'domisili']))
            ->assertOk()
            ->assertSee('class="letter-kop"', false)
            ->assertSee('src="/images/kop-surat.png"', false)
            ->assertSee('SURAT KETERANGAN DOMISILI', false)
            ->assertDontSee('PEMERINTAH KABUPATEN', false);
    }
}
