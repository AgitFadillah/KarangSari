<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuan;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Services\TemplateSuratService;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengajuanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProfilDesaSeeder::class, JenisSuratSeeder::class]);
        Storage::fake('local');
    }

    /** Nomor referensi sesuai konfigurasi desa, bukan teks yang ditulis di test. */
    protected function nomorReferensi(int $urut): string
    {
        return sprintf('%s-%d-%04d', config('desa.prefix_referensi'), now()->year, $urut);
    }

    /** Isian template wajib untuk jenis surat tertentu, mengikuti penandanya. */
    protected function isianUntuk(string $slug): array
    {
        $jenis = JenisSurat::query()->where('slug', $slug)->firstOrFail();
        $isian = [];

        foreach (app(TemplateSuratService::class)->penandaKustom($jenis) as $p) {
            $isian[$p['kunci']] = match ($p['tipe'] ?? 'text') {
                'date' => '2026-01-15',
                'number' => '5',
                default => 'Isi '.$p['label'],
            };
        }

        // Nama/jenis usaha tidak boleh tertukar dengan label panjang.
        if (array_key_exists('nama_usaha', $isian)) {
            $isian['nama_usaha'] = 'Warung Kelontong Bu Siti';
        }

        return $isian;
    }

    /**
     * @return array<string, mixed>
     */
    protected function dataValid(array $override = []): array
    {
        return array_merge([
            'nama' => 'Budi Santoso',
            'nik' => '1801010101010001',
            'tempat_lahir' => 'Lampung Selatan',
            'tanggal_lahir' => '1987-08-15',
            'alamat' => 'Kp. Pasir Angin RT 02/RW 05',
            'keperluan' => 'Keterangan domisili untuk administrasi sekolah',
            'whatsapp' => '081234567890',
            'persetujuan' => '1',
            'isian_surat' => $this->isianUntuk('domisili'),
        ], $override);
    }

    public function test_halaman_publik_dapat_dibuka(): void
    {
        foreach (['/', '/profil', '/informasi', '/potensi', '/kontak', '/pelayanan', '/panduan', '/kebijakan-privasi', '/pemberitahuan-data'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    /**
     * Sitemap hanya memuat halaman publik dan layanan yang aktif, supaya
     * layanan yang dinonaktifkan admin tidak bocor ke mesin pencari.
     */
    public function test_sitemap_memuat_halaman_publik_dan_layanan_aktif(): void
    {
        $respon = $this->get('/sitemap.xml');

        $respon->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $isi = $respon->getContent();

        $this->assertStringContainsString('<urlset', $isi);
        $this->assertStringContainsString('/pelayanan', $isi);
        $this->assertStringContainsString('/pelayanan/domisili', $isi);

        // Area berdata pribadi tidak boleh masuk sitemap.
        $this->assertStringNotContainsString('/admin', $isi);
        $this->assertStringNotContainsString('/pengajuan', $isi);

        // Setelah dinonaktifkan, layanan hilang dari sitemap.
        JenisSurat::query()->where('slug', 'domisili')->update(['is_aktif' => false]);

        $this->assertStringNotContainsString('/pelayanan/domisili', $this->get('/sitemap.xml')->getContent());
    }

    public function test_robots_txt_melarang_area_pribadi_dan_menunjuk_sitemap(): void
    {
        $isi = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Disallow: /admin', $isi);
        $this->assertStringContainsString('Disallow: /pengajuan', $isi);
        $this->assertStringContainsString('Disallow: /cek-status', $isi);
        $this->assertStringContainsString('Sitemap:', $isi);
    }

    public function test_detail_layanan_memakai_slug_dan_persyaratan_dari_db(): void
    {
        // KTP & KK tidak lagi menjadi persyaratan bawaan; yang tampil adalah
        // persyaratan tambahan yang memang dikonfigurasi desa.
        $this->get('/pelayanan/domisili')
            ->assertOk()
            ->assertSee('Surat Keterangan Domisili')
            ->assertSee('Surat pengantar RT/RW')
            ->assertDontSee('KTP pemohon')
            ->assertDontSee('Kartu Keluarga');
    }

    public function test_jenis_surat_nonaktif_tidak_bisa_diakses(): void
    {
        JenisSurat::query()->where('slug', 'domisili')->update(['is_aktif' => false]);

        $this->get('/pelayanan/domisili')->assertNotFound();
    }

    public function test_submit_valid_membuat_pengajuan_nomor_referensi_dan_riwayat(): void
    {
        $response = $this->post(route('pengajuan.store', 'domisili'), $this->dataValid());

        $pengajuan = Pengajuan::query()->firstOrFail();

        $response->assertRedirect(route('pengajuan.berhasil', $pengajuan));

        $this->assertSame($this->nomorReferensi(1), $pengajuan->nomor_referensi);
        $this->assertSame(StatusPengajuan::MenungguVerifikasi, $pengajuan->status);
        $this->assertTrue($pengajuan->persetujuan_data);
        $this->assertCount(1, $pengajuan->riwayat);
        $this->assertNotEmpty($pengajuan->isian_surat);

        $this->get(route('pengajuan.berhasil', $pengajuan))
            ->assertOk()
            ->assertSee($pengajuan->nomor_referensi)
            ->assertSee('Budi Santoso');
    }

    public function test_submit_tanpa_nik_tempat_dan_tanggal_lahir_ditolak(): void
    {
        // Semua dokumen resmi desa mencetak NIK dan tempat/tanggal lahir,
        // sehingga ketiganya wajib diisi sejak awal.
        $data = $this->dataValid();
        unset($data['nik'], $data['tempat_lahir'], $data['tanggal_lahir']);

        $this->post(route('pengajuan.store', 'domisili'), $data)
            ->assertSessionHasErrors(['nik', 'tempat_lahir', 'tanggal_lahir']);

        $this->assertDatabaseCount('pengajuans', 0);
    }

    public function test_identitas_tersimpan_pada_pengajuan(): void
    {
        $this->post(route('pengajuan.store', 'domisili'), $this->dataValid())
            ->assertRedirect();

        $pengajuan = Pengajuan::query()->firstOrFail();

        $this->assertSame('1801010101010001', $pengajuan->nik);
        $this->assertSame('Lampung Selatan', $pengajuan->tempat_lahir);
        $this->assertSame('1987-08-15', $pengajuan->tanggal_lahir?->toDateString());
    }

    public function test_isian_surat_wajib_diisi(): void
    {
        $data = $this->dataValid();
        unset($data['isian_surat']);

        $this->post(route('pengajuan.store', 'domisili'), $data)
            ->assertSessionHasErrors('isian_surat');

        $this->assertDatabaseCount('pengajuans', 0);
    }

    public function test_nomor_referensi_berurutan_per_tahun(): void
    {
        $this->post(route('pengajuan.store', 'domisili'), $this->dataValid());
        $this->post(route('pengajuan.store', 'domisili'), $this->dataValid());

        $this->assertSame(
            [$this->nomorReferensi(1), $this->nomorReferensi(2)],
            Pengajuan::query()->orderBy('id')->pluck('nomor_referensi')->all()
        );
    }

    public function test_nomor_referensi_tetap_berurutan_setelah_9999(): void
    {
        $tahun = now()->year;
        $prefix = config('desa.prefix_referensi');

        // Simulasikan pengajuan ke-9999 sudah ada; nomor berikutnya harus
        // 10000 - tidak boleh memotong digit atau mengulang nomor lama.
        $lama = new Pengajuan;
        $lama->forceFill([
            'jenis_surat_id' => JenisSurat::query()->where('slug', 'domisili')->firstOrFail()->id,
            'nomor_referensi' => "{$prefix}-{$tahun}-9999",
            'nama' => 'Penanda',
            'alamat' => 'Kp. Uji',
            'keperluan' => 'Uji nomor',
            'whatsapp' => '081234567890',
            'status' => StatusPengajuan::Selesai->value,
        ])->save();

        $this->assertSame(
            "{$prefix}-{$tahun}-10000",
            app(\App\Services\PengajuanService::class)->nomorBerikutnya()
        );
    }

    public function test_submit_tanpa_persetujuan_gagal(): void
    {
        $data = $this->dataValid();
        unset($data['persetujuan']);

        $this->post(route('pengajuan.store', 'domisili'), $data)
            ->assertSessionHasErrors('persetujuan');

        $this->assertDatabaseCount('pengajuans', 0);
    }

    public function test_file_lebih_dari_2mb_gagal(): void
    {
        $syarat = JenisSurat::query()->where('slug', 'domisili')->firstOrFail()
            ->persyaratan()->firstOrFail();

        $data = $this->dataValid([
            'dokumen' => [
                $syarat->id => UploadedFile::fake()->image('surat-rt.jpg')->size(3000),
            ],
        ]);

        $this->post(route('pengajuan.store', 'domisili'), $data)
            ->assertSessionHasErrors('dokumen.'.$syarat->id);

        $this->assertDatabaseCount('pengajuans', 0);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_format_file_tidak_diizinkan_gagal(): void
    {
        $syarat = JenisSurat::query()->where('slug', 'domisili')->firstOrFail()
            ->persyaratan()->firstOrFail();

        $data = $this->dataValid([
            'dokumen' => [
                $syarat->id => UploadedFile::fake()->create('surat.exe', 10, 'application/octet-stream'),
            ],
        ]);

        $this->post(route('pengajuan.store', 'domisili'), $data)
            ->assertSessionHasErrors('dokumen.'.$syarat->id);
    }

    /**
     * Format & ukuran mengikuti konfigurasi admin per persyaratan, bukan
     * batas keras 2 MB / JPG-PNG-PDF seperti sebelumnya.
     */
    public function test_format_dan_ukuran_mengikuti_konfigurasi_persyaratan(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();
        $syarat = $jenis->persyaratan()->firstOrFail();

        // Admin membatasi hanya PDF, maksimal 512 KB.
        $syarat->update(['format' => 'pdf', 'maks_kb' => 512]);

        // JPG kini ditolak walaupun di bawah batas ukuran.
        $this->post(route('pengajuan.store', 'domisili'), $this->dataValid([
            'dokumen' => [$syarat->id => UploadedFile::fake()->image('surat.jpg')->size(100)],
        ]))->assertSessionHasErrors('dokumen.'.$syarat->id);

        // PDF 600 KB ditolak karena melebihi 512 KB.
        $this->post(route('pengajuan.store', 'domisili'), $this->dataValid([
            'dokumen' => [$syarat->id => UploadedFile::fake()->create('surat.pdf', 600, 'application/pdf')],
        ]))->assertSessionHasErrors('dokumen.'.$syarat->id);

        // PDF 100 KB diterima.
        $this->post(route('pengajuan.store', 'domisili'), $this->dataValid([
            'dokumen' => [$syarat->id => UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf')],
        ]))->assertRedirect();

        $this->assertSame(1, Pengajuan::query()->count());
        $this->assertSame(1, Pengajuan::query()->firstOrFail()->dokumen()->count());
    }

    public function test_persyaratan_yang_ditandai_wajib_harus_diunggah(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();
        $syarat = $jenis->persyaratan()->firstOrFail();
        $syarat->update(['wajib' => true]);

        // Tanpa berkas -> ditolak.
        $this->post(route('pengajuan.store', 'domisili'), $this->dataValid())
            ->assertSessionHasErrors('dokumen.'.$syarat->id);

        $this->assertDatabaseCount('pengajuans', 0);
    }

    public function test_form_pengajuan_menampilkan_kolom_unggah_dokumen(): void
    {
        $syarat = JenisSurat::query()->where('slug', 'domisili')->firstOrFail()
            ->persyaratan()->firstOrFail();

        $this->get('/pengajuan?jenis=domisili')
            ->assertOk()
            ->assertSee('Dokumen persyaratan')
            ->assertSee('name="dokumen['.$syarat->id.']"', false)
            ->assertSee($syarat->label);
    }

    public function test_halaman_berhasil_tanpa_nomor_referensi_404(): void
    {
        $this->post(route('pengajuan.store', 'domisili'), $this->dataValid());
        $pengajuan = Pengajuan::query()->firstOrFail();

        // Sesi baru tanpa nomor referensi tidak boleh melihat data warga.
        $this->flushSession();

        $this->get(route('pengajuan.berhasil', $pengajuan))->assertNotFound();

        // Nomor referensi lama (ref=...) tidak lagi menjadi kunci akses.
        $this->get(route('pengajuan.berhasil', ['pengajuan' => $pengajuan, 'ref' => $pengajuan->nomor_referensi]))
            ->assertNotFound();

        // Kode akses acak yang benar membuka halaman konfirmasi.
        $this->get(route('pengajuan.berhasil', ['pengajuan' => $pengajuan, 'kode' => $pengajuan->kode_akses]))
            ->assertOk();
    }

    public function test_form_pengajuan_memuat_isian_template_sesuai_jenis(): void
    {
        $this->get('/pengajuan?jenis=usaha')
            ->assertOk()
            ->assertSee('Surat Keterangan Usaha')
            ->assertSee('Nama/jenis usaha')
            ->assertSee('name="isian_surat[nama_usaha]"', false);
    }

    /**
     * Tujuh kategori resmi harus bisa diajukan dari formulir warga dengan
     * kolom isian masing-masing, dan suratnya langsung lengkap.
     */
    public function test_tujuh_kategori_resmi_dapat_diajukan_dan_suratnya_lengkap(): void
    {
        $contoh = [
            'domisili' => ['agama' => 'Islam', 'status_perkawinan' => 'Kawin', 'pekerjaan' => 'Petani'],
            'usaha' => ['agama' => 'Islam', 'pekerjaan' => 'Wiraswasta', 'nama_usaha' => 'Warung Bu Siti', 'alamat_usaha' => 'Sukamaju RT 03/RW 05'],
            'tidak-mampu' => ['agama' => 'Islam', 'status_perkawinan' => 'Kawin', 'pekerjaan' => 'Buruh Tani'],
            'kematian' => ['agama' => 'Islam', 'status_perkawinan' => 'Kawin', 'pekerjaan' => 'Wiraswasta', 'tanggal_meninggal' => '2026-10-02', 'tempat_makam' => 'TPU Desa Karang Sari'],
            'belum-menikah' => ['agama' => 'Islam', 'status_perkawinan' => 'Belum Menikah', 'pekerjaan' => 'Karyawan Swasta'],
            'orang-tua-asuh' => [
                'agama' => 'Islam', 'pekerjaan' => 'Petani',
                'nama_anak' => 'Salsabila Putri', 'nik_anak' => '3201120000000106',
                'tempat_lahir_anak' => 'Lampung Selatan', 'tanggal_lahir_anak' => '2012-03-17',
                'agama_anak' => 'Islam', 'pekerjaan_anak' => 'Pelajar/Mahasiswa',
                'alamat_anak' => 'Pasir Angin RT 02/RW 05', 'sejak' => '2018',
            ],
            'pekerjaan' => ['agama' => 'Islam', 'status_perkawinan' => 'Kawin', 'pekerjaan' => 'Buruh Harian Lepas', 'pekerjaan_sebenarnya' => 'Tukang Bangunan'],
        ];

        // Throttle pengajuan 5x/10 menit adalah pengaman produksi; test ini
        // memeriksa ketujuh kategori sekaligus sehingga middleware itu dilepas.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        foreach ($contoh as $slug => $isian) {
            $this->post(route('pengajuan.store', $slug), $this->dataValid([
                'isian_surat' => $isian,
            ]))->assertRedirect();

            $pengajuan = Pengajuan::query()->whereHas(
                'jenisSurat',
                fn ($q) => $q->where('slug', $slug)
            )->latest('id')->firstOrFail();

            $teks = $pengajuan->suratResmiTeks();

            $this->assertStringContainsString('R O M S I, SH', $teks, "Nama kepala desa tidak tercetak pada {$slug}.");
            $this->assertStringContainsString('Budi Santoso', $teks, "Nama pemohon tidak tercetak pada {$slug}.");
            $this->assertStringContainsString('1801010101010001', $teks, "NIK pemohon tidak tercetak pada {$slug}.");
            $this->assertStringNotContainsString('{{', $teks, "Penanda template bocor pada {$slug}.");
            $this->assertStringNotContainsString('}}', $teks, "Penanda template bocor pada {$slug}.");

            // Tanggal surat = tanggal pengajuan (hari warga mengisi formulir).
            $this->assertStringContainsString(
                now()->locale('id')->translatedFormat('j F Y'),
                $teks,
                "Tanggal pengajuan tidak tercetak pada {$slug}."
            );
        }

        $this->assertSame(7, Pengajuan::query()->count());
    }

    /**
     * Kolom isian bertipe tanggal/angka divalidasi sesuai tipenya, bukan
     * sekadar "harus teks".
     */
    public function test_isian_bertipe_tanggal_dan_angka_divalidasi(): void
    {
        $kematian = [
            'agama' => 'Islam',
            'status_perkawinan' => 'Kawin',
            'pekerjaan' => 'Wiraswasta',
            'tanggal_meninggal' => 'bukan tanggal',
            'tempat_makam' => 'TPU Desa Karang Sari',
        ];

        $this->post(route('pengajuan.store', 'kematian'), $this->dataValid([
            'isian_surat' => $kematian,
        ]))->assertSessionHasErrors('isian_surat.tanggal_meninggal');

        $this->assertDatabaseCount('pengajuans', 0);
    }

    public function test_revisi_memakai_nomor_referensi_yang_sama(): void
    {
        $this->post(route('pengajuan.store', 'domisili'), $this->dataValid());

        $pengajuan = Pengajuan::query()->firstOrFail();

        // Admin meminta perbaikan.
        $pengajuan->forceFill([
            'status' => StatusPengajuan::PerluPerbaikan->value,
            'catatan_admin' => 'Nama usaha perlu diperbaiki.',
        ])->save();

        // Halaman revisi terbuka.
        $this->get(route('pengajuan.revisi', $pengajuan->nomor_referensi))->assertOk();

        $isian = $this->isianUntuk('domisili');
        $isian = array_map(fn ($v) => $v.' (revisi)', $isian);

        $this->post(route('pengajuan.revisi.simpan', $pengajuan->nomor_referensi), $this->dataValid([
            'isian_surat' => $isian,
        ]))->assertRedirect();

        $pengajuan->refresh();

        // Nomor referensi tidak berubah, status kembali menunggu verifikasi,
        // dan penghitung revisi bertambah.
        $this->assertSame($this->nomorReferensi(1), $pengajuan->nomor_referensi);
        $this->assertSame(StatusPengajuan::MenungguVerifikasi, $pengajuan->status);
        $this->assertSame(1, (int) $pengajuan->revisi_ke);
        $this->assertDatabaseCount('pengajuans', 1);
    }

    public function test_revisi_ditolak_bila_status_belum_perlu_perbaikan(): void
    {
        $this->post(route('pengajuan.store', 'domisili'), $this->dataValid());

        $pengajuan = Pengajuan::query()->firstOrFail();

        // Masih Menunggu Verifikasi: halaman revisi tidak boleh terbuka.
        $this->get(route('pengajuan.revisi', $pengajuan->nomor_referensi))->assertNotFound();
    }
}
