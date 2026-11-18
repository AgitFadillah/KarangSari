<?php

namespace Tests\Feature;

use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Navigasi publik: tombol "Ajukan Surat" berupa tombol terbelah yang
 * membuka dropdown Pelayanan / Cek Status / Panduan.
 *
 * Yang dijaga:
 *  - "Ajukan Surat" sendiri tetap mengarah ke katalog layanan (bisa diklik);
 *  - ketiga tautan layanan TIDAK terduplikasi di menu utama;
 *  - di layar kecil ketiganya tetap terjangkau lewat blok "Layanan" di menu.
 */
class NavigasiPublikTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProfilDesaSeeder::class);
        $this->seed(JenisSuratSeeder::class);
    }

    public function test_tombol_ajukan_surat_terbelah_dan_dropdown_ada(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('nav-aksi-utama', false)
            ->assertSee('navAksiToggle', false)
            ->assertSee('navAksiMenu', false)
            // Menu awalnya tertutup.
            ->assertSee('id="navAksiMenu" hidden', false)
            ->assertSee('aria-expanded="false"', false);
    }

    public function test_ajukan_surat_tetap_menuju_katalog_layanan(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Tautan utama pada tombol terbelah harus ke /pelayanan.
        $this->assertMatchesRegularExpression(
            '#<a href="[^"]*/pelayanan" class="btn btn-primary btn-sm nav-aksi-utama">Ajukan Surat</a>#',
            $html
        );
    }

    public function test_dropdown_memuat_pelayanan_cek_status_dan_panduan(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $menu = $this->menuDropdown($html);

        $this->assertStringContainsString('Pelayanan', $menu);
        $this->assertStringContainsString('Cek Status', $menu);
        $this->assertStringContainsString('Panduan', $menu);

        // Masing-masing menunjuk ke halaman yang benar.
        $this->assertMatchesRegularExpression('#href="[^"]*/pelayanan"#', $menu);
        $this->assertMatchesRegularExpression('#href="[^"]*/cek-status"#', $menu);
        $this->assertMatchesRegularExpression('#href="[^"]*/panduan"#', $menu);
    }

    public function test_menu_utama_tidak_menduplikasi_tautan_layanan(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $navUtama = $this->navUtama($html);

        $this->assertStringNotContainsString('>Pelayanan<', $navUtama);
        $this->assertStringNotContainsString('>Cek Status<', $navUtama);
        $this->assertStringNotContainsString('>Panduan<', $navUtama);

        // Menu utama tetap memuat navigasi umum.
        foreach (['Beranda', 'Profil Desa', 'Informasi', 'Potensi', 'Kontak'] as $label) {
            $this->assertStringContainsString('>'.$label.'<', $navUtama);
        }
    }

    public function test_tautan_layanan_tetap_ada_untuk_layar_kecil(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Blok khusus mobile berisi ketiga tautan, karena tombol disembunyikan.
        $this->assertStringContainsString('nav-mobile-layanan', $html);

        $mobile = $this->blokMobile($html);
        $this->assertStringContainsString('>Pelayanan<', $mobile);
        $this->assertStringContainsString('>Cek Status<', $mobile);
        $this->assertStringContainsString('>Panduan<', $mobile);
    }

    public function test_item_dropdown_aktif_sesuai_halaman(): void
    {
        // Saat berada di halaman Panduan, hanya tautan Panduan yang aktif.
        $menu = $this->menuDropdown($this->get('/panduan')->assertOk()->getContent());

        $this->assertMatchesRegularExpression('#href="[^"]*/panduan" class="active"#', $menu);
        $this->assertMatchesRegularExpression('#href="[^"]*/pelayanan" class=""#', $menu);
        $this->assertMatchesRegularExpression('#href="[^"]*/cek-status" class=""#', $menu);
    }

    public function test_dropdown_tampil_di_semua_halaman_publik(): void
    {
        foreach (['/', '/profil', '/informasi', '/potensi', '/pelayanan', '/kontak', '/panduan', '/cek-status'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('navAksiToggle', false);
        }
    }

    // ---------- helper ----------

    /**
     * Isi blok dropdown "Ajukan Surat".
     *
     * Blok ini berisi <div> bersarang, jadi pencocokan dilakukan dengan
     * menghitung kedalaman tag — bukan regex non-greedy yang berhenti di
     * </div> pertama.
     */
    protected function menuDropdown(string $html): string
    {
        $awal = strpos($html, '<div class="nav-aksi-menu" id="navAksiMenu"');
        $this->assertNotFalse($awal, 'Blok dropdown navAksiMenu tidak ditemukan.');

        return $this->potongBlok($html, $awal);
    }

    /** Isi <nav class="main-nav"> tanpa blok mobile. */
    protected function navUtama(string $html): string
    {
        preg_match('#<nav class="main-nav" id="mainNav">.*?</nav>#s', $html, $m);
        $this->assertNotEmpty($m, 'Menu utama tidak ditemukan.');

        // Buang blok "Layanan" versi mobile agar tidak terhitung ganda.
        return preg_replace(
            '#<div class="nav-mobile-layanan">.*?<div class="nav-mobile-layanan-label">.*?</div>.*?</div>#s',
            '',
            $m[0]
        );
    }

    /** Isi blok "Layanan" versi mobile. */
    protected function blokMobile(string $html): string
    {
        $awal = strpos($html, '<div class="nav-mobile-layanan">');
        $this->assertNotFalse($awal, 'Blok nav-mobile-layanan tidak ditemukan.');

        return $this->potongBlok($html, $awal);
    }

    /**
     * Ambil potongan mulai dari offset $awal hingga tag <div> penutup yang
     * berpasangan (menghitung kedalaman tag div).
     */
    protected function potongBlok(string $html, int $awal): string
    {
        $kedalaman = 0;
        $posisi = $awal;
        $panjang = strlen($html);

        while ($posisi < $panjang) {
            $buka = strpos($html, '<div', $posisi);
            $tutup = strpos($html, '</div>', $posisi);

            if ($tutup === false) {
                break;
            }

            if ($buka !== false && $buka < $tutup) {
                $kedalaman++;
                $posisi = $buka + 4;
                continue;
            }

            $kedalaman--;
            $posisi = $tutup + 6;

            if ($kedalaman === 0) {
                return substr($html, $awal, $posisi - $awal);
            }
        }

        $this->fail('Struktur <div> tidak seimbang saat memotong blok.');
    }
}
