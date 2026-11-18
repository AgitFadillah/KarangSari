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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilDesaDataTest extends TestCase
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

    public function test_kunci_data_bukan_angka_tidak_menyebabkan_500(): void
    {
        $profil = ProfilDesa::query()->firstOrFail();

        // Di PostgreSQL, `where id = 'abc'` melempar
        // "invalid input syntax for type integer" -> HTTP 500.
        // Di SQLite diam-diam mengembalikan null, jadi bug ini hanya
        // muncul setelah situs dipasang di produksi.
        $res = $this->actingAs($this->admin)->put(route('admin.profil.update'), [
            'nama_desa' => $profil->nama_desa,
            'data' => [
                'abc' => ['nilai' => '10'],
                '1.5' => ['nilai' => '20'],
                '' => ['nilai' => '30'],
            ],
        ]);

        $this->assertNotSame(500, $res->getStatusCode(), 'Kunci data[] non-numerik membuat server 500.');
        $this->assertSame(302, $res->getStatusCode());
    }

    public function test_data_sah_tetap_tersimpan(): void
    {
        $profil = ProfilDesa::query()->firstOrFail();
        $baris = DataDesa::query()->whereNotNull('label')->firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.profil.update'), [
            'nama_desa' => $profil->nama_desa,
            'data' => [
                (string) $baris->id => ['nilai' => '1234', 'keterangan' => 'catatan'],
            ],
        ])->assertRedirect();

        $baris->refresh();
        $this->assertSame('1234', $baris->nilai);
        $this->assertSame('catatan', $baris->keterangan);
    }
}
