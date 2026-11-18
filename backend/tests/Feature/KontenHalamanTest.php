<?php

namespace Tests\Feature;

use App\Models\KontenWebsite;
use App\Support\BatasPermintaan;
use App\Support\SiteProfile;
use Database\Seeders\DataDesaSeeder;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\KontenWebsiteSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Menu "Konten Website" di dashboard admin harus benar-benar mengubah judul
 * dan pengantar halaman publik.
 *
 * Sebelumnya lima bagian konten dapat diedit dan disimpan tanpa galat, tetapi
 * tidak satu pun halaman publik membacanya — semua judul ditulis mati di
 * dalam Blade. Perangkat desa menyimpan perubahan lalu tidak melihat efek
 * apa pun, yang tampak seperti fitur rusak.
 */
class KontenHalamanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            ProfilDesaSeeder::class,
            DataDesaSeeder::class,
            JenisSuratSeeder::class,
            KontenWebsiteSeeder::class,
        ]);

        Storage::fake('public');
        SiteProfile::lupakan();
        BatasPermintaan::bersihkanSemua();
    }

    public function test_judul_dan_pengantar_beranda_mengikuti_konten_admin(): void
    {
        $this->ubah('beranda', 'Desa Karang Sari Melayani Sepenuh Hati', 'Pelayanan surat kini cukup dari rumah.');

        $this->get('/')
            ->assertOk()
            ->assertSee('Desa Karang Sari Melayani Sepenuh Hati')
            ->assertSee('Pelayanan surat kini cukup dari rumah.');
    }

    public function test_judul_dan_pengantar_setiap_halaman_publik_mengikuti_konten_admin(): void
    {
        $this->ubah('profil', 'Profil Resmi Karang Sari', 'Sejarah desa yang lengkap.');
        $this->ubah('pelayanan', 'Layanan Surat Cepat', 'Pilih layanan lalu isi formulir.');
        $this->ubah('informasi', 'Kabar Terbaru Desa', 'Berita dan agenda terbaru.');
        $this->ubah('kontak', 'Hubungi Kantor Desa', 'Kami siap membantu warga.');

        $this->get('/profil')->assertOk()
            ->assertSee('Profil Resmi Karang Sari')
            ->assertSee('Sejarah desa yang lengkap.');

        $this->get('/pelayanan')->assertOk()
            ->assertSee('Layanan Surat Cepat')
            ->assertSee('Pilih layanan lalu isi formulir.');

        $this->get('/informasi')->assertOk()
            ->assertSee('Kabar Terbaru Desa')
            ->assertSee('Berita dan agenda terbaru.');

        $this->get('/kontak')->assertOk()
            ->assertSee('Hubungi Kantor Desa')
            ->assertSee('Kami siap membantu warga.');
    }

    public function test_konten_draf_tidak_tampil_dan_judul_bawaan_dipakai(): void
    {
        // Konten "informasi" dari seeder berstatus draf.
        $this->assertSame(
            KontenWebsite::DRAF,
            KontenWebsite::query()->where('bagian', 'informasi')->value('status')
        );

        $this->get('/informasi')
            ->assertOk()
            ->assertSee('Kabar, pengumuman, dan kegiatan warga.')
            ->assertDontSee('Informasi terbaru desa');
    }

    public function test_konten_kosong_jatuh_ke_judul_bawaan_bukan_judul_kosong(): void
    {
        KontenWebsite::query()->where('bagian', 'beranda')->update([
            'judul' => '',
            'isi' => '',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Layanan desa, kini bisa dimulai dari rumah.');
    }

    public function test_isi_konten_di_escape_sehingga_tag_html_tidak_dijalankan(): void
    {
        $this->ubah('beranda', 'Judul <script>alert(1)</script>', '<img src=x onerror=alert(1)>Halo');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('onerror=alert(1)', $html);
        $this->assertStringContainsString('Halo', $html);
    }

    private function ubah(string $bagian, string $judul, string $isi): void
    {
        KontenWebsite::query()->where('bagian', $bagian)->update([
            'judul' => $judul,
            'isi' => $isi,
            'status' => KontenWebsite::TERBIT,
        ]);
    }
}
