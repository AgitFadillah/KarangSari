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
use App\Support\SiteProfile;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Halaman publik harus tetap dapat dirender meski data konten kosong
 * (kondisi awal setelah instalasi) maupun terisi.
 *
 * Test ini menangkap error Blade yang tidak terlihat oleh test unit biasa.
 */
class HalamanPublikTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProfilDesaSeeder::class, JenisSuratSeeder::class]);

        // SiteProfile menyimpan profil di cache statis per proses; tanpa
        // dibersihkan, perubahan data profil di dalam test tidak terbaca.
        SiteProfile::lupakan();
    }

    public function test_halaman_publik_dirender_tanpa_data_konten(): void
    {
        // Kondisi awal: belum ada berita, potensi, statistik, atau perangkat.
        $this->assertSame(0, Berita::query()->count());

        foreach (['/', '/profil', '/informasi', '/potensi', '/kontak', '/pelayanan', '/cek-status'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_beranda_menampilkan_statistik_dan_berita_dari_database(): void
    {
        StatistikDesa::create(['label' => 'Penduduk', 'nilai' => '5123', 'satuan' => 'jiwa', 'urutan' => 1]);
        Berita::create([
            'judul' => 'Kerja Bakti Bersih Sungai',
            'slug' => 'kerja-bakti-bersih-sungai',
            'kategori' => 'berita',
            'ringkasan' => 'Warga bergotong royong membersihkan sungai.',
            'terbit_at' => now()->subDay(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Penduduk')
            ->assertSee('5123')
            ->assertSee('Kerja Bakti Bersih Sungai');
    }

    public function test_berita_draf_tidak_tampil_di_halaman_informasi(): void
    {
        Berita::create([
            'judul' => 'Berita Sudah Terbit',
            'slug' => 'berita-sudah-terbit',
            'kategori' => 'berita',
            'terbit_at' => now()->subDay(),
        ]);

        Berita::create([
            'judul' => 'Berita Masih Draf',
            'slug' => 'berita-masih-draf',
            'kategori' => 'berita',
            'terbit_at' => null,
        ]);

        $this->get('/informasi')
            ->assertOk()
            ->assertSee('Berita Sudah Terbit')
            ->assertDontSee('Berita Masih Draf');
    }

    public function test_potensi_hanya_menampilkan_yang_aktif(): void
    {
        PotensiDesa::create(['nama' => 'Kopi Lereng', 'kategori' => 'Pertanian', 'deskripsi' => 'Kopi arabika.', 'is_aktif' => true]);
        PotensiDesa::create(['nama' => 'Potensi Nonaktif', 'kategori' => 'Pertanian', 'deskripsi' => 'Tidak tampil.', 'is_aktif' => false]);

        $this->get('/potensi')
            ->assertOk()
            ->assertSee('Kopi Lereng')
            ->assertDontSee('Potensi Nonaktif');
    }

    public function test_profil_menampilkan_perangkat_desa_dari_database(): void
    {
        PerangkatDesa::create(['nama' => 'Budi Santoso', 'jabatan' => 'Kepala Desa', 'urutan' => 1, 'is_aktif' => true]);

        $this->get('/profil')
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Kepala Desa');
    }

    public function test_footer_memakai_jenis_surat_yang_benar_benar_ada(): void
    {
        // Jenis surat dari seeder punya slug yang dipakai footer; memastikan
        // halaman tidak error saat membangun tautan di footer.
        $this->assertGreaterThan(0, JenisSurat::query()->aktif()->count());

        $this->get('/')->assertOk()->assertSee('Katalog Semua Surat');
    }

    public function test_halaman_admin_konten_dapat_dibuka_operator(): void
    {
        $operator = User::create([
            'name' => 'Operator Konten',
            'username' => 'operator-konten',
            'role' => RoleAdmin::OperatorKonten,
            'is_active' => true,
            'password' => Hash::make('rahasia123'),
        ]);

        foreach ([
            '/admin/berita',
            '/admin/berita/create',
            '/admin/potensi',
            '/admin/potensi/create',
            '/admin/statistik',
            '/admin/statistik/create',
            '/admin/perangkat',
            '/admin/perangkat/create',
        ] as $url) {
            $this->actingAs($operator)->get($url)->assertOk();
        }
    }

    public function test_verifikator_tidak_boleh_membuka_konten(): void
    {
        $verifikator = User::create([
            'name' => 'Verifikator',
            'username' => 'verifikator-uji',
            'role' => RoleAdmin::Verifikator,
            'is_active' => true,
            'password' => Hash::make('rahasia123'),
        ]);

        $this->actingAs($verifikator)->get('/admin/berita')->assertForbidden();
    }

    /** Tiga terbitan, satu per kategori, untuk menguji pil penyaring. */
    protected function tigaKategori(): void
    {
        foreach ([
            ['judul' => 'Berita Kerja Bakti', 'slug' => 'berita-kerja-bakti', 'kategori' => 'berita'],
            ['judul' => 'Agenda Musyawarah Desa', 'slug' => 'agenda-musyawarah-desa', 'kategori' => 'agenda'],
            ['judul' => 'Pengumuman Posyandu', 'slug' => 'pengumuman-posyandu', 'kategori' => 'pengumuman'],
        ] as $i => $data) {
            Berita::create($data + [
                'ringkasan' => 'Ringkasan '.$data['judul'],
                'terbit_at' => now()->subDays($i),
            ]);
        }
    }

    public function test_pil_kategori_informasi_menyaring_isi_halaman(): void
    {
        $this->tigaKategori();

        // Tanpa filter: ketiga kategori tampil.
        $this->get('/informasi')
            ->assertOk()
            ->assertSee('Berita Kerja Bakti')
            ->assertSee('Agenda Musyawarah Desa')
            ->assertSee('Pengumuman Posyandu');

        // Pil "Berita": hanya berita, agenda & pengumuman disembunyikan.
        $this->get('/informasi?kategori=berita')
            ->assertOk()
            ->assertSee('Berita Kerja Bakti')
            ->assertDontSee('Agenda Musyawarah Desa')
            ->assertDontSee('Pengumuman Posyandu')
            ->assertSee('data-filter="berita"', false);

        // Pil "Agenda".
        $this->get('/informasi?kategori=agenda')
            ->assertOk()
            ->assertSee('Agenda Musyawarah Desa')
            ->assertDontSee('Berita Kerja Bakti')
            ->assertDontSee('Pengumuman Posyandu');

        // Pil "Pengumuman".
        $this->get('/informasi?kategori=pengumuman')
            ->assertOk()
            ->assertSee('Pengumuman Posyandu')
            ->assertDontSee('Berita Kerja Bakti')
            ->assertDontSee('Agenda Musyawarah Desa');
    }

    public function test_pil_kategori_informasi_menandai_pil_aktif(): void
    {
        $this->tigaKategori();

        // Hanya pil yang sedang dipilih yang berkelas "active" dan
        // diberi aria-current, agar tampilannya tidak membingungkan.
        $html = $this->get('/informasi?kategori=agenda')->assertOk()->getContent();

        // Ambil tag <a> milik tiap pil, lalu pastikan hanya pil aktif yang
        // memakai kelas "active".
        $this->assertMatchesRegularExpression(
            '/<a\b[^>]*class="tab active"[^>]*data-filter="agenda"/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<a\b[^>]*class="tab active"[^>]*data-filter="berita"/',
            $html
        );
    }

    public function test_kategori_informasi_kosong_menampilkan_pemberitahuan(): void
    {
        $this->tigaKategori();
        Berita::query()->where('kategori', 'agenda')->delete();

        $this->get('/informasi?kategori=agenda')
            ->assertOk()
            ->assertSee('Belum ada Agenda yang diterbitkan')
            ->assertSee('Tampilkan semua informasi')
            ->assertDontSee('Berita Kerja Bakti');
    }

    public function test_kategori_informasi_tidak_dikenal_kembali_ke_semua(): void
    {
        $this->tigaKategori();

        $this->get('/informasi?kategori=ngawur')
            ->assertOk()
            ->assertSee('Berita Kerja Bakti')
            ->assertSee('Agenda Musyawarah Desa')
            ->assertSee('Pengumuman Posyandu');

        // Pil "Semua" tetap ditandai aktif.
        $html = $this->get('/informasi?kategori=ngawur')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<a\b[^>]*class="tab active"[^>]*data-filter="semua"/', $html);
    }

    /**
     * Berita sorotan tampil sebagai banner besar. Sebelumnya berita yang sama
     * juga muncul lagi di blok "Berita Utama" sehingga judulnya terbaca dua
     * kali di satu halaman.
     */
    public function test_berita_sorotan_tidak_tampil_dua_kali(): void
    {
        Berita::create([
            'judul' => 'Sorotan Tunggal',
            'slug' => 'sorotan-tunggal',
            'kategori' => 'berita',
            'ringkasan' => 'Ringkasan sorotan.',
            'is_sorotan' => true,
            'terbit_at' => now()->subDay(),
        ]);

        $html = $this->get('/informasi')->assertOk()->getContent();

        $this->assertSame(
            1,
            substr_count($html, 'Sorotan Tunggal'),
            'Judul berita sorotan seharusnya muncul tepat satu kali di halaman.'
        );
    }

    /** Satu pengumuman saja: kartunya melebar penuh, bukan separuh halaman. */
    public function test_pengumuman_tunggal_memakai_kartu_lebar_penuh(): void
    {
        Berita::create([
            'judul' => 'Pengumuman Sendirian',
            'slug' => 'pengumuman-sendirian',
            'kategori' => 'pengumuman',
            'ringkasan' => 'Isi pengumuman.',
            'terbit_at' => now()->subDay(),
        ]);

        $html = $this->get('/informasi?kategori=pengumuman')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/class="two-col info-satu"/', $html);
    }

    /**
     * Kartu "Jam pelayanan" di halaman kontak dulu ditulis mati sehingga
     * selalu menampilkan "Sabtu–Minggu: Tutup" walau kantor buka setiap hari.
     * Sekarang isinya harus mengikuti data di menu Profil & Kontak.
     */
    public function test_kartu_jam_pelayanan_mengikuti_data_profil(): void
    {
        ProfilDesa::query()->firstOrFail()->update([
            'jam_layanan' => '08.00 - 17.00',
            'jam_kunjungan' => 'Senin - Minggu',
        ]);

        $this->get('/kontak')
            ->assertOk()
            ->assertSee('Senin - Minggu')
            ->assertSee('08.00 - 17.00')
            ->assertDontSee('Sabtu–Minggu');
    }

    /** Rincian per hari (termasuk hari libur) dipakai bila diisi petugas. */
    public function test_kartu_jam_pelayanan_menampilkan_rincian_harian(): void
    {
        ProfilDesa::query()->firstOrFail()->update([
            'jam_layanan' => '08.00 - 17.00',
            'jam_kunjungan' => 'Senin - Jumat 08.00 - 15.00, Sabtu - Minggu Tutup',
        ]);

        $this->get('/kontak')
            ->assertOk()
            ->assertSee('Senin - Jumat')
            ->assertSee('Sabtu - Minggu')
            ->assertSee('Tutup');
    }

    /** Tanpa data jam, kartu memberi keterangan, bukan baris kosong. */
    public function test_kartu_jam_pelayanan_memberi_keterangan_bila_kosong(): void
    {
        ProfilDesa::query()->firstOrFail()->update([
            'jam_layanan' => null,
            'jam_kunjungan' => null,
        ]);

        $this->get('/kontak')
            ->assertOk()
            ->assertSee('Jam pelayanan belum diisi.');
    }

    /**
     * Beranda: bila hanya ada satu berita, kartu sorotan melebar penuh dan
     * kolom samping tidak dirender. Sebelumnya kolom kanan kosong dan tautan
     * arsip melayang jauh di bawah kartu.
     */
    public function test_beranda_satu_berita_memakai_kartu_lebar_penuh(): void
    {
        Berita::create([
            'judul' => 'Satu-satunya Kabar',
            'slug' => 'satu-satunya-kabar',
            'kategori' => 'berita',
            'ringkasan' => 'Ringkasan kabar.',
            'terbit_at' => now()->subDay(),
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/class="news-grid news-grid-tunggal"/', $html);
        $this->assertStringNotContainsString('Lihat arsip informasi', $html);
        $this->assertSame(1, substr_count($html, 'Satu-satunya Kabar'));
        // Judul tidak lagi ditempeli nama desa.
        $this->assertStringNotContainsString('Satu-satunya Kabar —', $html);
    }

    /** Bila ada berita lain, kolom samping tampil seperti biasa. */
    public function test_beranda_banyak_berita_menampilkan_kolom_samping(): void
    {
        foreach ([['Kabar Pertama', 'kabar-pertama'], ['Kabar Kedua', 'kabar-kedua']] as $i => [$judul, $slug]) {
            Berita::create([
                'judul' => $judul,
                'slug' => $slug,
                'kategori' => 'berita',
                'ringkasan' => 'Ringkasan.',
                'terbit_at' => now()->subDays($i + 1),
            ]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('news-grid-tunggal', $html);
        $this->assertStringContainsString('Lihat arsip informasi', $html);
        $this->assertStringContainsString('Kabar Kedua', $html);
    }

    /** Tanpa satu pun berita, seksi informasi tidak dirender setengah kosong. */
    public function test_beranda_tanpa_berita_tidak_merender_seksi_informasi(): void
    {
        $this->assertSame(0, Berita::query()->count());

        $html = $this->get('/')->assertOk()->getContent();

        // "Kabar terbaru dari desa" juga dipakai sebagai subtitle quicklink,
        // jadi patokannya judul seksi dan kartu berita, bukan frasa itu.
        $this->assertStringNotContainsString('news-grid', $html);
        $this->assertStringNotContainsString('Berita Utama', $html);
        $this->assertStringNotContainsString('<h2>Kabar terbaru dari '.$this->profil()->nama_desa.'</h2>', $html);
    }

    /** Profil desa yang sedang dipakai halaman. */
    protected function profil(): ProfilDesa
    {
        return ProfilDesa::query()->firstOrFail();
    }

    /**
     * Peta lokasi memakai sematan Google Maps sungguhan, bukan gambar CSS.
     * Kueri peta yang dikosongkan admin harus jatuh ke alamat kantor agar
     * peta tidak menunjuk ke lokasi acak.
     */
    public function test_peta_lokasi_memakai_sematan_google_maps_dari_alamat(): void
    {
        $this->assertNull($this->profil()->kueri_peta);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('output=embed', $html);
        $this->assertStringContainsString('maps?q=', $html);
        // Tampilan satelit dan zoom dekat supaya bangunan balai desa terlihat.
        $this->assertStringContainsString('t=k', $html);
        $this->assertStringContainsString('z=17', $html);
        // Kueri memuat alamat kantor sehingga peta menunjuk lokasi yang benar.
        $this->assertStringContainsString(rawurlencode($this->profil()->alamat_kantor), $html);
        // Label lokasi ditulis di atas peta agar warga tahu titik yang ditunjuk.
        $this->assertStringContainsString('peta-label', $html);
        $this->assertStringContainsString('Balai '.$this->profil()->nama_desa, $html);
        $this->assertStringContainsString('Buka di Google Maps', $html);
    }

    public function test_kontak_menampilkan_peta_lokasi(): void
    {
        $this->get('/kontak')
            ->assertOk()
            ->assertSee('output=embed', false)
            ->assertSee('Kantor '.$this->profil()->nama_desa, false)
            ->assertSee('Balai '.$this->profil()->nama_desa, false);
    }

    /**
     * Bila admin mengisi "Kueri lokasi peta", nilai itulah yang dipakai
     * (lebih spesifik daripada alamat kantor).
     */
    public function test_kueri_peta_dari_admin_dipakai_bila_diisi(): void
    {
        $this->profil()->update(['kueri_peta' => 'Balai Desa Karang Sari']);

        $html = $this->get('/kontak')->assertOk()->getContent();

        $this->assertStringContainsString(rawurlencode('Balai Desa Karang Sari'), $html);
    }

    /**
     * Tanpa nomor WhatsApp desa, tombol "Chat WhatsApp desa" tidak dirender
     * agar tidak mengarah ke tautan kosong (https://wa.me/).
     */
    public function test_tombol_whatsapp_desa_tidak_dirender_tanpa_nomor(): void
    {
        $this->assertNull($this->profil()->whatsapp);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('Chat WhatsApp desa', $html);
        $this->assertStringNotContainsString('wa.me/', $html);
        $this->assertStringContainsString('Belum diisi oleh perangkat desa', $html);
    }

    /** Nomor WhatsApp yang diisi dipakai untuk tautan wa.me. */
    public function test_tombol_whatsapp_desa_dirender_saat_nomor_diisi(): void
    {
        $this->profil()->update(['whatsapp' => '0812-3456-7890']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Chat WhatsApp desa', $html);
        $this->assertStringContainsString('https://wa.me/081234567890', $html);
    }
}
