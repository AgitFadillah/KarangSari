<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Enums\StatusPengajuan;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Models\PerangkatDesa;
use App\Models\User;
use App\Support\SiteProfile;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Berkas unggahan: nama berkas dan tipe MIME berasal dari pengunggah, lalu
 * dipakai kembali pada header HTTP dan/atau disimpan ke disk publik.
 * Nilai mentahnya berbahaya dan harus dibersihkan.
 */
class BerkasUnggahanTest extends TestCase
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

    /**
     * Berkas PNG sah yang NAMANYA dibuat berbahaya oleh pengunggah.
     *
     * Aturan validasi "image"/"mimes" memeriksa isi berkas, bukan namanya,
     * jadi permintaan seperti ini memang lolos validasi.
     */
    private function berkasBernama(string $nama): UploadedFile
    {
        $png = UploadedFile::fake()->image('asli.png', 40, 40);
        $salinan = tempnam(sys_get_temp_dir(), 'uji').'.png';
        copy($png->getRealPath(), $salinan);

        return new UploadedFile($salinan, $nama, 'image/png', null, true);
    }

    private function unggahFoto(string $nama): ?string
    {
        $this->actingAs($this->admin)->post(route('admin.perangkat.store'), [
            'nama' => 'Uji '.md5($nama),
            'jabatan' => 'Staf',
            'urutan' => 1,
            'is_aktif' => '1',
            'foto' => $this->berkasBernama($nama),
        ]);

        return PerangkatDesa::query()->where('nama', 'Uji '.md5($nama))->value('foto_path');
    }

    /**
     * Foto perangkat disimpan di disk "public" yang dilayani langsung oleh
     * web server. Bila ekstensi mengikuti nama kiriman, berkas PNG yang
     * dinamai "foto.html" tersimpan sebagai "uuid.html" dan menjadi halaman
     * HTML di domain situs desa — stored XSS terhadap sesi admin.
     */
    public function test_foto_perangkat_tidak_disimpan_dengan_ekstensi_berbahaya(): void
    {
        foreach (['evil.html', 'evil.svg', 'evil.php', 'evil.phtml', 'evil.xhtml'] as $nama) {
            $path = $this->unggahFoto($nama);

            // Dua-duanya dapat diterima: berkas ditolak validasi, atau
            // tersimpan dengan ekstensi gambar yang aman. Yang TIDAK boleh
            // adalah tersimpan memakai ekstensi kiriman (.html/.svg/.php).
            if ($path === null) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/\.(png|jpg|jpeg|webp|gif)$/',
                $path,
                'Ekstensi berkas tersimpan mengikuti nama kiriman ('.$nama.') -> '.$path
            );
        }
    }

    public function test_foto_perangkat_normal_tetap_tersimpan(): void
    {
        $path = $this->unggahFoto('foto-resmi.png');

        $this->assertNotNull($path);
        $this->assertStringEndsWith('.png', $path);
        Storage::disk('public')->assertExists($path);
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
        $p->nomor_referensi = 'KRS-2026-0001';
        $p->isiStatus(StatusPengajuan::MenungguVerifikasi);
        $p->save();

        return $p;
    }

    /**
     * Nama berkas kiriman warga dipakai sebagai nama unduhan pada header
     * Content-Disposition. Garis miring di dalamnya membuat Symfony melempar
     * InvalidArgumentException, sehingga petugas hanya melihat HTTP 500 dan
     * tidak bisa membuka dokumen warga sama sekali.
     */
    public function test_nama_berkas_warga_berisi_garis_miring_tidak_menyebabkan_500(): void
    {
        $p = $this->pengajuan();
        Storage::disk('local')->put('dokumen/2026/x/ktp.pdf', '%PDF-1.4 uji');

        $dok = $p->dokumen()->create([
            'label' => 'Fotokopi KTP',
            'path' => 'dokumen/2026/x/ktp.pdf',
            'disk' => 'local',
            'nama_asli' => 'ktp/../rahasia.pdf',
            'mime' => 'application/pdf',
            'ukuran' => 100,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.pengajuan.dokumen', [$p, $dok]))
            ->assertOk();
    }

    /**
     * CR/LF pada nama berkas atau MIME menyuntik header HTTP baru ke respons
     * milik domain desa (header injection).
     */
    public function test_nama_berkas_dan_mime_warga_tidak_menyuntik_header(): void
    {
        $p = $this->pengajuan();
        Storage::disk('local')->put('dokumen/2026/x/ktp.pdf', '%PDF-1.4 uji');

        $dok = $p->dokumen()->create([
            'label' => 'Fotokopi KTP',
            'path' => 'dokumen/2026/x/ktp.pdf',
            'disk' => 'local',
            'nama_asli' => "ktp.pdf\r\nX-Injected: yes",
            'mime' => "application/pdf\r\nX-Injected2: yes",
            'ukuran' => 100,
        ]);

        $res = $this->actingAs($this->admin)
            ->get(route('admin.pengajuan.dokumen', [$p, $dok]));

        $res->assertOk();

        $this->assertArrayNotHasKey('x-injected', $res->headers->all());
        $this->assertArrayNotHasKey('x-injected2', $res->headers->all());

        $disposisi = (string) $res->headers->get('content-disposition');
        $this->assertStringNotContainsString("\r", $disposisi);
        $this->assertStringNotContainsString("\n", $disposisi);
    }

    /**
     * Berkas warga yang dilaporkan sebagai HTML tidak boleh dirender browser
     * di origin situs desa (stored XSS terhadap sesi admin).
     */
    public function test_mime_html_dari_warga_dinonaktifkan(): void
    {
        $p = $this->pengajuan();
        Storage::disk('local')->put('dokumen/2026/x/ktp.pdf', '<script>alert(1)</script>');

        $dok = $p->dokumen()->create([
            'label' => 'Fotokopi KTP',
            'path' => 'dokumen/2026/x/ktp.pdf',
            'disk' => 'local',
            'nama_asli' => 'ktp.pdf',
            'mime' => 'text/html',
            'ukuran' => 100,
        ]);

        $res = $this->actingAs($this->admin)
            ->get(route('admin.pengajuan.dokumen', [$p, $dok]));

        $res->assertOk();
        $this->assertSame('application/octet-stream', $res->headers->get('content-type'));
        $this->assertStringStartsWith('attachment', (string) $res->headers->get('content-disposition'));
    }
}
