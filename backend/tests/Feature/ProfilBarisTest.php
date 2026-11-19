<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Models\DataDesa;
use App\Models\ProfilDesa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Baris isian yang bisa ditambah/dihapus pada menu Profil & Kontak
 * (batas wilayah dan penggunaan lahan), serta koordinat peta kantor.
 *
 * Sebelumnya keduanya hanya berupa satu kotak teks berformat
 * "Arah: nama desa" dan "Label: persen", sehingga perangkat desa harus
 * menghafal tanda titik dua dan tidak bisa menghapus satu baris saja.
 */
class ProfilBarisTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

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

        ProfilDesa::query()->create([
            'nama_desa' => 'Desa Karang Sari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => 'Lampung Selatan',
        ]);
    }

    /** @return array<string, mixed> */
    protected function dataDasar(array $tambahan = []): array
    {
        return array_merge([
            'nama_desa' => 'Desa Karang Sari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => 'Lampung Selatan',
        ], $tambahan);
    }

    public function test_baris_batas_wilayah_dirangkai_dari_isian_terpisah(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.profil.update'), $this->dataDasar([
                'batas' => [
                    ['arah' => 'Utara', 'tetangga' => 'Desa Sukamaju'],
                    ['arah' => 'Selatan', 'tetangga' => 'Desa Cibeureum'],
                ],
            ]))
            ->assertRedirect(route('admin.profil.edit'))
            ->assertSessionHasNoErrors();

        $profil = ProfilDesa::query()->firstOrFail();

        $this->assertSame("Utara: Desa Sukamaju\nSelatan: Desa Cibeureum", $profil->batas_wilayah);
        $this->assertSame(
            ['Utara' => 'Desa Sukamaju', 'Selatan' => 'Desa Cibeureum'],
            $profil->batas_wilayah_tersusun
        );
    }

    public function test_baris_kosong_diabaikan(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.profil.update'), $this->dataDasar([
                'batas' => [
                    ['arah' => 'Utara', 'tetangga' => 'Desa Sukamaju'],
                    ['arah' => '', 'tetangga' => ''],
                    ['arah' => 'Timur', 'tetangga' => ''],
                ],
            ]));

        $this->assertSame('Utara: Desa Sukamaju', ProfilDesa::query()->firstOrFail()->batas_wilayah);
    }

    public function test_baris_dapat_dihapus_sampai_habis(): void
    {
        $profil = ProfilDesa::query()->firstOrFail();
        $profil->update(['batas_wilayah' => 'Utara: Desa Lama']);

        $this->actingAs($this->admin)
            ->put(route('admin.profil.update'), $this->dataDasar([
                'batas' => [['arah' => '', 'tetangga' => '']],
            ]));

        $this->assertNull(ProfilDesa::query()->firstOrFail()->batas_wilayah);
    }

    public function test_penggunaan_lahan_dirangkai_dan_dibatasi_seratus_persen(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.profil.update'), $this->dataDasar([
                'lahan' => [
                    ['label' => 'Sawah', 'persen' => '55'],
                    ['label' => 'Kebun', 'persen' => '45'],
                    ['label' => 'Berlebih', 'persen' => '250'],
                ],
            ]));

        $profil = ProfilDesa::query()->firstOrFail();

        $this->assertSame("Sawah: 55\nKebun: 45\nBerlebih: 250", $profil->penggunaan_lahan);

        // Model membatasi persentase ke 0..100 saat ditampilkan.
        $this->assertSame(
            [
                ['label' => 'Sawah', 'persen' => 55],
                ['label' => 'Kebun', 'persen' => 45],
                ['label' => 'Berlebih', 'persen' => 100],
            ],
            $profil->penggunaan_lahan_tersusun
        );
    }

    public function test_titik_dua_pada_nama_tidak_merusak_pembacaan_baris(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.profil.update'), $this->dataDasar([
                'batas' => [['arah' => 'Utara: khusus', 'tetangga' => 'Desa Sukamaju']],
            ]));

        $tersusun = ProfilDesa::query()->firstOrFail()->batas_wilayah_tersusun;

        $this->assertCount(1, $tersusun);
        $this->assertSame('Desa Sukamaju', reset($tersusun));
    }

    public function test_format_teks_lama_tetap_diterima(): void
    {
        // Tautan/bookmark formulir lama hanya mengirim field teks.
        $this->actingAs($this->admin)
            ->put(route('admin.profil.update'), $this->dataDasar([
                'batas_wilayah' => 'Utara: Desa Sukamaju',
                'penggunaan_lahan' => 'Sawah: 60',
            ]));

        $profil = ProfilDesa::query()->firstOrFail();

        $this->assertSame('Utara: Desa Sukamaju', $profil->batas_wilayah);
        $this->assertSame('Sawah: 60', $profil->penggunaan_lahan);
    }

    public function test_koordinat_peta_tersimpan_dan_dipakai_sematan(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.profil.update'), $this->dataDasar([
                'peta_lat' => '-5,358421',
                'peta_lng' => '105,294715',
            ]))
            ->assertSessionHasNoErrors();

        $profil = ProfilDesa::query()->firstOrFail();

        // Koma desimal dibakukan menjadi titik.
        $this->assertSame('-5.358421', $profil->peta_lat);
        $this->assertSame('105.294715', $profil->peta_lng);
        $this->assertSame('-5.358421,105.294715', $profil->koordinatPeta());

        $this->assertStringContainsString('-5.358421%2C105.294715', $profil->embedPeta());
        $this->assertStringContainsString('z=17', $profil->embedPeta());
        $this->assertStringContainsString('t=k', $profil->embedPeta());
    }

    public function test_koordinat_di_luar_jangkauan_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.profil.update'), $this->dataDasar([
                'peta_lat' => '-500',
                'peta_lng' => '999',
            ]))
            ->assertSessionHasErrors(['peta_lat', 'peta_lng']);
    }

    public function test_koordinat_bukan_angka_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.profil.update'), $this->dataDasar([
                'peta_lat' => 'dekat pasar',
            ]))
            ->assertSessionHasErrors('peta_lat');
    }

    public function test_tanpa_koordinat_peta_kembali_ke_kueri_teks(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.profil.update'), $this->dataDasar([
                'kueri_peta' => 'Kantor Desa Karang Sari',
                'peta_lat' => '',
                'peta_lng' => '',
            ]));

        $profil = ProfilDesa::query()->firstOrFail();

        $this->assertNull($profil->koordinatPeta());
        $this->assertStringContainsString('z=17', $profil->embedPeta());
        $this->assertStringContainsString('t=k', $profil->embedPeta());
        $this->assertStringContainsString('Kantor%20Desa%20Karang%20Sari', $profil->embedPeta());
    }

    public function test_form_menampilkan_baris_tersimpan_dan_tombol_tambah(): void
    {
        $profil = ProfilDesa::query()->firstOrFail();
        $profil->update([
            'batas_wilayah' => "Utara: Desa Sukamaju\nSelatan: Desa Cibeureum",
            'penggunaan_lahan' => 'Sawah: 60',
            'peta_lat' => '-5.358421',
            'peta_lng' => '105.294715',
        ]);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.profil.edit'))
            ->assertOk()
            ->assertSee('data-tambah-baris="batas"', false)
            ->assertSee('data-tambah-baris="lahan"', false)
            ->getContent();

        // Baris tersimpan muncul sebagai isian, bukan satu kotak teks.
        $this->assertStringContainsString('value="Desa Sukamaju"', $html);
        $this->assertStringContainsString('value="Desa Cibeureum"', $html);
        $this->assertStringContainsString('value="-5.358421"', $html);
    }

    /**
     * Riwayat kepala desa boleh diubah, ditambah, dan dihapus dari satu
     * formulir. Baris lama memakai id sebagai kunci; baris baru memakai
     * kunci "baru_*" dan dihapus lewat riwayat_hapus[].
     */
    public function test_riwayat_kepala_desa_dapat_ditambah_diedit_dan_dihapus(): void
    {
        $pertama = DataDesa::query()->create([
            'kelompok' => DataDesa::RIWAYAT_KADES,
            'label' => 'Cuhli',
            'nilai' => '1998 - 2004',
            'keterangan' => 'Kepala Desa Pertama',
            'urutan' => 1,
        ]);

        $kedua = DataDesa::query()->create([
            'kelompok' => DataDesa::RIWAYAT_KADES,
            'label' => 'Nama Lama',
            'nilai' => '2004 - 2010',
            'keterangan' => 'Kepala Desa',
            'urutan' => 2,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.profil.update'), $this->dataDasar([
                'riwayat' => [
                    $pertama->id => [
                        'label' => 'Cuhli',
                        'nilai' => '1998 - 2004',
                        'keterangan' => 'Kepala Desa Pertama',
                    ],
                    'baru_0' => [
                        'label' => 'R O M S I, SH',
                        'nilai' => '2022 - sekarang',
                        'keterangan' => 'Kepala Desa',
                    ],
                ],
                'riwayat_hapus' => [$kedua->id],
            ]))
            ->assertRedirect(route('admin.profil.edit'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('data_desas', [
            'id' => $pertama->id,
            'label' => 'Cuhli',
            'nilai' => '1998 - 2004',
        ]);
        $this->assertDatabaseMissing('data_desas', ['id' => $kedua->id]);

        $baru = DataDesa::query()
            ->kelompok(DataDesa::RIWAYAT_KADES)
            ->where('label', 'R O M S I, SH')
            ->firstOrFail();

        $this->assertSame('2022 - sekarang', $baru->nilai);
        $this->assertSame('Kepala Desa', $baru->keterangan);
        $this->assertGreaterThan($pertama->urutan, $baru->urutan);

        // Baris baru langsung tampil di halaman profil publik.
        $this->get(route('profil'))
            ->assertOk()
            ->assertSee('Cuhli')
            ->assertSee('R O M S I, SH')
            ->assertDontSee('Nama Lama');
    }

    public function test_riwayat_tanpa_nama_ditolak_saat_kolom_lain_diisi(): void
    {
        DataDesa::query()->create([
            'kelompok' => DataDesa::RIWAYAT_KADES,
            'label' => 'Cuhli',
            'nilai' => '1998 - 2004',
            'urutan' => 1,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.profil.update'), $this->dataDasar([
                'riwayat' => [
                    'baru_0' => [
                        'label' => '',
                        'nilai' => '2022 - sekarang',
                        'keterangan' => 'Kepala Desa',
                    ],
                ],
            ]))
            ->assertSessionHasErrors('riwayat');

        // Baris yang tidak valid tidak boleh menambah data tanpa nama.
        $this->assertSame(
            1,
            DataDesa::query()->kelompok(DataDesa::RIWAYAT_KADES)->count()
        );
    }

    public function test_form_riwayat_menampilkan_tombol_tambah_dan_hapus(): void
    {
        $baris = DataDesa::query()->create([
            'kelompok' => DataDesa::RIWAYAT_KADES,
            'label' => 'Cuhli',
            'nilai' => '1998 - 2004',
            'keterangan' => 'Kepala Desa Pertama',
            'urutan' => 1,
        ]);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.profil.edit'))
            ->assertOk()
            ->assertSee('data-tambah-riwayat', false)
            ->assertSee('data-riwayat-editor', false)
            ->assertSee('data-hapus-riwayat', false)
            ->assertSee('id="riwayat-template"', false)
            ->getContent();

        $this->assertStringContainsString(
            'name="riwayat['.$baris->id.'][label]"',
            $html
        );
        $this->assertStringContainsString('value="Cuhli"', $html);
        $this->assertStringContainsString('riwayat_hapus[]', $html);
    }
}
