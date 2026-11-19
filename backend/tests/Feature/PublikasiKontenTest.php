<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Models\KontenWebsite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Publikasi konten website (draf vs terbit).
 *
 * Perangkat desa melaporkan tidak bisa mengubah status menjadi "Terbit".
 * Penyebabnya bukan markup (hanya ada satu <select>), melainkan dropdown
 * kustom yang tidak bisa diklik. Sebagai jalur yang tidak bisa gagal, form
 * kini juga punya tombol eksplisit "Terbitkan sekarang".
 */
class PublikasiKontenTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected KontenWebsite $konten;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Support\SiteProfile::lupakan();

        $this->admin = User::create([
            'name' => 'Admin Uji',
            'username' => 'adminuji',
            'password' => Hash::make('sandi-uji-test-1234'),
            'role' => RoleAdmin::Administrator,
            'is_active' => true,
        ]);

        $this->konten = KontenWebsite::query()->create([
            'bagian' => 'beranda',
            'judul' => 'Judul Awal',
            'isi' => 'Isi awal konten.',
            'status' => KontenWebsite::DRAF,
        ]);
    }

    public function test_tombol_terbitkan_menyimpan_status_terbit(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.konten.update', $this->konten), [
                'judul' => 'Judul Awal',
                'isi' => 'Isi awal konten.',
                'status' => KontenWebsite::DRAF,
                'terbitkan' => '1',
            ])
            ->assertRedirect(route('admin.konten.edit', $this->konten))
            ->assertSessionHas('status');

        $this->assertSame(KontenWebsite::TERBIT, $this->konten->refresh()->status);
    }

    public function test_tombol_draf_menyimpan_status_draf(): void
    {
        $this->konten->update(['status' => KontenWebsite::TERBIT]);

        $this->actingAs($this->admin)
            ->put(route('admin.konten.update', $this->konten), [
                'judul' => 'Judul Awal',
                'isi' => 'Isi awal konten.',
                'status' => KontenWebsite::TERBIT,
                'drafkan' => '1',
            ]);

        $this->assertSame(KontenWebsite::DRAF, $this->konten->refresh()->status);
    }

    public function test_dropdown_status_tetap_bekerja(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.konten.update', $this->konten), [
                'judul' => 'Judul Baru',
                'isi' => 'Isi baru.',
                'status' => KontenWebsite::TERBIT,
            ])
            ->assertSessionHasNoErrors();

        $konten = $this->konten->refresh();

        $this->assertSame(KontenWebsite::TERBIT, $konten->status);
        $this->assertSame('Judul Baru', $konten->judul);
    }

    public function test_status_tidak_dikenal_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.konten.update', $this->konten), [
                'judul' => 'Judul Awal',
                'status' => 'entah',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(KontenWebsite::DRAF, $this->konten->refresh()->status);
    }

    public function test_konten_terbit_tampil_di_halaman_publik(): void
    {
        $this->konten->update(['status' => KontenWebsite::TERBIT]);

        // Judul konten bagian beranda dipakai sebagai teks pengantar halaman.
        $this->get('/')->assertOk()->assertSee('Judul Awal');
    }

    public function test_form_hanya_punya_satu_kontrol_status(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.konten.edit', $this->konten))
            ->assertOk()
            ->getContent();

        // Satu <select name="status"> asli; dropdown kustom dibuat JS di
        // browser, jadi tidak boleh ada duplikat di HTML server.
        $this->assertSame(1, substr_count($html, 'name="status"'));

        // Tombol eksplisit tersedia sebagai jalur alternatif.
        $this->assertStringContainsString('name="terbitkan"', $html);
        $this->assertStringContainsString('name="drafkan"', $html);
    }
}
