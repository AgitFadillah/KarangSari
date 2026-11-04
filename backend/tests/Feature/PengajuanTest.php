<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuan;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
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

    /**
     * @return array<string, mixed>
     */
    protected function dataValid(array $override = []): array
    {
        return array_merge([
            'nama' => 'Budi Santoso',
            'nik' => '3201121508870003',
            'tempat_lahir' => 'Bogor',
            'tanggal_lahir' => '1987-08-15',
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Kp. Pasir Angin RT 02/RW 05',
            'keperluan' => 'Keterangan domisili untuk administrasi sekolah',
            'whatsapp' => '081234567890',
            'persetujuan' => '1',
            'dokumen' => [
                UploadedFile::fake()->image('ktp.jpg', 600, 400)->size(300),
                UploadedFile::fake()->create('kk.pdf', 400, 'application/pdf'),
            ],
        ], $override);
    }

    public function test_halaman_publik_dapat_dibuka(): void
    {
        foreach (['/', '/profil', '/informasi', '/potensi', '/kontak', '/pelayanan', '/kebijakan-privasi', '/pemberitahuan-data'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_detail_layanan_memakai_slug_dan_persyaratan_dari_db(): void
    {
        $this->get('/pelayanan/domisili')
            ->assertOk()
            ->assertSee('Surat Keterangan Domisili')
            ->assertSee('KTP pemohon')
            ->assertSee('Kartu Keluarga');
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

        $this->assertSame('SBM-'.now()->year.'-0001', $pengajuan->nomor_referensi);
        $this->assertSame(StatusPengajuan::MenungguVerifikasi, $pengajuan->status);
        $this->assertTrue($pengajuan->persetujuan_data);
        $this->assertCount(2, $pengajuan->dokumen);
        $this->assertCount(1, $pengajuan->riwayat);

        // Dokumen wajib di disk privat, bukan public.
        foreach ($pengajuan->dokumen as $dokumen) {
            Storage::disk('local')->assertExists($dokumen->path);
            Storage::disk('public')->assertMissing($dokumen->path);
        }

        $this->get(route('pengajuan.berhasil', $pengajuan))
            ->assertOk()
            ->assertSee($pengajuan->nomor_referensi)
            ->assertSee('Budi Santoso');
    }

    public function test_nomor_referensi_berurutan_per_tahun(): void
    {
        $this->post(route('pengajuan.store', 'domisili'), $this->dataValid());
        $this->post(route('pengajuan.store', 'domisili'), $this->dataValid());

        $this->assertSame(
            ['SBM-'.now()->year.'-0001', 'SBM-'.now()->year.'-0002'],
            Pengajuan::query()->orderBy('id')->pluck('nomor_referensi')->all()
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
        $data = $this->dataValid([
            'dokumen' => [
                UploadedFile::fake()->image('ktp.jpg')->size(3000),
                UploadedFile::fake()->create('kk.pdf', 400, 'application/pdf'),
            ],
        ]);

        $this->post(route('pengajuan.store', 'domisili'), $data)
            ->assertSessionHasErrors('dokumen.0');

        $this->assertDatabaseCount('pengajuans', 0);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_nik_tidak_valid_gagal(): void
    {
        $this->post(route('pengajuan.store', 'domisili'), $this->dataValid(['nik' => '1234567890123456']))
            ->assertSessionHasErrors('nik');

        $this->assertDatabaseCount('pengajuans', 0);
    }

    public function test_jumlah_dokumen_harus_sesuai_persyaratan(): void
    {
        $data = $this->dataValid([
            'dokumen' => [UploadedFile::fake()->image('ktp.jpg')->size(300)],
        ]);

        $this->post(route('pengajuan.store', 'domisili'), $data)
            ->assertSessionHasErrors('dokumen');
    }

    public function test_format_file_tidak_diizinkan_gagal(): void
    {
        $data = $this->dataValid([
            'dokumen' => [
                UploadedFile::fake()->create('ktp.exe', 10, 'application/octet-stream'),
                UploadedFile::fake()->create('kk.pdf', 400, 'application/pdf'),
            ],
        ]);

        $this->post(route('pengajuan.store', 'domisili'), $data)
            ->assertSessionHasErrors('dokumen.0');
    }

    public function test_halaman_berhasil_tanpa_nomor_referensi_404(): void
    {
        $this->post(route('pengajuan.store', 'domisili'), $this->dataValid());
        $pengajuan = Pengajuan::query()->firstOrFail();

        // Sesi baru tanpa nomor referensi tidak boleh melihat data warga.
        $this->flushSession();

        $this->get(route('pengajuan.berhasil', $pengajuan))->assertNotFound();
        $this->get(route('pengajuan.berhasil', ['pengajuan' => $pengajuan, 'ref' => $pengajuan->nomor_referensi]))->assertOk();
    }

    public function test_form_pengajuan_memuat_persyaratan_sesuai_jenis(): void
    {
        $this->get('/pengajuan?jenis=usaha')
            ->assertOk()
            ->assertSee('Surat Keterangan Usaha')
            ->assertSee('Foto tempat usaha');
    }
}
