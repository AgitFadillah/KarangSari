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

    /**
     * Regresi: CSP lama memblokir blob: (pratinjau foto perangkat) dan
     * fonts.googleapis.com / fonts.gstatic.com (font Plus Jakarta Sans),
     * sehingga foto tidak bisa dipratinjau dan font jatuh ke bawaan sistem.
     */
    public function test_csp_mengizinkan_blob_dan_google_fonts(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('blob:', $csp);
        $this->assertStringContainsString('https://fonts.googleapis.com', $csp);
        $this->assertStringContainsString('https://fonts.gstatic.com', $csp);
    }

    /**
     * Peta lokasi di beranda/kontak memakai iframe Google Maps. Tanpa
     * frame-src, CSP default-src 'self' memblokir iframe tersebut sehingga
     * kotak peta tampil kosong.
     */
    public function test_csp_mengizinkan_iframe_peta_google_maps(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('frame-src https://www.google.com', $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
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

    public function test_kode_akses_tidak_ikut_serialisasi_model(): void
    {
        $jenis = \App\Models\JenisSurat::query()->firstOrFail();

        $pengajuan = new \App\Models\Pengajuan;
        $pengajuan->forceFill([
            'jenis_surat_id' => $jenis->id,
            'nomor_referensi' => 'KRS-2026-9001',
            'kode_akses' => 'RAHASIA2',
            'nama' => 'Uji',
            'nik' => '1801010101010009',
            'tempat_lahir' => 'Lampung',
            'tanggal_lahir' => '1990-01-01',
            'alamat' => 'Uji',
            'keperluan' => 'Uji',
            'whatsapp' => '081200000000',
            'persetujuan_data' => true,
            'persetujuan_at' => now(),
        ]);
        $pengajuan->save();

        // Kode tetap bisa dibaca kode program (untuk dikirim petugas), tetapi
        // tidak boleh muncul pada toArray()/toJson() yang mungkin dikirim ke
        // klien.
        $this->assertSame('RAHASIA2', $pengajuan->kode_akses);
        $this->assertArrayNotHasKey('kode_akses', $pengajuan->toArray());
        $this->assertStringNotContainsString('RAHASIA2', $pengajuan->toJson());
    }

    public function test_kode_akses_acak_memakai_alfabet_aman_dan_panjang_tetap(): void
    {
        $kode = \App\Support\KodeAkses::acak();

        $this->assertSame(8, strlen($kode));
        $this->assertMatchesRegularExpression('/^[2-9A-HJ-NP-Z]{8}$/', $kode);

        // Normalisasi menerima ketikan warga yang memakai huruf kecil/spasi.
        $this->assertSame('K7QM3FZP', \App\Support\KodeAkses::normalisasi(' k7qm-3fzp '));
    }

    public function test_teks_baca_membersihkan_skrip_di_semua_tag(): void
    {
        $html = \App\Support\TeksBaca::keHtml(
            '<p onclick="alert(1)" style="color:red">Halo</p>'
            .'<a href="javascript:alert(2)">klik</a>'
        );

        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('style=', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('Halo', $html);
    }

    /**
     * Peramban men-dekode entitas HTML pada nilai atribut SEBELUM membaca
     * skema tautan. Penyaringan berbasis teks "javascript:" saja tidak cukup:
     * "javas&#99;ript:" tidak memuat teks itu, tetapi tetap menjadi tautan
     * javascript: saat diklik.
     */
    public function test_teks_baca_menolak_tautan_berskema_tersamar(): void
    {
        $berbahaya = [
            'javas&#99;ript:alert(1)',
            'jav&#x61;script:alert(1)',
            '&#106;avascript:alert(1)',
            '&amp;#106;avascript:alert(1)',
            'javascript&colon;alert(1)',
            'JaVaScRiPt:alert(1)',
            'java&#9;script:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'vbscript:msgbox(1)',
            '  javascript:alert(1)',
        ];

        foreach ($berbahaya as $href) {
            $html = \App\Support\TeksBaca::keHtml('<p>x</p><a href="'.$href.'">klik</a>');

            $this->assertStringNotContainsString('href', $html, 'Tautan berbahaya lolos: '.$href);
            $this->assertStringContainsString('klik', $html, 'Teks tautan tidak boleh ikut hilang.');
        }
    }

    public function test_teks_baca_mempertahankan_tautan_yang_sah(): void
    {
        $html = \App\Support\TeksBaca::keHtml(
            '<p>x</p><a href="https://desa.go.id/surat">web</a>'
            .'<a href="/pelayanan">layanan</a>'
            .'<a href="mailto:desa@example.id">email</a>'
        );

        $this->assertStringContainsString('href="https://desa.go.id/surat"', $html);
        $this->assertStringContainsString('href="/pelayanan"', $html);
        $this->assertStringContainsString('href="mailto:desa@example.id"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }
}
