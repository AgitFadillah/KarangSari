<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Models\Berita;
use App\Models\JenisSurat;
use App\Models\PerangkatDesa;
use App\Models\PotensiDesa;
use App\Models\ProfilDesa;
use App\Models\StatistikDesa;
use App\Models\User;
use App\Support\TeksTampil;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\KontenWebsiteSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Support\SiteProfile;
use Tests\TestCase;

/**
 * Formulir admin tidak boleh mati (HTTP 500) saat sebuah kolom yang
 * divalidasi `string` dikirim sebagai array.
 *
 * Kenapa ini diuji terpisah?
 *
 * Validasi Laravel memang menolak array untuk aturan `string`, tetapi
 * halaman edit kemudian dirender ulang memakai `old()` yang berisi array.
 * Blade mencetak `{{ ... }}` lewat e() -> htmlspecialchars(), yang melempar
 * TypeError untuk array, sehingga pesan validasi yang ramah tidak pernah
 * sampai ke admin. Selain itu `(string) old(...)` pada pilihan status
 * memicu "Array to string conversion". Keduanya hanya terlihat pada jalur
 * gagal-validasi, yang jarang diuji.
 */
class FormArrayAmanTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([ProfilDesaSeeder::class, JenisSuratSeeder::class, KontenWebsiteSeeder::class]);
        SiteProfile::lupakan();

        $this->admin = User::create([
            'name' => 'Admin Uji',
            'username' => 'adminuji',
            'password' => Hash::make('sandi-uji-test-1234'),
            'role' => RoleAdmin::Administrator,
            'is_active' => true,
        ]);
    }

    /** Pastikan respons bukan 500 dan tidak memuat jejak TypeError. */
    protected function assertTidakMeledak(\Illuminate\Testing\TestResponse $res, string $konteks): void
    {
        $this->assertNotSame(500, $res->getStatusCode(), $konteks.' membuat server 500.');

        $isi = (string) $res->getContent();

        $this->assertStringNotContainsString('htmlspecialchars', $isi, $konteks.' memicu htmlspecialchars(array).');
        $this->assertStringNotContainsString('Array to string conversion', $isi, $konteks.' memicu Array to string conversion.');
        $this->assertStringNotContainsString('TypeError', $isi, $konteks.' memicu TypeError.');
    }

    public function test_profil_dengan_daftar_dusun_berupa_array_tidak_500(): void
    {
        $profil = ProfilDesa::query()->firstOrFail();

        $res = $this->actingAs($this->admin)->put(route('admin.profil.update'), [
            'nama_desa' => $profil->nama_desa,
            // Kolom ini divalidasi `string`; array harus ditolak dengan
            // pesan validasi, bukan mematikan halaman edit.
            'daftar_dusun' => ['Dusun I', 'Dusun II'],
            'iklim' => ['kemarau'],
        ]);

        $this->assertTidakMeledak($res, 'Simpan profil dengan daftar_dusun[]');
        $res->assertSessionHasErrors(['daftar_dusun', 'iklim']);
    }

    public function test_potensi_dengan_kolom_array_tidak_500(): void
    {
        $res = $this->actingAs($this->admin)->post(route('admin.potensi.store'), [
            'nama' => ['bukan teks'],
            'is_aktif' => ['1'],
        ]);

        $this->assertTidakMeledak($res, 'Simpan potensi dengan nama[]');
        $res->assertSessionHasErrors(['nama']);
    }

    public function test_statistik_dengan_kolom_array_tidak_500(): void
    {
        $res = $this->actingAs($this->admin)->post(route('admin.statistik.store'), [
            'label' => ['bukan teks'],
            'nilai' => ['1'],
            'is_aktif' => ['1'],
        ]);

        $this->assertTidakMeledak($res, 'Simpan statistik dengan label[]');
        $res->assertSessionHasErrors(['label']);
    }

    public function test_perangkat_dengan_kolom_array_tidak_500(): void
    {
        $res = $this->actingAs($this->admin)->post(route('admin.perangkat.store'), [
            'nama' => ['bukan teks'],
            'jabatan' => ['bukan teks'],
            'is_aktif' => ['1'],
        ]);

        $this->assertTidakMeledak($res, 'Simpan perangkat dengan nama[]');
        $res->assertSessionHasErrors(['nama', 'jabatan']);
    }

    public function test_berita_dengan_kolom_array_tidak_500(): void
    {
        $res = $this->actingAs($this->admin)->post(route('admin.berita.store'), [
            'judul' => ['bukan teks'],
            'kategori' => ['berita'],
            'isi' => ['bukan teks'],
        ]);

        $this->assertTidakMeledak($res, 'Simpan berita dengan judul[]');
        $res->assertSessionHasErrors(['judul']);
    }

    public function test_konten_dengan_kolom_array_tidak_500(): void
    {
        $konten = \App\Models\KontenWebsite::query()->firstOrFail();

        $res = $this->actingAs($this->admin)->put(route('admin.konten.update', $konten), [
            'judul' => ['bukan teks'],
            'status' => ['terbit'],
        ]);

        $this->assertTidakMeledak($res, 'Simpan konten dengan judul[]');
        $res->assertSessionHasErrors(['judul']);
    }

    public function test_jenis_surat_dengan_kolom_array_tidak_500(): void
    {
        $jenis = JenisSurat::query()->firstOrFail();

        $res = $this->actingAs($this->admin)->put(route('admin.jenis-surat.update', $jenis), [
            'kode' => ['SKD'],
            'nama' => ['bukan teks'],
            'is_aktif' => ['1'],
        ]);

        $this->assertTidakMeledak($res, 'Simpan jenis surat dengan nama[]');
        $res->assertSessionHasErrors(['nama']);
    }

    /**
     * Inti perbaikan: nilai array tidak pernah membuat e() melempar.
     *
     * Array dicetak sebagai teks gabungan (bukan "Array"), lalu tetap
     * di-escape. Untuk kolom satu-nilai, skalar() sengaja mengosongkannya.
     */
    public function test_teks_tampil_mengubah_array_menjadi_teks_aman(): void
    {
        $this->assertSame('b', TeksTampil::e(['a' => 'b']));
        $this->assertSame('Dusun I, Dusun II', TeksTampil::teks(['Dusun I', 'Dusun II']));
        $this->assertSame('', TeksTampil::skalar(['Dusun I']));
        $this->assertSame('teks biasa', TeksTampil::skalar('teks biasa'));
        $this->assertSame('1', TeksTampil::skalar(true));
        $this->assertSame('', TeksTampil::skalar(null));

        // Escaping tetap berjalan seperti e() bawaan.
        $this->assertSame('&lt;b&gt;', TeksTampil::e('<b>'));
        $this->assertSame('&lt;b&gt;', TeksTampil::e(['<b>']));
        $this->assertStringNotContainsString('Array', TeksTampil::e([['nested']]));
    }

    /**
     * `{{ }}` harus mencetak nilai array tanpa mematikan halaman, dan tetap
     * meng-escape HTML di dalamnya.
     */
    public function test_blade_mencetak_array_tanpa_melempar(): void
    {
        $html = \Illuminate\Support\Facades\Blade::render('{{ $nilai }}', ['nilai' => ['<script>x</script>']]);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertSame('&lt;script&gt;x&lt;/script&gt;', trim($html));
    }

    public function test_blade_tetap_meng_escape_string(): void
    {
        $html = \Illuminate\Support\Facades\Blade::render('{{ $nilai }}', ['nilai' => '<b>x</b>']);

        $this->assertSame('&lt;b&gt;x&lt;/b&gt;', trim($html));
    }
}
