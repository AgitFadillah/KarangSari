<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Enums\StatusPengajuan;
use App\Models\JenisSurat;
use App\Models\LogAktivitas;
use App\Models\Pengajuan;
use App\Models\User;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PencarianTest extends TestCase
{
    use RefreshDatabase;

    protected User $verifikator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([ProfilDesaSeeder::class, JenisSuratSeeder::class]);
        Storage::fake('local');
        Storage::fake('public');

        $this->verifikator = User::create([
            'name' => 'Rangga',
            'username' => 'rangga',
            'password' => Hash::make('sandi-uji-test-1234'),
            'role' => RoleAdmin::Verifikator,
            'is_active' => true,
        ]);
    }

    protected function buat(string $nama, string $nik): Pengajuan
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();
        $p = new Pengajuan;
        $p->fill([
            'jenis_surat_id' => $jenis->id,
            'nama' => $nama,
            'nik' => $nik,
            'tempat_lahir' => 'Bogor',
            'tanggal_lahir' => '1987-08-15',
            'alamat' => 'Kp. Pasir Angin',
            'keperluan' => 'Administrasi',
            'whatsapp' => '081234567890',
            'persetujuan_data' => true,
            'persetujuan_at' => now(),
        ]);
        $p->nomor_referensi = 'KRS-2026-'.str_pad((string) (Pengajuan::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);
        $p->isiStatus(StatusPengajuan::MenungguVerifikasi);
        $p->save();

        return $p;
    }

    public function test_pencarian_tidak_peka_huruf_besar_kecil(): void
    {
        $this->buat('Budi Santoso', '3201121508870003');

        foreach (['budi', 'BUDI', 'Budi', 'santoso', 'SANTOSO'] as $kata) {
            $this->actingAs($this->verifikator)
                ->get('/admin/pengajuan?q='.urlencode($kata))
                ->assertOk()
                ->assertSee('Budi Santoso');
        }
    }

    public function test_pencarian_nik_dari_awalan(): void
    {
        $this->buat('Budi Santoso', '3201121508870003');

        $this->actingAs($this->verifikator)
            ->get('/admin/pengajuan?q=320112')
            ->assertOk()
            ->assertSee('Budi Santoso');

        $this->actingAs($this->verifikator)
            ->get('/admin/pengajuan?q=999999')
            ->assertOk()
            ->assertDontSee('Budi Santoso');
    }

    public function test_wildcard_like_diperlakukan_sebagai_huruf_biasa(): void
    {
        $this->buat('Budi Santoso', '3201121508870003');

        // "%" tidak boleh berarti "semua data".
        $this->actingAs($this->verifikator)
            ->get('/admin/pengajuan?q='.urlencode('%'))
            ->assertOk()
            ->assertDontSee('Budi Santoso');

        $this->actingAs($this->verifikator)
            ->get('/admin/pengajuan?q='.urlencode('_'))
            ->assertOk()
            ->assertDontSee('Budi Santoso');
    }

    public function test_pencarian_log_tidak_peka_huruf_besar_kecil(): void
    {
        LogAktivitas::catat(tindakan: 'Menghapus Potensi Desa', subjek: 'Kebun Durian');

        $this->actingAs($this->verifikator)
            ->get('/admin/log?q=kebun')
            ->assertOk()
            ->assertSee('Kebun Durian');
    }

    public function test_pencarian_jenis_surat_tidak_peka_huruf_besar_kecil(): void
    {
        $this->actingAs($this->verifikator)
            ->get('/admin/jenis-surat?q=DOMISILI')
            ->assertOk()
            ->assertSee('Domisili');
    }

    /**
     * Jabatan Kepala Desa dicocokkan dengan LIKE. Di PostgreSQL LIKE peka
     * huruf besar/kecil, sehingga jabatan "kepala desa" (huruf kecil) akan
     * membuat blok sambutan Kepala Desa hilang dari beranda dan profil —
     * padahal di SQLite tampak normal. Pencocokan harus tidak peka huruf.
     */
    public function test_kepala_desa_ditemukan_meski_jabatan_huruf_kecil(): void
    {
        \App\Models\PerangkatDesa::query()->update(['is_aktif' => false]);

        \App\Models\PerangkatDesa::create([
            'nama' => 'R O M S I, SH',
            'jabatan' => 'kepala desa',
            'urutan' => 1,
            'is_aktif' => true,
        ]);

        // Blok sambutan Kepala Desa muncul di beranda dan halaman profil.
        foreach (['/', '/profil'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('R O M S I, SH');
        }
    }
}
