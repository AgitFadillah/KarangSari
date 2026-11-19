<?php

namespace Tests\Feature;

use App\Models\PerangkatDesa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bagan struktur pemerintahan pada halaman Profil Desa.
 *
 * Perangkat desa meminta susunan perangkat dibuat sebagai pohon: pemimpin
 * di puncak dengan garis penghubung ke perangkat lain (di ponsel berubah
 * menjadi daftar bertingkat bergaya direktori). Tes ini memastikan bagan
 * tetap terbentuk walau jabatan "Kepala Desa" belum ditulis persis.
 */
class StrukturPemerintahanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Support\SiteProfile::lupakan();
    }

    protected function buatPerangkat(string $nama, string $jabatan, int $urutan): PerangkatDesa
    {
        return PerangkatDesa::query()->create([
            'nama' => $nama,
            'jabatan' => $jabatan,
            'urutan' => $urutan,
            'is_aktif' => true,
        ]);
    }

    public function test_kepala_desa_menjadi_puncak_bagan(): void
    {
        $this->buatPerangkat('Sekretaris Desa', 'Sekretaris Desa', 2);
        $this->buatPerangkat('R O M S I', 'Kepala Desa', 1);

        $html = $this->get('/profil')
            ->assertOk()
            ->assertSee('org-chart', false)
            ->assertSee('org-puncak', false)
            ->getContent();

        // Kartu puncak memuat nama Kepala Desa.
        $puncak = $this->potongan($html, 'org-puncak', 'org-cabang');
        $this->assertStringContainsString('R O M S I', $puncak);
        $this->assertStringNotContainsString('Sekretaris Desa', $puncak);
    }

    public function test_perangkat_lain_berada_di_cabang(): void
    {
        $this->buatPerangkat('R O M S I', 'Kepala Desa', 1);
        $this->buatPerangkat('Sekretaris Desa', 'Sekretaris Desa', 2);
        $this->buatPerangkat('Kaur Keuangan', 'Kaur Keuangan', 3);

        $html = $this->get('/profil')->assertOk()->getContent();

        $cabang = $this->potongan($html, 'org-cabang', null);

        $this->assertStringContainsString('Sekretaris Desa', $cabang);
        $this->assertStringContainsString('Kaur Keuangan', $cabang);
        $this->assertStringNotContainsString('Kepala Desa', $cabang);
    }

    public function test_tanpa_jabatan_kepala_desa_entri_pertama_jadi_puncak(): void
    {
        // Data lapangan kadang diisi "Kades" atau nama jabatan lain; bagan
        // tetap harus punya satu puncak, bukan gagal dirender.
        $this->buatPerangkat('Pemimpin Desa', 'Kades', 1);
        $this->buatPerangkat('Staf', 'Staf', 2);

        $html = $this->get('/profil')->assertOk()->getContent();

        $puncak = $this->potongan($html, 'org-puncak', 'org-cabang');
        $this->assertStringContainsString('Pemimpin Desa', $puncak);
    }

    public function test_satu_perangkat_tetap_dirender(): void
    {
        $this->buatPerangkat('R O M S I', 'Kepala Desa', 1);

        $this->get('/profil')
            ->assertOk()
            ->assertSee('R O M S I')
            ->assertSee('org-puncak', false)
            ->assertDontSee('org-cabang', false);
    }

    public function test_belum_ada_perangkat_menampilkan_keterangan(): void
    {
        $this->get('/profil')
            ->assertOk()
            ->assertSee('Data perangkat desa belum diisi.')
            ->assertDontSee('org-chart', false);
    }

    /** Potong HTML mulai dari penanda sampai penanda akhir (atau habis). */
    protected function potongan(string $html, string $awal, ?string $akhir): string
    {
        $posisi = strpos($html, $awal);
        $this->assertNotFalse($posisi, "Penanda '{$awal}' tidak ditemukan.");

        $sisa = substr($html, $posisi);

        if ($akhir === null) {
            return $sisa;
        }

        $posisiAkhir = strpos($sisa, $akhir);

        return $posisiAkhir === false ? $sisa : substr($sisa, 0, $posisiAkhir);
    }
}
