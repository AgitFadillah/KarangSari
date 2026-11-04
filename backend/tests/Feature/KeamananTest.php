<?php

namespace Tests\Feature;

use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeamananTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProfilDesaSeeder::class, JenisSuratSeeder::class]);
    }

    public function test_header_keamanan_terpasang_di_halaman_publik(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    }

    public function test_csp_melarang_framing_dan_object(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
    }

    public function test_hsts_tidak_dikirim_lewat_http(): void
    {
        $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_dokumen_warga_tidak_bisa_diakses_lewat_url_storage(): void
    {
        // Route publik /storage/{path} tidak boleh ada sama sekali.
        foreach (['/storage/', '/storage/dokumen/2026/a/ktp.pdf', '/storage/app/private/'] as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    public function test_halaman_konfirmasi_tidak_bisa_dienumerasi(): void
    {
        $this->get('/pengajuan/berhasil/1')->assertNotFound();
        $this->get('/pengajuan/berhasil/999')->assertNotFound();
    }

    public function test_halaman_login_memakai_token_csrf(): void
    {
        // Middleware CSRF Laravel otomatis dimatikan di environment testing,
        // jadi yang bisa diverifikasi di sini adalah token benar-benar dirender.
        // Penolakan 419 sudah diuji manual lewat server sungguhan.
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('name="_token"', false);
    }
}
