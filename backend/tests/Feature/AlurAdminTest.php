<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Enums\StatusPengajuan;
use App\Models\Berita;
use App\Models\DataDesa;
use App\Models\DokumenPengajuan;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Models\PerangkatDesa;
use App\Models\PotensiDesa;
use App\Models\ProfilDesa;
use App\Models\StatistikDesa;
use App\Models\User;
use App\Services\TemplateSuratService;
use App\Support\BatasPermintaan;
use App\Support\SiteProfile;
use Database\Seeders\DataDesaSeeder;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Perjalanan petugas desa di panel admin, dari login sampai pekerjaan selesai:
 *
 *   login -> daftar pengajuan -> detail -> verifikasi dokumen ->
 *   ubah status (termasuk jalur "Diterima") -> unduh surat Word ->
 *   kelola konten -> ubah profil -> hasilnya tampil di halaman publik
 *
 * Tes ini meniru klik nyata petugas, bukan memanggil service langsung, supaya
 * tombol dan formulir yang benar-benar dipakai di dashboard ikut teruji.
 */
class AlurAdminTest extends TestCase
{
    use RefreshDatabase;

    private const SANDI = 'sandi-uji-admin-2026';

    protected User $administrator;

    protected User $verifikator;

    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProfilDesaSeeder::class, DataDesaSeeder::class, JenisSuratSeeder::class]);
        Storage::fake('local');
        Storage::fake('public');
        SiteProfile::lupakan();
        BatasPermintaan::bersihkanSemua();

        $this->administrator = $this->buatAkun('sari', 'Sari Wulandari', RoleAdmin::Administrator);
        $this->verifikator = $this->buatAkun('rangga', 'Rangga Pratama', RoleAdmin::Verifikator);
        $this->operator = $this->buatAkun('dewi', 'Dewi Lestari', RoleAdmin::OperatorKonten);
    }

    protected function buatAkun(string $username, string $nama, RoleAdmin $role): User
    {
        return User::create([
            'name' => $nama,
            'username' => $username,
            'email' => $username.'@karangsari.desa.id',
            'password' => Hash::make(self::SANDI),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    /** Isian template wajib untuk jenis surat tertentu, mengikuti penandanya. */
    protected function isianUntuk(string $slug): array
    {
        $jenis = JenisSurat::query()->where('slug', $slug)->firstOrFail();
        $isian = [];

        foreach (app(TemplateSuratService::class)->penandaKustom($jenis) as $p) {
            $isian[$p['kunci']] = match ($p['tipe'] ?? 'text') {
                'date' => '2026-02-10',
                'number' => '4',
                default => 'Isi '.$p['label'],
            };
        }

        return $isian;
    }

    /** Pengajuan yang dikirim warga lewat formulir publik (bukan dibuat manual). */
    protected function pengajuanMasuk(string $slug = 'domisili', array $tambahan = []): Pengajuan
    {
        $data = array_merge([
            'nama' => 'Siti Nurhaliza',
            'nik' => '1801010101010002',
            'tempat_lahir' => 'Lampung Selatan',
            'tanggal_lahir' => '1995-04-21',
            'alamat' => 'Dusun Mekar Sari RT 03/RW 02',
            'keperluan' => 'Keterangan domisili untuk pendaftaran sekolah anak',
            'whatsapp' => '081298765432',
            'persetujuan' => '1',
            'isian_surat' => $this->isianUntuk($slug),
        ], $tambahan);

        $this->post(route('pengajuan.store', $slug), $data)->assertRedirect();

        return Pengajuan::query()->latest('id')->firstOrFail();
    }

    // ------------------------------------------------------------------ login

    public function test_tiga_peran_admin_dapat_masuk_dan_membuka_dashboard(): void
    {
        foreach (['sari' => $this->administrator, 'rangga' => $this->verifikator, 'dewi' => $this->operator] as $username => $akun) {
            $this->post('/admin/login', ['username' => $username, 'password' => self::SANDI])
                ->assertRedirect(route('admin.ringkasan'));

            $this->assertAuthenticatedAs($akun);

            $this->get('/admin')->assertOk();

            if ($username === 'dewi') {
                // Operator Konten tidak menerima ringkasan pengajuan (data warga).
                $this->get('/admin')
                    ->assertOk()
                    ->assertDontSee('Total pengajuan')
                    ->assertSee('Ringkasan konten');
            } else {
                $this->get('/admin')->assertSee('Total pengajuan');
            }

            $this->post('/admin/logout')->assertRedirect(route('admin.login'));
            $this->assertGuest();
        }
    }

    public function test_ringkasan_menampilkan_pengajuan_yang_menunggu_tindakan(): void
    {
        $pengajuan = $this->pengajuanMasuk();

        $this->actingAs($this->verifikator)
            ->get('/admin')
            ->assertOk()
            ->assertSee($pengajuan->nomor_referensi);
    }

    // --------------------------------------------------------------- pengajuan

    public function test_daftar_pengajuan_dapat_disaring_dan_dicari(): void
    {
        $pengajuan = $this->pengajuanMasuk();

        $this->actingAs($this->verifikator)
            ->get('/admin/pengajuan')
            ->assertOk()
            ->assertSee('Siti Nurhaliza')
            ->assertSee($pengajuan->nomor_referensi);

        $this->actingAs($this->verifikator)
            ->get('/admin/pengajuan?status=menunggu_verifikasi')
            ->assertOk()
            ->assertSee($pengajuan->nomor_referensi);

        // NIK lengkap tidak pernah tampil mentah di daftar.
        $this->actingAs($this->verifikator)
            ->get('/admin/pengajuan')
            ->assertDontSee('1801010101010002');
    }

    public function test_detail_pengajuan_menampilkan_tombol_aksi_dan_tautan_whatsapp(): void
    {
        $pengajuan = $this->pengajuanMasuk();

        $respon = $this->actingAs($this->verifikator)
            ->get(route('admin.pengajuan.show', $pengajuan))
            ->assertOk()
            ->assertSee('Ubah status')
            ->assertSee('Nomor surat')
            ->assertSee('Siti Nurhaliza');

        // Tombol WhatsApp membuka chat langsung ke warga (wa.me), bukan
        // menyuruh petugas menyalin nomor secara manual.
        $html = $respon->getContent();
        $this->assertStringContainsString('https://wa.me/6281298765432', $html);
        $this->assertStringNotContainsString('Salin nomor WhatsApp', $html);
    }

    public function test_petugas_melihat_jalur_terima_pada_pengajuan_yang_perlu_perbaikan(): void
    {
        $pengajuan = $this->pengajuanMasuk();

        $this->actingAs($this->verifikator)->patch(route('admin.pengajuan.status', $pengajuan), [
            'status' => StatusPengajuan::PerluPerbaikan->value,
            'catatan' => 'Alamat pada surat kurang lengkap.',
        ])->assertRedirect(route('admin.pengajuan.show', $pengajuan));

        $this->assertSame(StatusPengajuan::PerluPerbaikan, $pengajuan->fresh()->status);

        // Panel "Ubah status" harus tetap menawarkan "Disetujui" supaya ada
        // jalan menerima pengajuan yang sudah dibetulkan warga.
        $respon = $this->actingAs($this->verifikator)
            ->get(route('admin.pengajuan.show', $pengajuan))
            ->assertOk();

        $respon->assertSee('Disetujui');
        $respon->assertSee('value="disetujui"', false);

        // Aksi cepat "Terima" ikut tampil di kepala halaman.
        $this->assertStringContainsString('Terima', $respon->getContent());
    }

    public function test_alur_status_lengkap_sampai_selesai(): void
    {
        $pengajuan = $this->pengajuanMasuk();

        $langkah = [
            [StatusPengajuan::PerluPerbaikan, 'Lengkapi alamat.'],
            [StatusPengajuan::Disetujui, 'Dokumen lengkap.'],
            [StatusPengajuan::SedangDiproses, null],
            [StatusPengajuan::SiapDiambil, null],
            [StatusPengajuan::Selesai, null],
        ];

        foreach ($langkah as [$tujuan, $catatan]) {
            $this->actingAs($this->verifikator)
                ->patch(route('admin.pengajuan.status', $pengajuan), array_filter([
                    'status' => $tujuan->value,
                    'catatan' => $catatan,
                ], fn ($v) => $v !== null))
                ->assertRedirect(route('admin.pengajuan.show', $pengajuan))
                ->assertSessionHasNoErrors();

            $this->assertSame($tujuan, $pengajuan->fresh()->status);
        }

        $pengajuan->refresh();
        $this->assertSame($this->verifikator->id, $pengajuan->diproses_oleh);
        $this->assertNotNull($pengajuan->selesai_at);

        // Riwayat memuat satu baris awal "Pengajuan diterima" dari warga,
        // lalu satu baris untuk setiap perubahan status oleh petugas.
        // Relasi riwayat() mengurutkan dari yang terbaru, jadi baris warga
        // ada di elemen terakhir dan status terakhir di elemen pertama.
        $this->assertCount(count($langkah) + 1, $pengajuan->riwayat);
        $this->assertSame('warga', $pengajuan->riwayat->last()->aktor);
        $this->assertSame(StatusPengajuan::Selesai, $pengajuan->riwayat->first()->status);
        $this->assertSame(StatusPengajuan::MenungguVerifikasi, $pengajuan->riwayat->last()->status);
    }

    public function test_verifikasi_dokumen_warga_dari_panel_admin(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();
        $syarat = $jenis->persyaratan()->firstOrFail();

        $pengajuan = $this->pengajuanMasuk('domisili', [
            'dokumen' => [$syarat->id => UploadedFile::fake()->image('pengantar-rt.jpg')->size(300)],
        ]);

        $dokumen = $pengajuan->dokumen()->firstOrFail();
        $this->assertSame(DokumenPengajuan::MENUNGGU, $dokumen->status_verifikasi);

        $this->actingAs($this->verifikator)
            ->patch(route('admin.pengajuan.dokumen.status', [$pengajuan, $dokumen]), [
                'status_verifikasi' => DokumenPengajuan::TERVERIFIKASI,
            ])
            ->assertRedirect(route('admin.pengajuan.show', $pengajuan));

        $this->assertSame(DokumenPengajuan::TERVERIFIKASI, $dokumen->fresh()->status_verifikasi);

        // Dokumen tetap bisa diunduh petugas lewat route admin.
        $unduh = $this->actingAs($this->verifikator)
            ->get(route('admin.pengajuan.dokumen', [$pengajuan, $dokumen]));
        $unduh->assertOk();
    }

    public function test_dokumen_pengajuan_lain_tidak_bisa_dibuka_lewat_id_lain(): void
    {
        $satu = $this->pengajuanMasuk();
        $dua = $this->pengajuanMasuk();

        $dokumen = $satu->dokumen()->create([
            'label' => 'KTP pemohon',
            'path' => 'dokumen/ktp.pdf',
            'nama_asli' => 'ktp.pdf',
            'mime' => 'application/pdf',
            'ukuran' => 1024,
        ]);

        $this->actingAs($this->verifikator)
            ->get(route('admin.pengajuan.dokumen', [$dua, $dokumen]))
            ->assertNotFound();
    }

    public function test_nomor_surat_dapat_disimpan_tanpa_mengubah_status(): void
    {
        $pengajuan = $this->pengajuanMasuk();

        $this->actingAs($this->verifikator)
            ->put(route('admin.pengajuan.nomor-surat', $pengajuan), [
                'nomor_surat' => '474/012/KS/2026',
            ])
            ->assertRedirect(route('admin.pengajuan.show', $pengajuan));

        $pengajuan->refresh();
        $this->assertSame('474/012/KS/2026', $pengajuan->nomor_surat);
        $this->assertSame(StatusPengajuan::MenungguVerifikasi, $pengajuan->status);
    }

    public function test_surat_word_dapat_diunduh_petugas_dan_berisi_isian_warga(): void
    {
        $pengajuan = $this->pengajuanMasuk();

        $respon = $this->actingAs($this->verifikator)
            ->get(route('admin.pengajuan.surat-word', $pengajuan));

        $respon->assertOk();
        $respon->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $isi = $respon->streamedContent();
        $this->assertStringStartsWith('PK', $isi);

        // DOCX sah: arsip ZIP berisi word/document.xml, dan isinya memuat
        // identitas warga serta nama kepala desa dari template resmi.
        $path = tempnam(sys_get_temp_dir(), 'surat-admin').'.docx';
        file_put_contents($path, $isi);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'Berkas surat harus berupa DOCX yang sah.');
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertNotFalse($xml);
        $teks = html_entity_decode(strip_tags((string) $xml), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $this->assertStringContainsString('Siti Nurhaliza', $teks);
        $this->assertStringContainsString('R O M S I, SH', $teks);
        $this->assertStringNotContainsString('{{', $teks);
    }

    public function test_operator_konten_tidak_boleh_mengubah_status_pengajuan(): void
    {
        $pengajuan = $this->pengajuanMasuk();

        $this->actingAs($this->operator)
            ->patch(route('admin.pengajuan.status', $pengajuan), [
                'status' => StatusPengajuan::Disetujui->value,
            ])
            ->assertForbidden();

        $this->assertSame(StatusPengajuan::MenungguVerifikasi, $pengajuan->fresh()->status);
    }

    // ------------------------------------------------------------------ konten

    public function test_operator_konten_mengelola_berita_sampai_tampil_di_halaman_informasi(): void
    {
        $this->actingAs($this->operator)
            ->post(route('admin.berita.store'), [
                'judul' => 'Musyawarah Desa RKPDes 2026',
                'kategori' => 'berita',
                'ringkasan' => 'Hasil musyawarah desa tahun ini.',
                'isi' => 'Warga berkumpul di balai desa membahas rencana pembangunan.',
                'terbit_at' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('admin.berita.index'));

        $berita = Berita::query()->firstOrFail();

        $this->get(route('informasi'))
            ->assertOk()
            ->assertSee('Musyawarah Desa RKPDes 2026');

        $this->get(route('informasi.detail', $berita->slug))
            ->assertOk()
            ->assertSee('Musyawarah Desa RKPDes 2026');
    }

    public function test_operator_konten_mengelola_potensi_dan_statistik(): void
    {
        $this->actingAs($this->operator)
            ->post(route('admin.potensi.store'), [
                'nama' => 'Kopi Lereng Puncak',
                'kategori' => 'Pertanian',
                'deskripsi' => 'Kopi arabika warga dusun.',
                'urutan' => 1,
                'is_aktif' => '1',
            ])
            ->assertRedirect(route('admin.potensi.index'));

        $this->actingAs($this->operator)
            ->post(route('admin.statistik.store'), [
                'label' => 'Kepala keluarga',
                'nilai' => '2011',
                'satuan' => 'KK',
                'urutan' => 1,
                'is_aktif' => '1',
            ])
            ->assertRedirect(route('admin.statistik.index'));

        $this->assertSame(1, PotensiDesa::query()->count());
        $this->assertSame(1, StatistikDesa::query()->count());

        $this->get(route('potensi'))->assertOk()->assertSee('Kopi Lereng Puncak');
        $this->get(route('beranda'))->assertOk()->assertSee('Kopi Lereng Puncak');
    }

    public function test_administrator_mengelola_akun_admin(): void
    {
        $this->actingAs($this->administrator)
            ->post(route('admin.akun.store'), [
                'name' => 'Petugas Baru',
                'username' => 'petugas',
                'email' => 'petugas@karangsari.desa.id',
                'role' => RoleAdmin::Verifikator->value,
                'password' => 'sandi-baru-sekali-2026',
                'password_confirmation' => 'sandi-baru-sekali-2026',
            ])
            ->assertRedirect(route('admin.akun.index'));

        $this->assertDatabaseHas('users', ['username' => 'petugas', 'role' => 'verifikator']);
    }

    public function test_administrator_menambah_perangkat_desa_dan_tampil_di_profil(): void
    {
        $this->actingAs($this->administrator)
            ->post(route('admin.perangkat.store'), [
                'nama' => 'Ahmad Fauzi',
                'jabatan' => 'Sekretaris Desa',
                'urutan' => 1,
                'is_aktif' => '1',
            ])
            ->assertRedirect(route('admin.perangkat.index'));

        $this->assertSame(1, PerangkatDesa::query()->count());

        $this->get(route('profil'))
            ->assertOk()
            ->assertSee('Ahmad Fauzi')
            ->assertSee('Sekretaris Desa');
    }

    // ------------------------------------------------------------------ profil

    /** Isian profil lengkap sesuai formulir admin. */
    protected function dataProfil(array $tambahan = []): array
    {
        return array_merge([
            'nama_desa' => 'Desa Karang Sari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => 'Lampung Selatan',
            'provinsi' => 'Lampung',
            'kode_pos' => '35365',
            'alamat_kantor' => 'Jl. RA. Basyid Desa Karang Sari',
            'telepon' => '08123456789',
            'whatsapp' => '081234567890',
            'email' => 'karangsari@desa.id',
            'jam_layanan' => '08.00 - 17.00',
            'jam_kunjungan' => 'Senin - Jumat',
            'tahun_berdiri' => '2002',
            'kueri_peta' => 'Desa Karang Sari, Jati Agung',
            'sejarah' => 'Desa Karang Sari adalah pemekaran dari Desa Karang Anyar.',
            'visi' => 'Desa yang maju dan mandiri.',
            'misi' => "Meningkatkan pelayanan\nMembangun infrastruktur desa",
            'batas_wilayah' => 'Utara: Desa Karang Anyar',
            'penggunaan_lahan' => 'Sawah dan pemukiman',
            'kondisi_umum' => 'Desa Karang Sari terletak 1,5 km ke arah barat dari ibu kota kecamatan.',
            'iklim' => 'Iklim kemarau dan penghujan.',
            'luas_wilayah' => '725 ha',
            'daftar_dusun' => "Dusun I B\nDusun II B\nDusun Warung Gunung",
            'jarak_provinsi' => '15 Km',
            'jarak_kabupaten' => '80 Km',
            'jarak_kecamatan' => '2,5 Km',
            'penduduk_laki_laki' => '5.333',
            'penduduk_perempuan' => '4.805',
            'jumlah_kk' => '2.011',
            'keadaan_sosial' => 'Mayoritas penduduk bermata pencaharian sebagai petani.',
        ], $tambahan);
    }

    public function test_profil_desa_tersimpan_dan_tampil_di_halaman_publik(): void
    {
        $this->actingAs($this->administrator)
            ->put(route('admin.profil.update'), $this->dataProfil())
            ->assertRedirect(route('admin.profil.edit'))
            ->assertSessionHasNoErrors();

        $profil = ProfilDesa::query()->firstOrFail();
        $this->assertSame('725 ha', $profil->luas_wilayah);
        $this->assertSame('5.333', $profil->penduduk_laki_laki);

        SiteProfile::lupakan();

        // Jumlah penduduk dihitung dari rincian L + P (5.333 + 4.805).
        $this->assertSame('10.138', $profil->jumlah_penduduk);

        $this->get(route('profil'))
            ->assertOk()
            ->assertSee('725 ha')
            ->assertSee('10.138')
            ->assertSee('2.011')
            ->assertSee('15 Km')
            ->assertSee('Dusun Warung Gunung')
            ->assertSee('Iklim kemarau dan penghujan.')
            ->assertSee('Mayoritas penduduk bermata pencaharian sebagai petani.');
    }

    public function test_tabel_data_desa_diisi_dari_admin_lalu_tampil_di_profil(): void
    {
        $umur = DataDesa::query()->kelompok(DataDesa::GOLONGAN_UMUR)->terurut()->firstOrFail();
        $agama = DataDesa::query()->kelompok(DataDesa::AGAMA)->where('label', 'Islam')->firstOrFail();
        $kesejahteraan = DataDesa::query()->kelompok(DataDesa::KESEJAHTERAAN)->terurut()->firstOrFail();
        $riwayat = DataDesa::query()->kelompok(DataDesa::RIWAYAT_KADES)->terurut()->get();

        $data = [];
        $data[$umur->id] = ['nilai_l' => '51', 'nilai_p' => '42'];
        $data[$agama->id] = ['nilai' => '10.085'];
        $data[$kesejahteraan->id] = ['nilai' => '534', 'keterangan' => 'KK'];

        foreach ($riwayat as $i => $baris) {
            $data[$baris->id] = [
                'nilai' => $i === 0 ? 'Lima tahun' : 'Sekarang',
                'keterangan' => $i === 0 ? 'Kepala Desa Pertama' : 'Kepala Desa',
            ];
        }

        $this->actingAs($this->administrator)
            ->put(route('admin.profil.update'), $this->dataProfil(['data' => $data]))
            ->assertRedirect(route('admin.profil.edit'))
            ->assertSessionHasNoErrors();

        $this->assertSame('51', $umur->fresh()->nilai_l);
        $this->assertSame('10.085', $agama->fresh()->nilai);

        SiteProfile::lupakan();

        $this->get(route('profil'))
            ->assertOk()
            ->assertSee('Menurut golongan umur')
            // Kolom jumlah dihitung otomatis 51 + 42 = 93.
            ->assertSee('93')
            ->assertSee('10.085')
            ->assertSee('Tingkat kesejahteraan masyarakat')
            ->assertSee('Riwayat kepala desa')
            ->assertSee('Kepala Desa Pertama');
    }

    public function test_halaman_edit_profil_menampilkan_isian_dokumen_profil_desa(): void
    {
        $this->actingAs($this->administrator)
            ->get(route('admin.profil.edit'))
            ->assertOk()
            // Bagian "Kondisi Umum Desa".
            ->assertSee('Kondisi umum desa')
            ->assertSee('name="luas_wilayah"', false)
            ->assertSee('name="jumlah_kk"', false)
            ->assertSee('name="penduduk_laki_laki"', false)
            ->assertSee('name="penduduk_perempuan"', false)
            ->assertSee('name="kondisi_umum"', false)
            ->assertSee('name="iklim"', false)
            ->assertSee('name="daftar_dusun"', false)
            ->assertSee('name="keadaan_sosial"', false)
            // Tabel data desa dari dokumen.
            ->assertSee('Jumlah penduduk menurut golongan umur')
            ->assertSee('Jumlah penduduk menurut agama')
            ->assertSee('Jumlah penduduk menurut tingkat pendidikan')
            ->assertSee('Jumlah penduduk menurut mata pencaharian')
            ->assertSee('Riwayat kepala desa');
    }

    public function test_form_profil_menolak_email_tidak_sah(): void
    {
        $this->actingAs($this->administrator)
            ->put(route('admin.profil.update'), $this->dataProfil(['email' => 'bukan-email']))
            ->assertSessionHasErrors('email');
    }

    public function test_perubahan_profil_langsung_tampil_di_footer_dan_kontak(): void
    {
        $this->actingAs($this->administrator)
            ->put(route('admin.profil.update'), $this->dataProfil([
                'telepon' => '0721-555123',
                'jam_layanan' => '07.30 - 16.00',
            ]))
            ->assertRedirect(route('admin.profil.edit'));

        SiteProfile::lupakan();

        foreach ([route('beranda'), route('kontak'), route('profil')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('0721-555123')
                ->assertSee('07.30 - 16.00');
        }
    }

    public function test_topbar_tidak_menampilkan_butir_kosong_saat_kontak_belum_diisi(): void
    {
        $this->actingAs($this->administrator)
            ->put(route('admin.profil.update'), $this->dataProfil([
                'jam_layanan' => '',
                'telepon' => '',
                'email' => '',
                'alamat_kantor' => '',
            ]))
            ->assertRedirect(route('admin.profil.edit'));

        SiteProfile::lupakan();

        $html = $this->get(route('beranda'))->assertOk()->getContent();

        // Dulu tampil "Pelayanan " (label tanpa isi) dan ikon telepon kosong.
        $this->assertStringNotContainsString('Pelayanan </span>', $html);
        $this->assertStringNotContainsString('footer-contact', $html);
    }

    // ------------------------------------------------------------------- akses

    public function test_halaman_admin_utama_dapat_dibuka_setiap_peran(): void
    {
        $pengajuan = $this->pengajuanMasuk();

        $halaman = [
            '/admin',
            '/admin/pengajuan',
            route('admin.pengajuan.show', $pengajuan),
            '/admin/panduan',
            '/admin/log',
        ];

        foreach ($halaman as $url) {
            $this->actingAs($this->administrator)->get($url)->assertOk();
            $this->actingAs($this->verifikator)->get($url)->assertOk();
            $this->actingAs($this->operator)->get($url)->assertOk();
        }

        // Katalog jenis surat hanya untuk Administrator & Verifikator; Operator
        // Konten mengurus berita/potensi/profil, bukan template surat.
        foreach (['/admin/jenis-surat'] as $url) {
            $this->actingAs($this->administrator)->get($url)->assertOk();
            $this->actingAs($this->verifikator)->get($url)->assertOk();
            $this->actingAs($this->operator)->get($url)->assertForbidden();
        }

        // Halaman khusus konten hanya untuk Administrator & Operator Konten.
        foreach (['/admin/berita', '/admin/potensi', '/admin/statistik', '/admin/perangkat', '/admin/profil', '/admin/konten'] as $url) {
            $this->actingAs($this->administrator)->get($url)->assertOk();
            $this->actingAs($this->operator)->get($url)->assertOk();
            $this->actingAs($this->verifikator)->get($url)->assertForbidden();
        }

        // Halaman akun hanya untuk Administrator.
        $this->actingAs($this->administrator)->get('/admin/akun')->assertOk();
        $this->actingAs($this->verifikator)->get('/admin/akun')->assertForbidden();
        $this->actingAs($this->operator)->get('/admin/akun')->assertForbidden();
    }
}
