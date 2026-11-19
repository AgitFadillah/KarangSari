<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Models\PerangkatDesa;
use App\Models\User;
use App\Services\TemplateSuratService;
use App\Support\TemplateSuratDesa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Template surat resmi desa.
 *
 * Perangkat desa menyerahkan dokumen Word surat dan meminta
 * isinya dipakai apa adanya: kop surat, kalimat, dan blok tanda tangan tidak
 * boleh dikarang sistem. Yang berubah hanya bagian titik-titik, yang menjadi
 * penanda {{...}} agar terisi otomatis dari data pemohon dan isian warga.
 * Dokumen aslinya tidak disertakan di repositori ini.
 */
class TemplateSuratDesaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Support\SiteProfile::lupakan();
    }

    protected function jenisDomisili(): JenisSurat
    {
        return JenisSurat::query()->firstOrCreate(
            ['slug' => 'domisili'],
            [
                'kode' => 'SKD',
                'kode_layanan' => '470',
                'nama' => 'Surat Keterangan Domisili',
                'estimasi_hari' => '1 hari kerja',
                'deskripsi' => 'Keterangan tempat tinggal.',
                'template_surat' => TemplateSuratDesa::untuk('domisili'),
                'is_aktif' => true,
            ]
        );
    }

    protected function pengajuan(array $atribut = []): Pengajuan
    {
        $pengajuan = new Pengajuan;
        $pengajuan->forceFill(array_merge([
            'jenis_surat_id' => $this->jenisDomisili()->id,
            'nomor_referensi' => 'KRS-2026-0001',
            'nama' => 'Faiz Ridho',
            'alamat' => 'RT 02 Dusun I',
            'keperluan' => 'Perpindahan domisili',
            'whatsapp' => '08123456789',
            'persetujuan_data' => true,
            'persetujuan_at' => now(),
        ], $atribut));
        $pengajuan->save();

        return $pengajuan->refresh();
    }

    public function test_template_domisili_memakai_teks_dokumen_desa(): void
    {
        $template = TemplateSuratDesa::untuk('domisili');

        $this->assertNotNull($template);

        // Kalimat kunci yang memang ada di dokumen resmi surat domisili.
        $this->assertStringContainsString('SURAT KETERANGAN DOMISILI', $template);
        $this->assertStringContainsString(
            'menerangkan dengan sebenar-benarnya bahwa :',
            $template
        );
        $this->assertStringContainsString(
            'Demikian Surat Keterangan Domisili ini dibuat dengan sebenarnya dan untuk dapat dipergunakan sebagaimana mestinya.',
            $template
        );
        // Kop dan tanda tangan disusun dari penanda, bukan teks tetap.
        $this->assertStringContainsString('{{kop_surat}}', $template);
        $this->assertStringContainsString(\App\Support\PetaSuratDesa::NAMA_KEPALA_DESA, $template);
        $this->assertStringContainsString('{{tempat_tanggal_surat}}', $template);
        $this->assertStringNotContainsString('{{kepala_desa}}', $template);
    }

    public function test_kop_surat_disusun_dari_profil_desa(): void
    {
        $profil = \App\Support\SiteProfile::current();
        $profil->forceFill([
            'nama_desa' => 'Desa Karang Sari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => 'Lampung Selatan',
            'alamat_kantor' => 'Jl. RA. Basyid Desa Karang Sari Kecamatan Jati Agung',
        ])->save();
        \App\Support\SiteProfile::lupakan();

        $kop = app(TemplateSuratService::class)->kopSurat();

        $this->assertSame(
            "PEMERINTAH KABUPATEN LAMPUNG SELATAN\n"
            ."KECAMATAN JATI AGUNG\n"
            ."DESA KARANG SARI\n"
            .'Jl. RA. Basyid Desa Karang Sari Kecamatan Jati Agung',
            $kop
        );
    }

    public function test_kop_tidak_mencetak_kabupaten_kosong(): void
    {
        $profil = \App\Support\SiteProfile::current();
        $profil->forceFill([
            'nama_desa' => 'Desa Karang Sari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => null,
            'alamat_kantor' => null,
        ])->save();
        \App\Support\SiteProfile::lupakan();

        $kop = app(TemplateSuratService::class)->kopSurat();

        $this->assertStringNotContainsString('PEMERINTAH KABUPATEN', $kop);
        $this->assertSame("KECAMATAN JATI AGUNG\nDESA KARANG SARI", $kop);
    }

    public function test_nomor_surat_ganda_tidak_terjadi(): void
    {
        // Profil menyimpan nama desa dengan awalan "Desa"; kop tidak boleh
        // mencetak "DESA DESA ...".
        $this->assertStringNotContainsString('DESA DESA', app(TemplateSuratService::class)->kopSurat());
    }

    public function test_isian_warga_hanya_yang_ditanyakan_template(): void
    {
        $penanda = collect(app(TemplateSuratService::class)->penandaKustom($this->jenisDomisili()))
            ->pluck('kunci')
            ->all();

        // Agama, status perkawinan, dan pekerjaan memang titik-titik pada
        // dokumen desa, jadi ditanyakan ke warga.
        $this->assertContains('agama', $penanda);
        $this->assertContains('status_perkawinan', $penanda);
        $this->assertContains('pekerjaan', $penanda);

        // Penanda otomatis dan penanda frasa tidak boleh jadi kolom isian.
        $this->assertNotContains('nama', $penanda);
        $this->assertNotContains('nik', $penanda);
        $this->assertNotContains('kop_surat', $penanda);
        $this->assertNotContains('yang_berdomisili_di', $penanda);
    }

    public function test_surat_terisi_dari_data_pengajuan_dan_isian_warga(): void
    {
        // Nama pejabat di database berbeda dari dokumen: surat harus tetap
        // mencetak nama yang tercetak pada dokumen resmi desa.
        PerangkatDesa::create([
            'nama' => 'Pejabat Lain',
            'jabatan' => 'Kepala Desa',
            'urutan' => 1,
            'is_aktif' => true,
        ]);
        \App\Support\SiteProfile::lupakan();

        $pengajuan = $this->pengajuan([
            'nik' => '1801012345678901',
            'nomor_surat' => '470/12/18.01.13.2019/XI/2026',
            'isian_surat' => [
                'agama' => 'Islam',
                'status_perkawinan' => 'Kawin',
                'pekerjaan' => 'Petani',
            ],
        ]);

        $surat = $pengajuan->suratTersusun();

        $this->assertStringContainsString('Faiz Ridho', $surat);
        $this->assertStringContainsString('1801012345678901', $surat);
        $this->assertStringContainsString('Islam', $surat);
        $this->assertStringContainsString('Kawin', $surat);
        $this->assertStringContainsString('Petani', $surat);
        $this->assertStringContainsString('yang berdomisili di dusun RT 02 Dusun I', $surat);
        $this->assertStringContainsString('Nomor : 470/12/18.01.13.2019/XI/2026', $surat);
        $this->assertStringContainsString('R O M S I, SH', $surat);
        $this->assertStringNotContainsString('Pejabat Lain', $surat);

        // Tidak ada penanda yang lolos ke surat jadi.
        $this->assertStringNotContainsString('{{', $surat);
        $this->assertStringNotContainsString('}}', $surat);
    }

    public function test_data_kosong_tidak_menyisakan_kalimat_janggal(): void
    {
        $pengajuan = $this->pengajuan();

        $surat = $pengajuan->suratTersusun();

        // Baris berlabel kosong diberi titik-titik seperti dokumen Word desa.
        $this->assertStringContainsString('NIK                 : ....................', $surat);
        // Kalimat domisili tetap utuh karena alamat pemohon wajib diisi.
        $this->assertStringContainsString('yang berdomisili di dusun RT 02 Dusun I', $surat);
        // Tidak ada sisa kalimat janggal seperti "di ." atau "dusun .".
        $this->assertDoesNotMatchRegularExpression('/\sdi\s*\./', $surat);
        $this->assertStringNotContainsString('dusun .', $surat);
    }

    public function test_isian_lama_tidak_bocor_ke_surat_baru(): void
    {
        // Pengajuan lama masih menyimpan isian template versi sistem
        // ({{lama_tinggal}}); isian itu tidak boleh muncul lagi di surat.
        $pengajuan = $this->pengajuan([
            'isian_surat' => ['lama_tinggal' => '5 tahun', 'agama' => 'Islam'],
        ]);

        $surat = $pengajuan->suratTersusun();

        $this->assertStringNotContainsString('5 tahun', $surat);
        $this->assertStringContainsString('Islam', $surat);
    }

    public function test_pratinjau_warga_menampilkan_kop_gambar_dan_kolom_isian(): void
    {
        $this->jenisDomisili();

        $profil = \App\Support\SiteProfile::current();
        $profil->forceFill([
            'nama_desa' => 'Desa Karang Sari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => 'Lampung Selatan',
            'alamat_kantor' => 'Jl. RA. Basyid Desa Karang Sari',
        ])->save();
        \App\Support\SiteProfile::lupakan();

        $this->get('/pengajuan?jenis=domisili')
            ->assertOk()
            // Kop tampil sebagai gambar resmi, bukan teks yang dirakit ulang.
            ->assertSee('class="letter-kop"', false)
            ->assertSee('src="/images/kop-surat.png"', false)
            ->assertDontSee('PEMERINTAH KABUPATEN LAMPUNG SELATAN', false)
            ->assertSee('SURAT KETERANGAN DOMISILI')
            ->assertSee('name="isian_surat[agama]"', false)
            ->assertSee('name="isian_surat[pekerjaan]"', false);
    }

    public function test_halaman_jenis_surat_menandai_template_belum_ada_dokumen(): void
    {
        $jenis = $this->jenisDomisili();

        $admin = User::create([
            'username' => 'sari',
            'name' => 'Sari',
            'email' => 'sari@example.test',
            'password' => bcrypt('rahasia'),
            'role' => RoleAdmin::Administrator,
            'is_active' => true,
        ]);

        // Domisili sudah punya dokumen resmi: tampil sebagai sumbernya.
        $this->actingAs($admin)
            ->get(route('admin.jenis-surat.edit', $jenis))
            ->assertOk()
            ->assertSee('SURAT KET. DOMISILI.docx');

        // Kategori lama yang tidak punya dokumen resmi (KTP): admin diberi tahu.
        $lain = JenisSurat::query()->create([
            'slug' => 'ktp',
            'kode' => 'SPKTP',
            'nama' => 'Surat Pengantar KTP',
            'template_surat' => 'Template bawaan.',
            'is_aktif' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.jenis-surat.edit', $lain))
            ->assertOk()
            ->assertSee('masih template bawaan sistem');
    }

    public function test_katalog_hanya_berisi_surat_yang_dokumennya_sudah_ada(): void
    {
        // Setiap entri katalog wajib menyebut berkas sumbernya, supaya jelas
        // template ini berasal dari dokumen desa, bukan karangan sistem.
        foreach (TemplateSuratDesa::katalog() as $slug => $data) {
            $this->assertNotEmpty($data['sumber'], "Slug {$slug} tidak menyebut berkas sumber.");
            $this->assertNotEmpty($data['template'], "Slug {$slug} tidak punya template.");
        }
    }
}
