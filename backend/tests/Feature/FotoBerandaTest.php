<?php

namespace Tests\Feature;

use App\Models\ProfilDesa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Foto utama beranda.
 *
 * Keluhan lapangan: foto sudah diunggah lewat menu Profil & Kontak, tetapi
 * tidak pernah muncul di situs publik — dan kotak unggah kembali kosong
 * setelah disimpan sehingga terlihat seperti unggahan gagal.
 *
 * Tes ini mengunci dua hal:
 *  1. Berkas benar-benar tersimpan dan tampil di beranda.
 *  2. Form admin memperlihatkan berkas tersimpan setelah disimpan.
 */
class FotoBerandaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Support\SiteProfile::lupakan();
    }

    protected function admin(): User
    {
        return User::firstOrCreate(
            ['username' => 'sari'],
            [
                'name' => 'Sari',
                'email' => 'sari@example.test',
                'password' => bcrypt('rahasia'),
                'role' => \App\Enums\RoleAdmin::Administrator,
                'is_active' => true,
            ]
        );
    }

    protected function profilAwal(): ProfilDesa
    {
        return ProfilDesa::query()->create([
            'nama_desa' => 'Desa KarangSari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => 'Lampung Selatan',
        ]);
    }

    protected function dataDasar(array $tambahan = []): array
    {
        return array_merge([
            'nama_desa' => 'Desa KarangSari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => 'Lampung Selatan',
        ], $tambahan);
    }

    public function test_foto_beranda_tersimpan_dan_tampil_di_beranda(): void
    {
        Storage::fake('public');
        $this->profilAwal();

        $this->actingAs($this->admin())
            ->put(route('admin.profil.update'), $this->dataDasar([
                'foto_beranda' => UploadedFile::fake()->image('desa.jpg', 1200, 800),
            ]))
            ->assertSessionHasNoErrors();

        $profil = ProfilDesa::query()->first();
        $this->assertNotNull($profil->foto_beranda_path);
        Storage::disk('public')->assertExists($profil->foto_beranda_path);

        \App\Support\SiteProfile::lupakan();

        // Inti keluhan: foto harus benar-benar muncul di halaman beranda.
        // Letaknya di seksi "Sekilas Desa" (bukan hero — hero memakai kartu
        // contoh surat sesuai permintaan perangkat desa).
        $this->get('/')
            ->assertOk()
            ->assertSee('class="sk-foto"', false)
            ->assertSee($profil->foto_beranda_path, false);
    }

    public function test_hero_tetap_memakai_kartu_contoh_surat(): void
    {
        Storage::fake('public');
        $this->profilAwal();

        $this->actingAs($this->admin())
            ->put(route('admin.profil.update'), $this->dataDasar([
                'foto_beranda' => UploadedFile::fake()->image('desa.jpg', 1200, 800),
            ]));

        \App\Support\SiteProfile::lupakan();

        // Visual hero tidak digeser oleh foto desa; kartu contoh surat tetap.
        $this->get('/')
            ->assertOk()
            ->assertSee('class="doc-stack"', false)
            ->assertDontSee('class="hero-foto"', false);
    }

    public function test_beranda_tanpa_foto_tetap_memakai_kartu_contoh_surat(): void
    {
        $this->profilAwal();

        // Tanpa foto, hero tetap memakai mockup surat dan tidak ada blok
        // foto di "Sekilas Desa". Catatan: aturan CSS .sk-foto selalu ada
        // di <style>, jadi yang diperiksa markup-nya, bukan nama kelasnya.
        $this->get('/')
            ->assertOk()
            ->assertDontSee('class="sk-foto"', false)
            ->assertSee('class="doc-stack"', false);
    }

    public function test_form_admin_menampilkan_berkas_tersimpan(): void
    {
        Storage::fake('public');
        $this->profilAwal();

        $this->actingAs($this->admin())
            ->put(route('admin.profil.update'), $this->dataDasar([
                'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
                'foto_beranda' => UploadedFile::fake()->image('desa.jpg', 1200, 800),
            ]));

        // Membuka form lagi harus menandai kedua berkas sudah ada,
        // supaya kotak unggah tidak tampil kosong.
        $this->actingAs($this->admin())
            ->get(route('admin.profil.edit'))
            ->assertOk()
            ->assertSee('data-berkas-ada="1"', false)
            ->assertSee('Logo desa tersimpan')
            ->assertSee('Foto desa tersimpan');
    }

    public function test_foto_beranda_dapat_dihapus(): void
    {
        Storage::fake('public');
        $this->profilAwal();

        $this->actingAs($this->admin())
            ->put(route('admin.profil.update'), $this->dataDasar([
                'foto_beranda' => UploadedFile::fake()->image('desa.jpg', 1200, 800),
            ]));

        $profil = ProfilDesa::query()->first();
        $jalur = $profil->foto_beranda_path;
        $this->assertNotNull($jalur);

        // Tombol "Hapus" mengirim foto_beranda_hapus=1 tanpa berkas baru.
        $this->actingAs($this->admin())
            ->put(route('admin.profil.update'), $this->dataDasar([
                'foto_beranda_hapus' => '1',
            ]));

        $this->assertNull(ProfilDesa::query()->first()->foto_beranda_path);
        Storage::disk('public')->assertMissing($jalur);
    }

    public function test_unggah_foto_baru_menggantikan_foto_lama(): void
    {
        Storage::fake('public');
        $this->profilAwal();

        $this->actingAs($this->admin())
            ->put(route('admin.profil.update'), $this->dataDasar([
                'foto_beranda' => UploadedFile::fake()->image('lama.jpg', 800, 600),
            ]));

        $lama = ProfilDesa::query()->first()->foto_beranda_path;

        $this->actingAs($this->admin())
            ->put(route('admin.profil.update'), $this->dataDasar([
                'foto_beranda' => UploadedFile::fake()->image('baru.jpg', 1200, 800),
            ]));

        $baru = ProfilDesa::query()->first()->foto_beranda_path;

        $this->assertNotSame($lama, $baru);
        Storage::disk('public')->assertMissing($lama);
        Storage::disk('public')->assertExists($baru);
    }

    public function test_foto_beranda_menolak_berkas_bukan_gambar(): void
    {
        Storage::fake('public');
        $this->profilAwal();

        $this->actingAs($this->admin())
            ->put(route('admin.profil.update'), $this->dataDasar([
                'foto_beranda' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('foto_beranda');

        $this->assertNull(ProfilDesa::query()->first()->foto_beranda_path);
    }
}
