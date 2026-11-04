<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Models\User;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    /** Sandi dummy khusus test — TIDAK sama dengan sandi admin sungguhan. */
    private const SANDI_UJI = 'sandi-uji-test-1234';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProfilDesaSeeder::class);
    }

    protected function adminAktif(array $override = []): User
    {
        return User::create(array_merge([
            'name' => 'Sari Wulandari',
            'username' => 'sari',
            'email' => 'sari@sukamaju.desa.id',
            'password' => Hash::make(self::SANDI_UJI),
            'role' => RoleAdmin::Administrator,
            'is_active' => true,
        ], $override));
    }

    public function test_halaman_login_tampil_dengan_csrf(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Masuk ke dashboard')
            ->assertSee('_token', false);
    }

    public function test_login_benar_masuk_ringkasan_dan_mencatat_login(): void
    {
        $admin = $this->adminAktif();

        $this->post('/admin/login', ['username' => 'sari', 'password' => self::SANDI_UJI])
            ->assertRedirect(route('admin.ringkasan'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($admin->fresh()->last_login_at);
        $this->assertDatabaseHas('log_aktivitets', ['user_id' => $admin->id, 'tindakan' => 'Login ke dashboard']);

        $this->get('/admin')->assertOk()->assertSee('Total pengajuan');
    }

    public function test_login_salah_ditolak(): void
    {
        $this->adminAktif();

        $this->post('/admin/login', ['username' => 'sari', 'password' => 'salah-sekali'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_akun_nonaktif_ditolak(): void
    {
        $this->adminAktif(['is_active' => false]);

        $this->post('/admin/login', ['username' => 'sari', 'password' => self::SANDI_UJI])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_username_tidak_terdaftar_ditolak(): void
    {
        $this->post('/admin/login', ['username' => 'tidakada', 'password' => self::SANDI_UJI])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        foreach (['/admin', '/admin/pengajuan', '/admin/jenis-surat', '/admin/akun', '/admin/log'] as $url) {
            $this->get($url)->assertRedirect(route('admin.login'));
        }
    }

    public function test_admin_aktif_logout_menghapus_sesi(): void
    {
        $admin = $this->adminAktif();

        $this->actingAs($admin)
            ->post('/admin/logout')
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_akun_yang_dinonaktifkan_saat_login_dikeluarkan(): void
    {
        $admin = $this->adminAktif();

        $this->actingAs($admin)->get('/admin')->assertOk();

        $admin->forceFill(['is_active' => false])->save();

        $this->actingAs($admin->fresh())
            ->get('/admin')
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_throttle_login_setelah_enam_percobaan(): void
    {
        $this->adminAktif();

        for ($i = 0; $i < 6; $i++) {
            $this->post('/admin/login', ['username' => 'sari', 'password' => 'salah']);
        }

        $this->post('/admin/login', ['username' => 'sari', 'password' => 'salah'])
            ->assertStatus(429);
    }

    public function test_operator_konten_tidak_boleh_kelola_akun(): void
    {
        $operator = $this->adminAktif(['username' => 'dewi', 'role' => RoleAdmin::OperatorKonten]);

        $this->actingAs($operator)->get('/admin/akun')->assertForbidden();
        $this->actingAs($operator)->get('/admin/konten')->assertOk();
        $this->actingAs($operator)->get('/admin/pengajuan')->assertOk();
    }
}
