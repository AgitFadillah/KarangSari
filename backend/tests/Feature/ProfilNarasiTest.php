<?php

namespace Tests\Feature;

use App\Models\ProfilDesa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Menyimpan "Narasi profil" (sejarah, visi, misi, batas wilayah, penggunaan
 * lahan) dari dashboard admin.
 *
 * Keluhan lapangan: perangkat desa mengisi lalu menekan simpan, tetapi isinya
 * kembali kosong saat halaman dibuka lagi. Tes ini mengunci perilaku yang
 * benar supaya tidak terulang.
 */
class ProfilNarasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Cache profil desa dipakai lintas permintaan; bersihkan agar tiap
        // tes mulai dari keadaan kosong.
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
            'kecamatan' => 'Cisayong',
            'kabupaten' => 'Tasikmalaya',
        ]);
    }

    /** Data HTTP minimal yang valid untuk update profil. */
    protected function dataDasar(array $tambahan = []): array
    {
        return array_merge([
            'nama_desa' => 'Desa KarangSari',
            'kecamatan' => 'Cisayong',
            'kabupaten' => 'Tasikmalaya',
            'provinsi' => 'Jawa Barat',
            'tahun_berdiri' => '1912',
        ], $tambahan);
    }

    public function test_narasi_profil_tersimpan_dan_terbaca_kembali(): void
    {
        $this->profilAwal();

        $this->actingAs($this->admin())
            ->put(route('admin.profil.update'), $this->dataDasar([
                'sejarah' => "Paragraf pertama sejarah.\n\nParagraf kedua sejarah.",
                'visi' => 'Menjadi desa yang mandiri dan sejahtera.',
                'misi' => "Meningkatkan pelayanan.\nMengembangkan pertanian.",
                'batas_wilayah' => "Utara: Desa Sukamaju\nSelatan: Desa Cibeureum",
                'penggunaan_lahan' => "Kebun: 45\nSawah: 55",
            ]))
            ->assertRedirect(route('admin.profil.edit'))
            ->assertSessionHas('status');

        $profil = ProfilDesa::query()->first();

        $this->assertSame("Paragraf pertama sejarah.\n\nParagraf kedua sejarah.", $profil->sejarah);
        $this->assertSame('Menjadi desa yang mandiri dan sejahtera.', $profil->visi);
        $this->assertSame("Meningkatkan pelayanan.\nMengembangkan pertanian.", $profil->misi);
        $this->assertSame("Utara: Desa Sukamaju\nSelatan: Desa Cibeureum", $profil->batas_wilayah);
        $this->assertSame("Kebun: 45\nSawah: 55", $profil->penggunaan_lahan);
        $this->assertSame('1912', $profil->tahun_berdiri);
    }

    public function test_narasi_tampil_lagi_di_form_setelah_disimpan(): void
    {
        $this->profilAwal();

        $this->actingAs($this->admin())
            ->put(route('admin.profil.update'), $this->dataDasar([
                'sejarah' => 'Sejarah yang baru saja disimpan.',
                'visi' => 'Visi yang baru saja disimpan.',
            ]));

        // Membuka form kembali harus memperlihatkan nilai tersimpan,
        // bukan kotak kosong.
        $this->actingAs($this->admin())
            ->get(route('admin.profil.edit'))
            ->assertOk()
            ->assertSee('Sejarah yang baru saja disimpan.')
            ->assertSee('Visi yang baru saja disimpan.');
    }

    public function test_narasi_tampil_di_halaman_profil_publik(): void
    {
        $this->profilAwal();

        $this->actingAs($this->admin())
            ->put(route('admin.profil.update'), $this->dataDasar([
                'sejarah' => 'Sejarah desa yang dapat dibaca warga.',
                'visi' => 'Visi desa untuk warga.',
                'misi' => "Butir misi pertama.\nButir misi kedua.",
            ]));

        $this->get('/profil')
            ->assertOk()
            ->assertSee('Sejarah desa yang dapat dibaca warga.')
            ->assertSee('Visi desa untuk warga.')
            ->assertSee('Butir misi pertama.')
            ->assertSee('Butir misi kedua.');
    }

    public function test_tahun_berdiri_tampil_pada_kartu_sejarah(): void
    {
        $this->profilAwal();

        $this->actingAs($this->admin())
            ->put(route('admin.profil.update'), $this->dataDasar(['tahun_berdiri' => '1908']));

        // Kartu "Berdiri" harus berisi tahun, bukan kosong seperti dulu.
        $this->get('/profil')
            ->assertOk()
            ->assertSee('Berdiri')
            ->assertSee('1908');
    }

    public function test_kartu_berdiri_disembunyikan_bila_tahun_kosong(): void
    {
        $this->profilAwal();   // tahun_berdiri sengaja tidak diisi

        $html = $this->get('/profil')->assertOk()->getContent();

        // Tidak boleh ada kartu berlabel "Berdiri" tanpa isi.
        $this->assertStringNotContainsString(
            '<div class="stat-mini-label">Berdiri</div>',
            $html
        );
    }

    public function test_narasi_boleh_dikosongkan(): void
    {
        $profil = $this->profilAwal();
        $profil->update(['sejarah' => 'Isi lama', 'visi' => 'Visi lama']);

        $this->actingAs($this->admin())
            ->put(route('admin.profil.update'), $this->dataDasar([
                'sejarah' => '',
                'visi' => '',
                'misi' => '',
            ]))
            ->assertSessionHasNoErrors();

        $profil->refresh();
        $this->assertSame('', (string) $profil->sejarah);
        $this->assertSame('', (string) $profil->visi);
    }

    public function test_logo_tetap_tersimpan_bersamaan_dengan_narasi(): void
    {
        Storage::fake('public');
        $this->profilAwal();

        $this->actingAs($this->admin())
            ->put(route('admin.profil.update'), $this->dataDasar([
                'sejarah' => 'Sejarah bersamaan dengan unggah logo.',
                'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
            ]));

        $profil = ProfilDesa::query()->first();
        $this->assertSame('Sejarah bersamaan dengan unggah logo.', $profil->sejarah);
        $this->assertNotNull($profil->logo_path);
        Storage::disk('public')->assertExists($profil->logo_path);
    }
}
