<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Menu pengguna di dasar sidebar admin.
 *
 * Keluhan lapangan: baris "Sari Wulandari / Administrator" dengan ikon panah
 * tidak bisa dibuka, dan kartu "Butuh bantuan?" tampak menutupinya. Baris itu
 * dulu hanya <a>/<div> statis dengan panah yang menyesatkan, dan kartu
 * bantuan berada DI DALAM daftar menu yang bisa digulir.
 */
class SidebarAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Support\SiteProfile::lupakan();

        $this->admin = User::create([
            'name' => 'Sari Wulandari',
            'username' => 'sari',
            'password' => Hash::make('sandi-uji-test-1234'),
            'role' => RoleAdmin::Administrator,
            'is_active' => true,
        ]);
    }

    protected function halamanAdmin(): string
    {
        return $this->actingAs($this->admin)
            ->get(route('admin.ringkasan'))
            ->assertOk()
            ->getContent();
    }

    public function test_baris_pengguna_berupa_tombol_yang_bisa_dibuka(): void
    {
        $html = $this->halamanAdmin();

        $this->assertStringContainsString('id="adminSidebarUserBtn"', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('aria-controls="adminSidebarUserPanel"', $html);
        $this->assertStringContainsString('id="adminSidebarUserPanel"', $html);

        // Tombol, bukan tautan/div statis.
        $this->assertMatchesRegularExpression(
            '/<button[^>]*id="adminSidebarUserBtn"/',
            $html
        );
    }

    public function test_panel_pengguna_berisi_jalan_keluar_dan_akun(): void
    {
        $html = $this->halamanAdmin();

        $panel = $this->potongan($html, 'adminSidebarUserPanel');

        $this->assertStringContainsString('Akun Admin', $panel);
        $this->assertStringContainsString('Keluar', $panel);
        $this->assertStringContainsString(route('admin.logout'), $panel);
        $this->assertStringContainsString('sari', $panel);
    }

    public function test_kartu_bantuan_di_luar_daftar_menu(): void
    {
        $html = $this->halamanAdmin();

        // Kartu bantuan harus berada setelah </nav>, bukan di dalamnya:
        // di dalam nav ia ikut tergulir dan menimpa baris pengguna.
        $posisiNavTutup = strpos($html, '</nav>');
        $posisiBantuan = strpos($html, 'Butuh bantuan?');

        $this->assertNotFalse($posisiNavTutup);
        $this->assertNotFalse($posisiBantuan);
        $this->assertGreaterThan($posisiNavTutup, $posisiBantuan);
    }

    public function test_baris_pengguna_berada_setelah_kartu_bantuan(): void
    {
        $html = $this->halamanAdmin();

        $posisiBantuan = strpos($html, 'Butuh bantuan?');
        $posisiPengguna = strpos($html, 'id="adminSidebarUserBtn"');

        $this->assertNotFalse($posisiBantuan);
        $this->assertNotFalse($posisiPengguna);
        $this->assertGreaterThan($posisiBantuan, $posisiPengguna);
    }

    public function test_peran_operator_tidak_melihat_menu_akun_di_panel(): void
    {
        $operator = User::create([
            'name' => 'Dewi Lestari',
            'username' => 'dewi',
            'password' => Hash::make('sandi-uji-test-1234'),
            'role' => RoleAdmin::OperatorKonten,
            'is_active' => true,
        ]);

        $html = $this->actingAs($operator)
            ->get(route('admin.ringkasan'))
            ->assertOk()
            ->getContent();

        $panel = $this->potongan($html, 'adminSidebarUserPanel');

        // Operator konten tetap bisa keluar, tetapi tidak melihat menu akun.
        $this->assertStringContainsString('Keluar', $panel);
        $this->assertStringNotContainsString('Akun Admin', $panel);
    }

    /** Potong HTML mulai dari penanda sampai akhir dokumen. */
    protected function potongan(string $html, string $awal): string
    {
        $posisi = strpos($html, $awal);
        $this->assertNotFalse($posisi, "Penanda '{$awal}' tidak ditemukan.");

        return substr($html, $posisi);
    }
}
