<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Enums\StatusPengajuan;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Models\User;
use App\Services\VerifikasiService;
use App\Support\SiteProfile;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Alur status pengajuan harus tetap konsisten walau ada dua petugas yang
 * mengubahnya hampir bersamaan.
 */
class AlurStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([ProfilDesaSeeder::class, JenisSuratSeeder::class]);
        Storage::fake('local');
        Storage::fake('public');
        SiteProfile::lupakan();

        $this->admin = User::create([
            'name' => 'Admin Uji',
            'username' => 'adminuji',
            'password' => Hash::make('sandi-uji-test-1234'),
            'role' => RoleAdmin::Administrator,
            'is_active' => true,
        ]);
    }

    private function pengajuan(): Pengajuan
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();

        $p = new Pengajuan;
        $p->fill([
            'jenis_surat_id' => $jenis->id,
            'nama' => 'Budi',
            'nik' => '3201121508870003',
            'tempat_lahir' => 'Bogor',
            'tanggal_lahir' => '1987-08-15',
            'alamat' => 'Kp. Pasir',
            'keperluan' => 'Administrasi',
            'whatsapp' => '081234567890',
            'persetujuan_data' => true,
            'persetujuan_at' => now(),
        ]);
        $p->forceFill(['nomor_referensi' => 'KRS-2026-0042', 'kode_akses' => 'ABCD2345']);
        $p->isiStatus(StatusPengajuan::MenungguVerifikasi);
        $p->save();

        return $p;
    }

    /**
     * Dua petugas membuka halaman yang sama, lalu mengubah status hampir
     * bersamaan. Instance model yang dipegang petugas kedua masih membawa
     * status LAMA.
     *
     * Pemeriksaan transisi dulu memakai status basi itu, sehingga pengajuan
     * yang sudah Disetujui bisa mundur menjadi Perlu Perbaikan — transisi
     * yang tidak sah dari Disetujui — dan warga menerima notifikasi yang
     * membingungkan. Hal yang sama terjadi bila satu petugas menekan tombol
     * dua kali.
     */
    public function test_status_basi_tidak_bisa_dipakai_untuk_transisi_tidak_sah(): void
    {
        $p = $this->pengajuan();

        // Dua salinan terpisah, keduanya melihat Menunggu Verifikasi.
        $salinanA = Pengajuan::query()->findOrFail($p->id);
        $salinanB = Pengajuan::query()->findOrFail($p->id);

        $svc = app(VerifikasiService::class);

        $svc->ubahStatus($salinanA, StatusPengajuan::Disetujui, $this->admin);

        // Salinan B masih memegang status Menunggu Verifikasi, dari mana
        // "Perlu Perbaikan" memang sah. Dari Disetujui, transisi itu TIDAK sah.
        $ditolak = false;

        try {
            $svc->ubahStatus($salinanB, StatusPengajuan::PerluPerbaikan, $this->admin);
        } catch (\DomainException) {
            $ditolak = true;
        }

        $this->assertTrue($ditolak, 'Transisi memakai status basi: pengajuan disetujui lalu mundur ke perlu perbaikan.');
        $this->assertSame(StatusPengajuan::Disetujui, $p->refresh()->status);

        // Hanya satu baris riwayat admin yang tercatat (tidak ada perubahan
        // kedua yang setengah jadi).
        $this->assertSame(
            1,
            $p->riwayat()->where('aktor', 'admin')->count(),
            'Riwayat mencatat perubahan yang seharusnya ditolak.'
        );
    }

    /**
     * Penolakan karena status basi tidak boleh meninggalkan jejak apa pun.
     */
    public function test_penolakan_transisi_tidak_mengubah_data(): void
    {
        $p = $this->pengajuan();

        $p->forceFill(['status' => StatusPengajuan::Selesai->value])->save();

        // Salinan yang masih melihat status lama.
        $basi = new Pengajuan;
        $basi->setRawAttributes($p->getAttributes(), true);
        $basi->isiStatus(StatusPengajuan::MenungguVerifikasi);

        $this->expectException(\DomainException::class);

        try {
            app(VerifikasiService::class)->ubahStatus($basi, StatusPengajuan::Disetujui, $this->admin);
        } finally {
            $this->assertSame(StatusPengajuan::Selesai, $p->refresh()->status);
            $this->assertSame(0, $p->riwayat()->count());
        }
    }

    /**
     * Transisi yang sah tetap berjalan normal.
     */
    public function test_transisi_sah_tetap_berjalan(): void
    {
        $p = $this->pengajuan();
        $svc = app(VerifikasiService::class);

        $svc->ubahStatus($p, StatusPengajuan::Disetujui, $this->admin, 'Dokumen lengkap.');
        $this->assertSame(StatusPengajuan::Disetujui, $p->refresh()->status);
        $this->assertNotNull($p->diverifikasi_at);

        $svc->ubahStatus($p, StatusPengajuan::SedangDiproses, $this->admin);
        $svc->ubahStatus($p, StatusPengajuan::SiapDiambil, $this->admin);
        $svc->ubahStatus($p, StatusPengajuan::Selesai, $this->admin);

        $p->refresh();
        $this->assertSame(StatusPengajuan::Selesai, $p->status);
        $this->assertNotNull($p->selesai_at);
        $this->assertSame(4, $p->riwayat()->where('aktor', 'admin')->count());
    }
}
