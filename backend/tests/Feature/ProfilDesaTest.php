<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Models\DataDesa;
use App\Models\ProfilDesa;
use App\Models\User;
use App\Support\SiteProfile;
use Database\Seeders\DataDesaSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilDesaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([ProfilDesaSeeder::class, DataDesaSeeder::class]);
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

    public function test_data_desa_berupa_string_tidak_menyebabkan_500(): void
    {
        $profil = ProfilDesa::query()->firstOrFail();

        $res = $this->actingAs($this->admin)->put(route('admin.profil.update'), [
            'nama_desa' => $profil->nama_desa,
            // Warga/admin salah kirim: nilai baris bukan array.
            'data' => ['1' => 'bukan-array'],
        ]);

        $this->assertNotSame(500, $res->getStatusCode(), 'Input data[] yang bukan array membuat server 500.');
    }

    public function test_logo_lama_tidak_hilang_bila_penyimpanan_db_gagal(): void
    {
        $profil = ProfilDesa::query()->firstOrFail();

        // Logo lama yang sah.
        $lama = UploadedFile::fake()->image('logo-lama.png', 40, 40);
        $pathLama = $lama->store('profil', 'public');
        $profil->update(['logo_path' => $pathLama]);

        Storage::disk('public')->assertExists($pathLama);

        // Paksa penyimpanan DB gagal SETELAH berkas lama dihapus.
        \Illuminate\Support\Facades\DB::listen(function () {});
        $gagal = false;

        \Illuminate\Support\Facades\Event::listen('eloquent.saving: '.ProfilDesa::class, function () use (&$gagal) {
            if (! $gagal) {
                $gagal = true;

                throw new \RuntimeException('DB mati saat menyimpan profil.');
            }
        });

        try {
            $this->actingAs($this->admin)->put(route('admin.profil.update'), [
                'nama_desa' => 'Nama Baru',
                'logo' => UploadedFile::fake()->image('logo-baru.png', 40, 40),
            ]);
        } catch (\Throwable) {
            // Diharapkan gagal.
        }

        // Logo lama HARUS tetap ada, karena DB masih menunjuk ke sana.
        $profil->refresh();
        $this->assertSame($pathLama, $profil->logo_path);
        $this->assertTrue(
            Storage::disk('public')->exists($profil->logo_path),
            'Logo lama terhapus padahal DB masih menunjuk ke berkas itu — logo desa jadi rusak.'
        );
    }
}
