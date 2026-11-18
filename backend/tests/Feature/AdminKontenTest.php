<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Models\Berita;
use App\Models\PerangkatDesa;
use App\Models\PotensiDesa;
use App\Models\StatistikDesa;
use App\Models\User;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * CRUD konten desa dari dashboard admin: berita, potensi, statistik, dan
 * perangkat desa. Semua data yang dulu ditulis di Blade kini dikelola di sini.
 */
class AdminKontenTest extends TestCase
{
    use RefreshDatabase;

    protected const SANDI = 'rahasia-uji-123';

    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProfilDesaSeeder::class, JenisSuratSeeder::class]);

        $this->operator = User::create([
            'name' => 'Operator Konten',
            'username' => 'operator',
            'role' => RoleAdmin::OperatorKonten,
            'is_active' => true,
            'password' => Hash::make(self::SANDI),
        ]);
    }

    public function test_operator_dapat_membuat_berita_dan_slug_dibuat_otomatis(): void
    {
        $this->actingAs($this->operator)
            ->post(route('admin.berita.store'), [
                'judul' => 'Musyawarah Desa RKPDes',
                'kategori' => 'berita',
                'ringkasan' => 'Hasil musyawarah desa.',
                'terbit_at' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('admin.berita.index'));

        $berita = Berita::query()->firstOrFail();
        $this->assertSame('musyawarah-desa-rkpdes', $berita->slug);
        $this->assertSame($this->operator->id, $berita->dibuat_oleh);
        $this->assertDatabaseHas('log_aktivitets', ['tindakan' => 'Menambahkan berita']);
    }

    public function test_judul_kembar_tetap_mendapat_slug_unik(): void
    {
        foreach (range(1, 2) as $_) {
            $this->actingAs($this->operator)->post(route('admin.berita.store'), [
                'judul' => 'Judul Sama',
                'kategori' => 'berita',
            ]);
        }

        $slug = Berita::query()->orderBy('id')->pluck('slug')->all();

        $this->assertSame(['judul-sama', 'judul-sama-2'], $slug);
    }

    public function test_berita_tanpa_tanggal_terbit_tersimpan_sebagai_draf(): void
    {
        $this->actingAs($this->operator)->post(route('admin.berita.store'), [
            'judul' => 'Draf Pengumuman',
            'kategori' => 'pengumuman',
        ]);

        $berita = Berita::query()->firstOrFail();

        $this->assertNull($berita->terbit_at);
        $this->assertFalse($berita->isTerbit());
        $this->assertSame('Draf', $berita->labelStatus());
    }

    public function test_kategori_tidak_dikenal_ditolak(): void
    {
        $this->actingAs($this->operator)
            ->post(route('admin.berita.store'), [
                'judul' => 'Berita Uji',
                'kategori' => 'kategori-palsu',
            ])
            ->assertSessionHasErrors('kategori');

        $this->assertSame(0, Berita::query()->count());
    }

    public function test_operator_dapat_mengubah_dan_menghapus_berita(): void
    {
        $berita = Berita::create([
            'judul' => 'Judul Awal',
            'slug' => 'judul-awal',
            'kategori' => 'berita',
        ]);

        // Judul berubah -> slug ikut diperbarui.
        $this->actingAs($this->operator)
            ->put(route('admin.berita.update', $berita), [
                'judul' => 'Judul Baru',
                'kategori' => 'agenda',
            ])
            ->assertRedirect(route('admin.berita.index'));

        $berita->refresh();
        $this->assertSame('judul-baru', $berita->slug);
        $this->assertSame('agenda', $berita->kategori);

        $this->actingAs($this->operator)
            ->delete(route('admin.berita.destroy', $berita))
            ->assertRedirect(route('admin.berita.index'));

        $this->assertSame(0, Berita::query()->count());
    }

    public function test_slug_tidak_berubah_bila_judul_tetap(): void
    {
        $berita = Berita::create([
            'judul' => 'Judul Tetap',
            'slug' => 'slug-khusus',
            'kategori' => 'berita',
        ]);

        $this->actingAs($this->operator)->put(route('admin.berita.update', $berita), [
            'judul' => 'Judul Tetap',
            'kategori' => 'berita',
            'ringkasan' => 'Ringkasan baru.',
        ]);

        $this->assertSame('slug-khusus', $berita->fresh()->slug);
    }

    public function test_operator_dapat_mengelola_potensi_desa(): void
    {
        $this->actingAs($this->operator)
            ->post(route('admin.potensi.store'), [
                'nama' => 'Kopi Lereng Puncak',
                'kategori' => 'Pertanian',
                'deskripsi' => 'Kopi arabika warga.',
                'kontak' => 'Kelompok Tani Lereng',
                'urutan' => 2,
                'is_aktif' => '1',
            ])
            ->assertRedirect(route('admin.potensi.index'));

        $potensi = PotensiDesa::query()->firstOrFail();
        $this->assertSame('Kopi Lereng Puncak', $potensi->nama);
        $this->assertSame(2, $potensi->urutan);
        $this->assertTrue($potensi->is_aktif);
    }

    public function test_potensi_tanpa_nama_ditolak(): void
    {
        $this->actingAs($this->operator)
            ->post(route('admin.potensi.store'), ['nama' => ''])
            ->assertSessionHasErrors('nama');
    }

    public function test_potensi_dapat_disimpan_sebagai_nonaktif(): void
    {
        $this->actingAs($this->operator)
            ->post(route('admin.potensi.store'), [
                'nama' => 'Potensi Belum Tayang',
                'is_aktif' => '0',
            ])
            ->assertRedirect(route('admin.potensi.index'));

        $this->assertFalse(PotensiDesa::query()->firstOrFail()->is_aktif);
    }

    public function test_operator_dapat_mengelola_statistik_desa(): void
    {
        $this->actingAs($this->operator)
            ->post(route('admin.statistik.store'), [
                'label' => 'Penduduk',
                'nilai' => '5123',
                'satuan' => 'jiwa',
                'urutan' => 1,
                'is_aktif' => '1',
            ])
            ->assertRedirect(route('admin.statistik.index'));

        $this->assertDatabaseHas('statistik_desas', ['label' => 'Penduduk', 'nilai' => '5123']);
    }

    public function test_operator_dapat_mengelola_perangkat_desa(): void
    {
        $this->actingAs($this->operator)
            ->post(route('admin.perangkat.store'), [
                'nama' => 'Budi Santoso',
                'jabatan' => 'Kepala Desa',
                'urutan' => 1,
                'is_aktif' => '1',
            ])
            ->assertRedirect(route('admin.perangkat.index'));

        $this->assertSame(1, PerangkatDesa::query()->count());

        $perangkat = PerangkatDesa::query()->firstOrFail();
        $this->assertSame('Kepala Desa', $perangkat->jabatan);
    }

    public function test_verifikator_tidak_boleh_mengubah_konten(): void
    {
        $verifikator = User::create([
            'name' => 'Verifikator',
            'username' => 'verifikator',
            'role' => RoleAdmin::Verifikator,
            'is_active' => true,
            'password' => Hash::make(self::SANDI),
        ]);

        $this->actingAs($verifikator)
            ->post(route('admin.berita.store'), ['judul' => 'Coba', 'kategori' => 'berita'])
            ->assertForbidden();

        $this->actingAs($verifikator)
            ->post(route('admin.potensi.store'), ['nama' => 'Coba'])
            ->assertForbidden();

        $this->assertSame(0, Berita::query()->count());
        $this->assertSame(0, PotensiDesa::query()->count());
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get(route('admin.berita.index'))->assertRedirect(route('admin.login'));
    }

    public function test_berita_dapat_disorot_dan_hanya_satu_sorotan(): void
    {
        $this->actingAs($this->operator)->post(route('admin.berita.store'), [
            'judul' => 'Sorotan Lama',
            'kategori' => 'berita',
            'is_highlight' => '1',
            'terbit_at' => now()->subDay()->format('Y-m-d\TH:i'),
        ]);

        $this->actingAs($this->operator)->post(route('admin.berita.store'), [
            'judul' => 'Sorotan Baru',
            'kategori' => 'berita',
            'is_highlight' => '1',
            'terbit_at' => now()->format('Y-m-d\TH:i'),
        ]);

        // Hanya berita terakhir yang tetap disorot.
        $this->assertSame(1, Berita::query()->where('is_highlight', true)->count());
        $this->assertSame('Sorotan Baru', Berita::query()->where('is_highlight', true)->value('judul'));
    }

    public function test_sorotan_tampil_di_paling_atas_halaman_informasi(): void
    {
        Berita::create([
            'judul' => 'Banner Sorotan Desa',
            'slug' => 'banner-sorotan-desa',
            'kategori' => 'berita',
            'ringkasan' => 'Ringkasan sorotan.',
            'is_highlight' => true,
            'terbit_at' => now(),
        ]);

        $this->get(route('informasi'))
            ->assertOk()
            ->assertSee('Banner Sorotan Desa')
            ->assertSee('Sorotan');
    }

    public function test_halaman_panduan_admin_dapat_dibuka(): void
    {
        $this->actingAs($this->operator)
            ->get(route('admin.panduan'))
            ->assertOk()
            ->assertSee('Alur status pengajuan')
            ->assertSee('Verifikasi dokumen')
            ->assertSee('Menulis template surat');
    }

    public function test_perangkat_desa_tampil_di_halaman_profil_publik(): void
    {
        PerangkatDesa::create([
            'nama' => 'Sari Wulandari',
            'jabatan' => 'Sekretaris Desa',
            'urutan' => 1,
            'is_aktif' => true,
        ]);

        $this->get(route('profil'))
            ->assertOk()
            ->assertSee('Sari Wulandari')
            ->assertSee('Sekretaris Desa');
    }

    public function test_foto_perangkat_dapat_diunggah_dan_tampil_di_profil(): void
    {
        Storage::fake('public');

        $this->actingAs($this->operator)
            ->post(route('admin.perangkat.store'), [
                'nama' => 'Budi Santoso',
                'jabatan' => 'Kepala Desa',
                'urutan' => 1,
                'is_aktif' => '1',
                'foto' => UploadedFile::fake()->image('kades.jpg', 400, 400),
            ])
            ->assertRedirect(route('admin.perangkat.index'));

        $perangkat = PerangkatDesa::query()->firstOrFail();
        $this->assertNotNull($perangkat->foto_path);
        $this->assertStringStartsWith('perangkat/', $perangkat->foto_path);
        Storage::disk('public')->assertExists($perangkat->foto_path);

        // Foto disimpan di disk public karena memang untuk halaman publik.
        $this->get(route('profil'))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($perangkat->foto_path), false);
    }

    public function test_foto_lama_dihapus_saat_diganti(): void
    {
        Storage::fake('public');

        $lama = UploadedFile::fake()->image('lama.jpg');
        $this->actingAs($this->operator)->post(route('admin.perangkat.store'), [
            'nama' => 'Budi Santoso',
            'jabatan' => 'Kepala Desa',
            'is_aktif' => '1',
            'foto' => $lama,
        ]);

        $perangkat = PerangkatDesa::query()->firstOrFail();
        $pathLama = $perangkat->foto_path;

        $this->actingAs($this->operator)
            ->put(route('admin.perangkat.update', $perangkat), [
                'nama' => 'Budi Santoso',
                'jabatan' => 'Kepala Desa',
                'is_aktif' => '1',
                'foto' => UploadedFile::fake()->image('baru.jpg'),
            ])
            ->assertRedirect(route('admin.perangkat.index'));

        $perangkat->refresh();
        $this->assertNotSame($pathLama, $perangkat->foto_path);
        Storage::disk('public')->assertMissing($pathLama);
        Storage::disk('public')->assertExists($perangkat->foto_path);
    }

    public function test_foto_dapat_dihapus_tanpa_mengganti(): void
    {
        Storage::fake('public');

        $this->actingAs($this->operator)->post(route('admin.perangkat.store'), [
            'nama' => 'Budi Santoso',
            'jabatan' => 'Kepala Desa',
            'is_aktif' => '1',
            'foto' => UploadedFile::fake()->image('kades.jpg'),
        ]);

        $perangkat = PerangkatDesa::query()->firstOrFail();
        $path = $perangkat->foto_path;

        $this->actingAs($this->operator)
            ->put(route('admin.perangkat.update', $perangkat), [
                'nama' => 'Budi Santoso',
                'jabatan' => 'Kepala Desa',
                'is_aktif' => '1',
                'hapus_foto' => '1',
            ])
            ->assertRedirect(route('admin.perangkat.index'));

        $this->assertNull($perangkat->refresh()->foto_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_foto_bukan_gambar_ditolak(): void
    {
        Storage::fake('public');

        $this->actingAs($this->operator)
            ->post(route('admin.perangkat.store'), [
                'nama' => 'Budi Santoso',
                'jabatan' => 'Kepala Desa',
                'is_aktif' => '1',
                'foto' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('foto');

        $this->assertSame(0, PerangkatDesa::query()->count());
    }

    public function test_foto_dihapus_saat_perangkat_dihapus(): void
    {
        Storage::fake('public');

        $this->actingAs($this->operator)->post(route('admin.perangkat.store'), [
            'nama' => 'Budi Santoso',
            'jabatan' => 'Kepala Desa',
            'is_aktif' => '1',
            'foto' => UploadedFile::fake()->image('kades.jpg'),
        ]);

        $perangkat = PerangkatDesa::query()->firstOrFail();
        $path = $perangkat->foto_path;

        $this->actingAs($this->operator)
            ->delete(route('admin.perangkat.destroy', $perangkat))
            ->assertRedirect(route('admin.perangkat.index'));

        $this->assertSame(0, PerangkatDesa::query()->count());
        Storage::disk('public')->assertMissing($path);
    }

    public function test_form_perangkat_memakai_enctype_multipart(): void
    {
        $this->actingAs($this->operator)
            ->get(route('admin.perangkat.create'))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="foto"', false);
    }
}
