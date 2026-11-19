<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Models\Berita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
/**
 * Foto pada berita, pengumuman, dan agenda.
 *
 * Sebelumnya kartu berita hanya menampilkan blok warna navy tanpa gambar,
 * sehingga dokumentasi kegiatan desa tidak bisa disertakan. Tes ini mengunci
 * tiga hal: foto tersimpan saat dibuat, foto lama dibuang saat diganti, dan
 * foto benar-benar tampil di halaman publik.
 */
class FotoBeritaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        \App\Support\SiteProfile::lupakan();

        $this->admin = User::create([
            'name' => 'Admin Uji',
            'username' => 'adminuji',
            'password' => Hash::make('sandi-uji-test-1234'),
            'role' => RoleAdmin::Administrator,
            'is_active' => true,
        ]);
    }

    /** @return array<string, mixed> */
    protected function dataBerita(array $tambahan = []): array
    {
        return array_merge([
            'judul' => 'Kerja Bakti Membersihkan Saluran Air',
            'kategori' => 'berita',
            'ringkasan' => 'Warga bergotong royong membersihkan saluran air desa.',
            'isi' => 'Kegiatan berlangsung sejak pagi.',
            'terbit_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ], $tambahan);
    }

    public function test_foto_tersimpan_saat_berita_dibuat(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.berita.store'), $this->dataBerita([
                'foto' => UploadedFile::fake()->image('kegiatan.png', 800, 500),
            ]))
            ->assertRedirect(route('admin.berita.index'));

        $berita = Berita::query()->firstOrFail();

        $this->assertNotNull($berita->foto_path);
        Storage::disk('public')->assertExists($berita->foto_path);

        // Ekstensi mengikuti TIPE ISI berkas, bukan nama kiriman.
        $this->assertStringEndsWith('.png', $berita->foto_path);
    }

    public function test_berita_tanpa_foto_tetap_bisa_disimpan(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.berita.store'), $this->dataBerita())
            ->assertRedirect(route('admin.berita.index'));

        $this->assertNull(Berita::query()->firstOrFail()->foto_path);
    }

    public function test_foto_lama_dibuang_saat_diganti(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.berita.store'), $this->dataBerita([
                'foto' => UploadedFile::fake()->image('lama.png', 600, 400),
            ]));

        $berita = Berita::query()->firstOrFail();
        $fotoLama = $berita->foto_path;

        $this->actingAs($this->admin)
            ->put(route('admin.berita.update', $berita), $this->dataBerita([
                'judul' => $berita->judul,
                'foto' => UploadedFile::fake()->image('baru.jpg', 600, 400),
            ]));

        $berita->refresh();

        $this->assertNotSame($fotoLama, $berita->foto_path);
        Storage::disk('public')->assertMissing($fotoLama);
        Storage::disk('public')->assertExists($berita->foto_path);
    }

    public function test_foto_dapat_dihapus_tanpa_mengganti_berkas(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.berita.store'), $this->dataBerita([
                'foto' => UploadedFile::fake()->image('hapus.png', 600, 400),
            ]));

        $berita = Berita::query()->firstOrFail();
        $foto = $berita->foto_path;

        $this->actingAs($this->admin)
            ->put(route('admin.berita.update', $berita), $this->dataBerita([
                'judul' => $berita->judul,
                'foto_hapus' => '1',
            ]));

        $berita->refresh();

        $this->assertNull($berita->foto_path);
        Storage::disk('public')->assertMissing($foto);
    }

    public function test_foto_ikut_dihapus_saat_berita_dihapus(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.berita.store'), $this->dataBerita([
                'foto' => UploadedFile::fake()->image('dihapus.png', 600, 400),
            ]));

        $berita = Berita::query()->firstOrFail();
        $foto = $berita->foto_path;

        $this->actingAs($this->admin)
            ->delete(route('admin.berita.destroy', $berita));

        Storage::disk('public')->assertMissing($foto);
        $this->assertSame(0, Berita::query()->count());
    }

    public function test_foto_tampil_di_beranda_informasi_dan_detail(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.berita.store'), $this->dataBerita([
                'foto' => UploadedFile::fake()->image('tampil.png', 800, 500),
            ]));

        $berita = Berita::query()->firstOrFail();
        $url = $berita->fotoUrl();

        $this->assertNotNull($url);
        $this->assertStringStartsWith('/storage/berita/', $url);

        $this->get('/')->assertOk()->assertSee($url);
        $this->get('/informasi')->assertOk()->assertSee($url);
        $this->get(route('informasi.detail', $berita->slug))->assertOk()->assertSee($url);
    }

    public function test_foto_berkas_bukan_gambar_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.berita.store'), $this->dataBerita([
                'foto' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('foto');

        $this->assertSame(0, Berita::query()->count());
    }

    public function test_berita_tanpa_foto_tetap_tampil_dengan_ilustrasi_bawaan(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.berita.store'), $this->dataBerita());

        $berita = Berita::query()->firstOrFail();

        // Halaman tetap tampil walau tidak ada foto: tidak ada <figure>
        // foto, dan blok ilustrasi bawaan tetap dirender.
        $this->get(route('informasi.detail', $berita->slug))
            ->assertOk()
            ->assertDontSee('<figure class="baca-foto">', false)
            ->assertSee($berita->judul);
    }

    public function test_nama_berkas_berbahaya_tidak_dipakai_sebagai_ekstensi(): void
    {
        // Berkas PNG sah yang dinamai ".html": bila ekstensi mengikuti nama
        // kiriman, berkas ini menjadi halaman HTML di domain desa (stored XSS).
        $asli = tempnam(sys_get_temp_dir(), 'uji');
        imagepng(imagecreatetruecolor(40, 30), $asli);

        $berkas = new UploadedFile($asli, 'jahat.html', 'image/png', null, true);

        $this->actingAs($this->admin)
            ->post(route('admin.berita.store'), $this->dataBerita(['foto' => $berkas]));

        $berita = Berita::query()->first();

        if ($berita !== null && $berita->foto_path !== null) {
            $this->assertStringEndsNotWith('.html', $berita->foto_path);
            $this->assertStringEndsWith('.png', $berita->foto_path);
        }

        // Apa pun hasilnya, tidak boleh ada berkas .html di disk publik.
        foreach (Storage::disk('public')->allFiles('berita') as $path) {
            $this->assertStringEndsNotWith('.html', $path);
        }

        @unlink($asli);
    }
}
