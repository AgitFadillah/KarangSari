<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Penyaringan log aktivitas per hari.
 *
 * Log bisa berisi ribuan baris, sehingga perangkat desa perlu menyaring
 * satu tanggal saja. Parameter ?tanggal= datang dari URL dan bisa berisi
 * apa pun, jadi nilai yang tidak sah harus diperlakukan sebagai "tanpa
 * filter", bukan melempar galat 500.
 */
class LogHarianTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Uji',
            'username' => 'adminuji',
            'password' => Hash::make('sandi-uji-test-1234'),
            'role' => RoleAdmin::Administrator,
            'is_active' => true,
        ]);
    }

    protected function catatPada(string $tanggal, string $tindakan): LogAktivitas
    {
        $log = LogAktivitas::catat(tindakan: $tindakan, subjek: 'uji', tingkat: LogAktivitas::SUKSES);
        $log->forceFill(['created_at' => $tanggal.' 10:00:00'])->save();

        return $log;
    }

    public function test_log_dikelompokkan_per_hari(): void
    {
        $this->catatPada('2026-11-18', 'Tindakan hari pertama');
        $this->catatPada('2026-11-19', 'Tindakan hari kedua');
        $this->catatPada('2026-11-19', 'Tindakan hari kedua lagi');

        $html = $this->actingAs($this->admin)
            ->get(route('admin.log'))
            ->assertOk()
            ->assertSee('Aktivitas per hari')
            ->assertSee('Tindakan hari pertama')
            ->assertSee('Tindakan hari kedua')
            ->getContent();

        // Dua tanggal berbeda muncul pada ringkasan per hari.
        $this->assertStringContainsString('18 Nov 2026', $html);
        $this->assertStringContainsString('19 Nov 2026', $html);
    }

    public function test_filter_tanggal_hanya_menampilkan_hari_itu(): void
    {
        $this->catatPada('2026-11-18', 'Tindakan hari pertama');
        $this->catatPada('2026-11-19', 'Tindakan hari kedua');

        $this->actingAs($this->admin)
            ->get(route('admin.log', ['tanggal' => '2026-11-19']))
            ->assertOk()
            ->assertSee('Tindakan hari kedua')
            ->assertDontSee('Tindakan hari pertama');
    }

    public function test_filter_tanggal_bisa_digabung_dengan_pencarian(): void
    {
        $this->catatPada('2026-11-19', 'Memverifikasi pengajuan surat');
        $this->catatPada('2026-11-19', 'Menghapus berita lama');
        $this->catatPada('2026-11-18', 'Memverifikasi pengajuan surat');

        $this->actingAs($this->admin)
            ->get(route('admin.log', ['tanggal' => '2026-11-19', 'q' => 'memverifikasi']))
            ->assertOk()
            ->assertSee('Memverifikasi pengajuan surat')
            ->assertDontSee('Menghapus berita lama');
    }

    public function test_tanggal_tidak_sah_diabaikan_bukan_galat(): void
    {
        $this->catatPada('2026-11-19', 'Tindakan hari kedua');

        foreach (['abc', '2026-13-45', '2026-02-31', "1'; DROP TABLE log_aktivitets;--"] as $nilai) {
            $this->actingAs($this->admin)
                ->get(route('admin.log', ['tanggal' => $nilai]))
                ->assertOk()
                ->assertSee('Tindakan hari kedua');
        }
    }

    public function test_ringkasan_per_hari_tetap_tampil_saat_tanggal_dipilih(): void
    {
        $this->catatPada('2026-11-18', 'Tindakan hari pertama');
        $this->catatPada('2026-11-19', 'Tindakan hari kedua');

        // Bila ringkasan ikut tersaring, hanya satu tanggal tersisa dan
        // navigasi antar hari menjadi mustahil.
        $this->actingAs($this->admin)
            ->get(route('admin.log', ['tanggal' => '2026-11-19']))
            ->assertOk()
            ->assertSee('18 Nov 2026')
            ->assertSee('19 Nov 2026');
    }
}
