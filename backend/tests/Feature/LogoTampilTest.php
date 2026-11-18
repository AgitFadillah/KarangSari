<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Models\ProfilDesa;
use App\Models\User;
use App\Support\BerkasPublik;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Logo desa harus benar-benar TAMPIL, bukan sekadar tersimpan.
 *
 * Regresi yang dijaga: URL logo dulu dibangun dari APP_URL sehingga menjadi
 * "http://localhost:8000/storage/..." — origin berbeda dari halaman, sehingga
 * diblokir CSP img-src 'self' dan gambar tampil rusak di semua tempat.
 */
class LogoTampilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(ProfilDesaSeeder::class);
        $this->seed(JenisSuratSeeder::class);
    }

    protected function admin(): User
    {
        return User::query()->firstOrCreate(
            ['username' => 'adm-logo'],
            [
                'name' => 'Admin Logo',
                'email' => 'logo@karangsari.desa.id',
                'password' => Hash::make('sandi-uji-panjang'),
                'role' => RoleAdmin::Administrator,
                'is_active' => true,
            ]
        );
    }

    /** Unggah logo lewat halaman admin, seperti admin sungguhan. */
    protected function unggahLogo(): ProfilDesa
    {
        $this->actingAs($this->admin())
            ->put(route('admin.profil.update'), [
                'nama_desa' => 'Desa KarangSari',
                'logo' => UploadedFile::fake()->image('logo.png', 400, 400),
            ])
            ->assertRedirect();

        $profil = ProfilDesa::query()->firstOrFail();
        $this->assertNotNull($profil->logo_path);

        return $profil;
    }

    public function test_unggah_logo_menyimpan_berkas_dan_menghasilkan_url_relatif(): void
    {
        $profil = $this->unggahLogo();

        Storage::disk('public')->assertExists($profil->logo_path);

        $url = $profil->logoUrl();

        // Bukti perbaikan: URL relatif, bukan absolut ber-APP_URL.
        $this->assertNotNull($url);
        $this->assertStringStartsWith('/storage/', $url);
        $this->assertStringNotContainsString('://', $url);
        $this->assertStringNotContainsString('localhost', $url);
    }

    public function test_logo_tampil_di_header_publik_footer_dan_sidebar_admin(): void
    {
        $profil = $this->unggahLogo();
        $url = $profil->logoUrl();

        // Header + footer beranda.
        $this->get('/')
            ->assertOk()
            ->assertSee('src="'.$url.'"', false);

        // Sidebar admin & pratinjau di halaman profil.
        $this->actingAs($this->admin())
            ->get(route('admin.profil.edit'))
            ->assertOk()
            ->assertSee('src="'.$url.'"', false)
            ->assertSee('brand-mark-logo', false);
    }

    public function test_halaman_publik_tidak_memakai_url_absolut_untuk_logo(): void
    {
        $this->unggahLogo();

        // Tidak boleh ada src gambar yang menunjuk host lain (localhost dsb).
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<img[^>]+src="https?:\/\/localhost/i',
            $html,
            'Logo tidak boleh memakai URL absolut dari APP_URL.'
        );
    }

    public function test_logo_hilang_dari_disk_jatuh_ke_lambang_bawaan(): void
    {
        $profil = $this->unggahLogo();

        // Berkas terhapus di luar aplikasi (mis. storage dibersihkan).
        Storage::disk('public')->delete($profil->logo_path);

        $profil->refresh();
        $this->assertNull($profil->logoUrl());

        $this->get('/')
            ->assertOk()
            ->assertDontSee('brand-mark-logo', false)
            ->assertDontSee('/storage/profil/', false);
    }

    public function test_tanpa_logo_memakai_lambang_bawaan(): void
    {
        $this->assertNull(ProfilDesa::query()->firstOrFail()->logoUrl());

        $this->get('/')
            ->assertOk()
            ->assertDontSee('brand-mark-logo', false);
    }

    /** Helper harus tahan terhadap path yang aneh. */
    public function test_helper_url_menormalkan_path(): void
    {
        $this->assertNull(BerkasPublik::url(null));
        $this->assertNull(BerkasPublik::url('   '));
        $this->assertSame('/storage/profil/a.png', BerkasPublik::url('profil/a.png'));
        $this->assertSame('/storage/profil/a.png', BerkasPublik::url('/profil/a.png'));
        $this->assertSame('/storage/profil/a.png', BerkasPublik::url('storage/profil/a.png'));
        $this->assertSame('/storage/profil/a.png', BerkasPublik::url('profil\\a.png'));

        // URL absolut dibiarkan apa adanya.
        $this->assertSame(
            'https://cdn.contoh.id/logo.png',
            BerkasPublik::url('https://cdn.contoh.id/logo.png')
        );
    }
}
