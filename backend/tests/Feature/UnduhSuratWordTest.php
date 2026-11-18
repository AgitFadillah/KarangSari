<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Enums\StatusPengajuan;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Models\User;
use App\Support\SiteProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unduh surat jadi (.docx) lewat HTTP, baik oleh petugas desa maupun warga.
 */
class UnduhSuratWordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SiteProfile::lupakan();
    }

    protected function admin(): User
    {
        return User::firstOrCreate(
            ['username' => 'sari'],
            [
                'name' => 'Sari',
                'email' => 'sari@example.test',
                'password' => bcrypt('rahasia'),
                'role' => RoleAdmin::Administrator,
                'is_active' => true,
            ]
        );
    }

    protected function pengajuan(StatusPengajuan $status = StatusPengajuan::Disetujui): Pengajuan
    {
        $jenis = JenisSurat::query()->firstOrCreate(
            ['slug' => 'domisili'],
            [
                'kode' => 'SKD',
                'nama' => 'Surat Keterangan Domisili',
                'kode_layanan' => '470',
                'estimasi_hari' => '1 hari kerja',
                'deskripsi' => 'Keterangan tempat tinggal.',
                'template_surat' => 'Menerangkan bahwa {{nama}}.',
                'is_aktif' => true,
            ]
        );

        $pengajuan = new Pengajuan;
        $pengajuan->forceFill([
            'jenis_surat_id' => $jenis->id,
            'nomor_referensi' => 'KRS-2026-0001',
            'kode_akses' => 'UNDUH234',
            'nama' => 'Faiz Ridho',
            'alamat' => 'Karang Sari',
            'keperluan' => 'Perpindahan Domisili',
            'whatsapp' => '08123456789',
            'isian_surat' => ['agama' => 'Islam', 'dusun' => 'Sukamaju'],
            'persetujuan_data' => true,
            'persetujuan_at' => now(),
        ]);
        $pengajuan->isiStatus($status);
        $pengajuan->save();

        return $pengajuan->refresh();
    }

    public function test_admin_dapat_mengunduh_surat_word(): void
    {
        $pengajuan = $this->pengajuan();

        $respons = $this->actingAs($this->admin())
            ->get(route('admin.pengajuan.surat-word', $pengajuan));

        $respons->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            $respons->headers->get('Content-Type')
        );
        $this->assertStringContainsString('.docx', (string) $respons->headers->get('Content-Disposition'));
    }

    public function test_berkas_unduhan_adalah_docx_yang_sah(): void
    {
        $pengajuan = $this->pengajuan();

        $respons = $this->actingAs($this->admin())
            ->get(route('admin.pengajuan.surat-word', $pengajuan));

        $isi = $respons->streamedContent();

        $this->assertStringStartsWith('PK', $isi, 'Berkas .docx harus berupa paket ZIP.');

        $tmp = tempnam(sys_get_temp_dir(), 'uji').'.docx';
        file_put_contents($tmp, $isi);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($tmp) === true);
        $this->assertNotFalse($zip->getFromName('word/document.xml'));
        $zip->close();
        @unlink($tmp);
    }

    public function test_tamu_tidak_dapat_mengunduh_surat_word(): void
    {
        $this->get(route('admin.pengajuan.surat-word', $this->pengajuan()))
            ->assertRedirect(route('admin.login'));
    }

    public function test_warga_dapat_mengunduh_surat_yang_sudah_disetujui(): void
    {
        $pengajuan = $this->pengajuan(StatusPengajuan::Disetujui);

        $respons = $this->get(route('pengajuan.surat', [
            'nomorReferensi' => $pengajuan->nomor_referensi,
            'kode' => $pengajuan->kode_akses,
        ]));

        $respons->assertOk();
        $this->assertStringStartsWith('PK', $respons->streamedContent());
    }

    public function test_warga_tidak_dapat_mengunduh_tanpa_kode_akses(): void
    {
        $pengajuan = $this->pengajuan(StatusPengajuan::Disetujui);

        // Nomor referensi berurutan; tanpa kode akses harus 404.
        $this->get(route('pengajuan.surat', $pengajuan->nomor_referensi))->assertNotFound();

        $this->get(route('pengajuan.surat', [
            'nomorReferensi' => $pengajuan->nomor_referensi,
            'kode' => 'SALAH123',
        ]))->assertNotFound();
    }

    public function test_warga_belum_dapat_mengunduh_surat_yang_belum_disetujui(): void
    {
        // Surat yang masih menunggu verifikasi belum sah untuk diunduh.
        $pengajuan = $this->pengajuan(StatusPengajuan::MenungguVerifikasi);

        $this->get(route('pengajuan.surat', $pengajuan->nomor_referensi))->assertNotFound();
    }

    public function test_nomor_referensi_tidak_dikenal_menghasilkan_404(): void
    {
        $this->get(route('pengajuan.surat', 'KRS-2026-9999'))->assertNotFound();
    }

    public function test_tombol_unduh_word_tampil_di_detail_admin(): void
    {
        $pengajuan = $this->pengajuan();

        $this->actingAs($this->admin())
            ->get(route('admin.pengajuan.show', $pengajuan))
            ->assertOk()
            ->assertSee('Unduh Word', false)
            ->assertSee(route('admin.pengajuan.surat-word', $pengajuan), false);
    }

    public function test_unduhan_dicatat_pada_log_aktivitas(): void
    {
        $pengajuan = $this->pengajuan();

        $this->actingAs($this->admin())
            ->get(route('admin.pengajuan.surat-word', $pengajuan));

        $this->assertDatabaseHas('log_aktivitets', [
            'tindakan' => 'Mengunduh surat jadi (Word)',
            'subjek' => $pengajuan->nomor_referensi,
        ]);
    }
}
