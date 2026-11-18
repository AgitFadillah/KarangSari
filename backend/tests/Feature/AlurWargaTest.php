<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuan;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Services\TemplateSuratService;
use App\Support\BatasPermintaan;
use Database\Seeders\DataDesaSeeder;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Perjalanan lengkap warga dari sudut pandang pengguna situs publik:
 *
 *   katalog layanan -> formulir -> kirim -> halaman berhasil ->
 *   cek status -> revisi -> unduh surat (.docx)
 *
 * Fokusnya bukan unit masing-masing kelas, melainkan apakah rangkaian halaman
 * yang dilihat warga benar-benar dapat dilalui tanpa jalan buntu, dan apakah
 * isian yang diketik warga muncul kembali pada surat resmi.
 */
class AlurWargaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProfilDesaSeeder::class, DataDesaSeeder::class, JenisSuratSeeder::class]);
        Storage::fake('local');
        Storage::fake('public');
        \App\Support\SiteProfile::lupakan();

        // Kuota permintaan dihitung per IP + nama limiter dan tidak direset
        // antar-permintaan dalam satu tes. Tanpa pembersihan ini, tes yang
        // mengirim beberapa pengajuan akan mengenai 429.
        BatasPermintaan::bersihkanSemua();
    }

    /** Slug resmi sesuai dokumen desa. */
    private const TUJUH_SLUG = [
        'domisili', 'usaha', 'tidak-mampu', 'kematian',
        'belum-menikah', 'orang-tua-asuh', 'pekerjaan',
    ];

    /** Isian template sesuai penanda tiap jenis surat. */
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

    /** @return array<string, mixed> */
    protected function dataWarga(array $tambahan = []): array
    {
        return array_merge([
            'nama' => 'Siti Nurhaliza',
            'nik' => '1801010101010002',
            'tempat_lahir' => 'Lampung Selatan',
            'tanggal_lahir' => '1995-04-21',
            'alamat' => 'Dusun Mekar Sari RT 03/RW 02',
            'keperluan' => 'Keterangan domisili untuk pendaftaran sekolah anak',
            'whatsapp' => '081298765432',
            'persetujuan' => '1',
            'isian_surat' => $this->isianUntuk('domisili'),
        ], $tambahan);
    }

    protected function kirimPengajuan(string $slug = 'domisili', array $tambahan = []): Pengajuan
    {
        $this->post(route('pengajuan.store', $slug), $this->dataWarga($tambahan))
            ->assertRedirect();

        return Pengajuan::query()->latest('id')->firstOrFail();
    }

    // ---------------------------------------------------------------- katalog

    public function test_katalog_layanan_menampilkan_tujuh_surat_resmi(): void
    {
        $respon = $this->get('/pelayanan')->assertOk();

        foreach (self::TUJUH_SLUG as $slug) {
            $respon->assertSee(route('layanan.detail', $slug), false);
        }

        // Kategori lama tanpa dokumen desa tidak boleh tampil sebagai aktif.
        $respon->assertDontSee(route('layanan.detail', 'keramaian'), false);
    }

    public function test_setiap_halaman_detail_layanan_dapat_dibuka(): void
    {
        foreach (self::TUJUH_SLUG as $slug) {
            $this->get(route('layanan.detail', $slug))->assertOk();
        }
    }

    public function test_formulir_warga_menampilkan_kolom_isian_sesuai_jenis_surat(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'usaha')->firstOrFail();
        $penanda = app(TemplateSuratService::class)->penandaKustom($jenis);

        $this->assertNotEmpty($penanda, 'Jenis surat usaha harus punya kolom isian.');

        $respon = $this->get('/pengajuan?jenis=usaha')->assertOk();

        foreach ($penanda as $p) {
            $respon->assertSee('name="isian_surat['.$p['kunci'].']"', false);
            $respon->assertSee($p['label']);
        }

        // Surat resmi langsung terlihat pada pratinjau (kop + nama kepala desa).
        $respon->assertSee('R O M S I, SH');
    }

    public function test_formulir_menampilkan_identitas_wajib_dan_persetujuan(): void
    {
        $this->get('/pengajuan?jenis=domisili')
            ->assertOk()
            ->assertSee('name="nik"', false)
            ->assertSee('name="tempat_lahir"', false)
            ->assertSee('name="tanggal_lahir"', false)
            ->assertSee('name="persetujuan"', false)
            ->assertSee('name="dokumen[', false);
    }

    // ---------------------------------------------------------------- kirim

    public function test_warga_mengirim_pengajuan_lalu_melihat_halaman_berhasil(): void
    {
        $this->post(route('pengajuan.store', 'domisili'), $this->dataWarga())
            ->assertRedirect();

        $pengajuan = Pengajuan::query()->firstOrFail();

        $this->assertSame(StatusPengajuan::MenungguVerifikasi, $pengajuan->status);
        $this->assertNotNull($pengajuan->nomor_referensi);

        // Halaman berhasil memuat nomor referensi dan instruksi lanjutan.
        $this->get(route('pengajuan.berhasil', $pengajuan))
            ->assertOk()
            ->assertSee($pengajuan->nomor_referensi)
            ->assertSee('Siti Nurhaliza')
            ->assertSee('Pantau status surat');
    }

    public function test_nomor_referensi_dan_kode_akses_dapat_dipakai_untuk_cek_status(): void
    {
        $pengajuan = $this->kirimPengajuan();

        $this->post(route('cek-status.cari'), [
            'nomor_referensi' => $pengajuan->nomor_referensi,
            'kode_akses' => $pengajuan->kode_akses,
        ])
            ->assertOk()
            ->assertSee($pengajuan->nomor_referensi)
            ->assertSee('Menunggu Verifikasi');
    }

    public function test_cek_status_menolak_kode_akses_yang_salah(): void
    {
        $pengajuan = $this->kirimPengajuan();

        // Sesi pengirim dibuang: meniru warga lain yang hanya tahu nomornya.
        $this->flushSession();

        $this->from(route('cek-status.form'))
            ->post(route('cek-status.cari'), [
                'nomor_referensi' => $pengajuan->nomor_referensi,
                'kode_akses' => 'SALAH123',
            ])
            ->assertRedirect(route('cek-status.form'))
            ->assertSessionHasErrors('kode_akses');
    }

    public function test_nomor_referensi_tidak_dikenal_memberi_pesan_jelas(): void
    {
        $this->from(route('cek-status.form'))
            ->post(route('cek-status.cari'), ['nomor_referensi' => 'KRS-2026-9999'])
            ->assertRedirect(route('cek-status.form'))
            ->assertSessionHasErrors('kode_akses');
    }

    public function test_cek_status_tidak_membocorkan_nik_dan_alamat(): void
    {
        $pengajuan = $this->kirimPengajuan();

        $html = $this->post(route('cek-status.cari'), [
            'nomor_referensi' => $pengajuan->nomor_referensi,
            'kode_akses' => $pengajuan->kode_akses,
        ])->assertOk()->getContent();

        $this->assertStringNotContainsString('1801010101010002', $html);
        $this->assertStringNotContainsString('Dusun Mekar Sari RT 03/RW 02', $html);
    }

    public function test_pemilik_melihat_kembali_kode_aksesnya_di_halaman_cek_status(): void
    {
        $pengajuan = $this->kirimPengajuan();

        // Solusi "kode tertinggal di perangkat lain": kode ditampilkan lagi
        // di halaman hasil, lengkap dengan tombol salin.
        $this->post(route('cek-status.cari'), [
            'nomor_referensi' => $pengajuan->nomor_referensi,
            'kode_akses' => $pengajuan->kode_akses,
        ])
            ->assertOk()
            ->assertSee('Kode akses')
            ->assertSee($pengajuan->kode_akses)
            ->assertSee('Salin kode akses');
    }

    /**
     * Tautan "pantau status" harus tetap bekerja di perangkat lain.
     *
     * Rute status-saya semula HANYA menerima sesi yang sudah ditandai, sehingga
     * tautan yang tersalin dari bilah alamat lalu dibuka di ponsel lain
     * berakhir 404 walau warga memegang kode akses yang benar. Kode akses kini
     * diterima juga, sama seperti halaman surat dan revisi.
     */
    public function test_status_saya_dapat_dibuka_dengan_kode_akses_di_perangkat_lain(): void
    {
        $pengajuan = $this->kirimPengajuan();

        // Sesi pengirim dibuang: meniru perangkat lain.
        $this->flushSession();

        $this->get(route('cek-status.saya', $pengajuan->nomor_referensi))
            ->assertNotFound();

        $this->get(route('cek-status.saya', [
            'nomorReferensi' => $pengajuan->nomor_referensi,
            'kode' => $pengajuan->kode_akses,
        ]))
            ->assertOk()
            ->assertSee($pengajuan->nomor_referensi);

        // Kode salah tetap ditolak.
        $this->flushSession();
        $this->get(route('cek-status.saya', [
            'nomorReferensi' => $pengajuan->nomor_referensi,
            'kode' => 'SALAH123',
        ]))->assertNotFound();
    }

    /**
     * Nomor referensi yang diketik ulang dengan huruf kecil harus tetap
     * dikenali di SEMUA halaman warga, bukan hanya di cek status.
     *
     * Rute unduh surat semula memakai nilai mentah dari URL, sehingga
     * "krs-2026-0042" menghasilkan 404 padahal kode aksesnya benar — warga
     * mengira suratnya hilang. Cek status dan revisi sudah menormalkan
     * huruf besar/kecil sejak awal; unduh surat harus ikut.
     */
    public function test_nomor_referensi_huruf_kecil_diterima_di_semua_halaman_warga(): void
    {
        $pengajuan = $this->kirimPengajuan();

        $pengajuan->forceFill(['status' => StatusPengajuan::Disetujui->value])->save();

        $kecil = strtolower($pengajuan->nomor_referensi);

        $this->flushSession();
        $this->post(route('cek-status.cari'), [
            'nomor_referensi' => $kecil,
            'kode_akses' => $pengajuan->kode_akses,
        ])->assertOk();

        $this->flushSession();
        $this->get(route('cek-status.saya', [
            'nomorReferensi' => $kecil,
            'kode' => $pengajuan->kode_akses,
        ]))->assertOk();

        $this->flushSession();
        $this->get(route('pengajuan.surat', [
            'nomorReferensi' => $kecil,
            'kode' => $pengajuan->kode_akses,
        ]))->assertOk();
    }

    // ------------------------------------------------------------- lupa kode

    public function test_kode_akses_dapat_dipulihkan_dengan_tanggal_lahir(): void
    {
        $pengajuan = $this->kirimPengajuan();

        // Perangkat/sesi baru: seolah warga membuka dari HP lain.
        $this->flushSession();

        $this->post(route('cek-status.lupa'), [
            'nomor_referensi' => $pengajuan->nomor_referensi,
            'tanggal_lahir' => '1995-04-21',
        ])
            ->assertOk()
            ->assertSee('Data ditemukan')
            ->assertSee($pengajuan->nomor_referensi)
            ->assertSee($pengajuan->kode_akses);

        // Sesi perangkat ini ikut ditandai, jadi tombol unduh/revisi bekerja.
        $this->assertNotNull(session('akses_pengajuan.'.$pengajuan->id));
    }

    public function test_pemulihan_kode_menolak_tanggal_lahir_salah(): void
    {
        $pengajuan = $this->kirimPengajuan();
        $this->flushSession();

        $this->from(route('cek-status.lupa.form'))
            ->post(route('cek-status.lupa'), [
                'nomor_referensi' => $pengajuan->nomor_referensi,
                'tanggal_lahir' => '1990-01-01',
            ])
            ->assertRedirect(route('cek-status.lupa.form'))
            ->assertSessionHasErrors('tanggal_lahir');

        $this->assertNull(session('akses_pengajuan.'.$pengajuan->id));
    }

    public function test_pemulihan_kode_menolak_nomor_yang_tidak_ada(): void
    {
        $this->from(route('cek-status.lupa.form'))
            ->post(route('cek-status.lupa'), [
                'nomor_referensi' => 'KRS-2026-9999',
                'tanggal_lahir' => '1995-04-21',
            ])
            ->assertRedirect(route('cek-status.lupa.form'))
            ->assertSessionHasErrors('tanggal_lahir');
    }

    public function test_nomor_referensi_terisi_otomatis_setelah_mengirim_pengajuan(): void
    {
        $pengajuan = $this->kirimPengajuan();

        // Halaman konfirmasi mengarahkan ke form cek status; nomor referensi
        // harus sudah terisi agar warga cukup menempel kode akses.
        $this->get(route('cek-status.form'))
            ->assertOk()
            ->assertSee('value="'.$pengajuan->nomor_referensi.'"', false)
            ->assertSee('Terisi otomatis', false);
    }

    public function test_status_saya_dapat_dibuka_perangkat_yang_sudah_berhak(): void
    {
        $pengajuan = $this->kirimPengajuan();

        // Sesi pengirim masih ada: tidak perlu menempel kode lagi.
        $this->get(route('cek-status.saya', $pengajuan->nomor_referensi))
            ->assertOk()
            ->assertSee($pengajuan->nomor_referensi)
            ->assertSee($pengajuan->kode_akses);

        // Perangkat lain (sesi baru) tetap ditolak.
        $this->flushSession();
        $this->get(route('cek-status.saya', $pengajuan->nomor_referensi))->assertNotFound();
    }

    public function test_halaman_berhasil_menawarkan_simpan_ke_whatsapp_sendiri(): void    {
        $pengajuan = $this->kirimPengajuan();

        $this->get(route('pengajuan.berhasil', $pengajuan))
            ->assertOk()
            ->assertSee('Simpan ke WhatsApp saya')
            // Nomor warga dinormalkan ke format internasional.
            ->assertSee('wa.me/6281298765432', false);
    }

    public function test_warga_tidak_bisa_membuka_halaman_berhasil_orang_lain(): void
    {
        $pengajuan = $this->kirimPengajuan();

        // Sesi baru (mis. warga lain) tanpa nomor referensi di URL.
        $this->flushSession();

        $this->get(route('pengajuan.berhasil', $pengajuan))->assertNotFound();
    }

    // ---------------------------------------------------------------- revisi

    public function test_warga_merevisi_setelah_admin_meminta_perbaikan(): void
    {
        $pengajuan = $this->kirimPengajuan();

        $pengajuan->forceFill([
            'status' => StatusPengajuan::PerluPerbaikan->value,
            'catatan_admin' => 'Alamat pada surat kurang lengkap.',
        ])->save();

        $this->get(route('pengajuan.revisi', $pengajuan->nomor_referensi))
            ->assertOk()
            ->assertSee('Alamat pada surat kurang lengkap.')
            ->assertSee($pengajuan->nomor_referensi);

        $isian = $this->isianUntuk('domisili');

        $this->post(route('pengajuan.revisi.simpan', $pengajuan->nomor_referensi), $this->dataWarga([
            'alamat' => 'Dusun Mekar Sari RT 03/RW 02, Desa Karang Sari',
            'isian_surat' => $isian,
        ]))->assertRedirect(route('cek-status.form'));

        $pengajuan->refresh();

        $this->assertSame(StatusPengajuan::MenungguVerifikasi, $pengajuan->status);
        $this->assertSame(1, (int) $pengajuan->revisi_ke);
        $this->assertStringContainsString('Desa Karang Sari', (string) $pengajuan->alamat);
    }

    public function test_halaman_revisi_menampilkan_surat_lengkap_dengan_isian_warga(): void
    {
        $pengajuan = $this->kirimPengajuan('usaha', [
            'isian_surat' => array_merge($this->isianUntuk('usaha'), [
                'nama_usaha' => 'Warung Kelontong Bu Siti',
                'alamat_usaha' => 'Dusun Mekar Sari RT 03',
            ]),
        ]);

        $pengajuan->forceFill(['status' => StatusPengajuan::PerluPerbaikan->value])->save();

        $this->get(route('pengajuan.revisi', $pengajuan->nomor_referensi))
            ->assertOk()
            ->assertSee('Warung Kelontong Bu Siti')
            ->assertSee('R O M S I, SH');
    }

    // ---------------------------------------------------------------- unduh

    public function test_surat_belum_bisa_diunduh_sebelum_disetujui(): void
    {
        $pengajuan = $this->kirimPengajuan();

        $this->get(route('pengajuan.surat', $pengajuan->nomor_referensi))->assertNotFound();
    }

    public function test_surat_dapat_diunduh_setelah_disetujui(): void
    {
        $pengajuan = $this->kirimPengajuan();

        $pengajuan->forceFill(['status' => StatusPengajuan::Disetujui->value])->save();

        $respon = $this->get(route('pengajuan.surat', $pengajuan->nomor_referensi));

        $respon->assertOk();
        $respon->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        // Berkas .docx adalah arsip ZIP yang berisi word/document.xml.
        $isi = $respon->streamedContent();
        $this->assertStringStartsWith('PK', $isi);

        $path = tempnam(sys_get_temp_dir(), 'surat').'.docx';
        file_put_contents($path, $isi);
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'Berkas surat harus berupa DOCX yang sah.');
        $this->assertNotFalse($zip->getFromName('word/document.xml'));
        $zip->close();
        @unlink($path);
    }

    public function test_surat_yang_diunduh_memuat_identitas_warga_dan_nama_kepala_desa(): void
    {
        $pengajuan = $this->kirimPengajuan();
        $pengajuan->forceFill(['status' => StatusPengajuan::Selesai->value])->save();

        $teks = $pengajuan->suratResmiTeks();

        $this->assertStringContainsString('Siti Nurhaliza', $teks);
        $this->assertStringContainsString('1801010101010002', $teks);
        $this->assertStringContainsString('R O M S I, SH', $teks);
        $this->assertStringContainsString(now()->locale('id')->translatedFormat('j F Y'), $teks);
        $this->assertStringNotContainsString('{{', $teks);
    }

    // ---------------------------------------------------------------- isian

    public function test_isian_warga_muncul_pada_surat_untuk_tujuh_kategori(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $contoh = [
            'domisili' => ['agama' => 'Islam', 'status_perkawinan' => 'Kawin', 'pekerjaan' => 'Petani'],
            'usaha' => ['agama' => 'Islam', 'pekerjaan' => 'Wiraswasta', 'nama_usaha' => 'Warung Bu Siti', 'alamat_usaha' => 'Mekar Sari RT 03'],
            'tidak-mampu' => ['agama' => 'Islam', 'status_perkawinan' => 'Kawin', 'pekerjaan' => 'Buruh Tani'],
            'kematian' => ['agama' => 'Islam', 'status_perkawinan' => 'Kawin', 'pekerjaan' => 'Wiraswasta', 'tanggal_meninggal' => '2026-10-02', 'tempat_makam' => 'TPU Desa Karang Sari'],
            'belum-menikah' => ['agama' => 'Islam', 'status_perkawinan' => 'Belum Menikah', 'pekerjaan' => 'Karyawan Swasta'],
            'orang-tua-asuh' => [
                'agama' => 'Islam', 'pekerjaan' => 'Petani',
                'nama_anak' => 'Salsabila Putri', 'nik_anak' => '3201120000000106',
                'tempat_lahir_anak' => 'Lampung Selatan', 'tanggal_lahir_anak' => '2012-03-17',
                'agama_anak' => 'Islam', 'pekerjaan_anak' => 'Pelajar/Mahasiswa',
                'alamat_anak' => 'Mekar Sari RT 03', 'sejak' => '2018',
            ],
            'pekerjaan' => ['agama' => 'Islam', 'status_perkawinan' => 'Kawin', 'pekerjaan' => 'Buruh Harian Lepas', 'pekerjaan_sebenarnya' => 'Tukang Bangunan'],
        ];

        foreach ($contoh as $slug => $isian) {
            $pengajuan = $this->kirimPengajuan($slug, ['isian_surat' => $isian]);
            $teks = $pengajuan->suratResmiTeks();

            foreach ($isian as $kunci => $nilai) {
                // Tanggal diubah formatnya oleh surat; cukup pastikan tahunnya.
                if (str_starts_with($kunci, 'tanggal_')) {
                    $this->assertStringContainsString(
                        substr($nilai, 0, 4),
                        $teks,
                        "Tanggal {$kunci} tidak tercetak pada {$slug}."
                    );

                    continue;
                }

                $this->assertStringContainsString(
                    $nilai,
                    $teks,
                    "Isian {$kunci} tidak tercetak pada {$slug}."
                );
            }
        }
    }

    public function test_kolom_isian_tidak_dikenal_diabaikan(): void
    {
        $pengajuan = $this->kirimPengajuan('domisili', [
            'isian_surat' => array_merge($this->isianUntuk('domisili'), [
                'kolom_palsu' => 'Nilai yang tidak boleh tersimpan',
            ]),
        ]);

        $this->assertArrayNotHasKey('kolom_palsu', (array) $pengajuan->isian_surat);
    }

    public function test_pratinjau_surat_mengembalikan_teks_resmi(): void
    {
        $respon = $this->postJson(route('pengajuan.pratinjau', 'domisili'), [
            'nama' => 'Siti Nurhaliza',
            'nik' => '1801010101010002',
            'tempat_lahir' => 'Lampung Selatan',
            'tanggal_lahir' => '1995-04-21',
            'alamat' => 'Dusun Mekar Sari RT 03',
            'isian' => $this->isianUntuk('domisili'),
        ]);

        $respon->assertOk()->assertJsonStructure(['teks']);

        $teks = (string) $respon->json('teks');

        $this->assertStringContainsString('Siti Nurhaliza', $teks);
        $this->assertStringContainsString('R O M S I, SH', $teks);
        $this->assertStringNotContainsString('{{', $teks);
    }

    // ---------------------------------------------------------------- unggah

    public function test_warga_mengunggah_dokumen_persyaratan(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();
        $syarat = $jenis->persyaratan()->firstOrFail();

        $pengajuan = $this->kirimPengajuan('domisili', [
            'dokumen' => [
                $syarat->id => UploadedFile::fake()->image('pengantar-rt.jpg')->size(300),
            ],
        ]);

        $this->assertSame(1, $pengajuan->dokumen()->count());

        $dokumen = $pengajuan->dokumen()->firstOrFail();
        $this->assertSame($syarat->label, $dokumen->label);
        Storage::disk('local')->assertExists($dokumen->path);
    }

    public function test_revisi_tanpa_unggah_ulang_dokumen_wajib_tetap_tersimpan(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();
        $syarat = $jenis->persyaratan()->firstOrFail();

        // Dokumen persyaratan dijadikan wajib: pengajuan pertama harus
        // mengunggahnya, tetapi revisi berikutnya TIDAK boleh menuntut unggah
        // ulang — halaman revisi menyatakan "Kosongkan bila tidak ingin
        // mengganti". Tanpa pengecualian ini, warga yang hanya memperbaiki
        // salah ketik tidak akan pernah bisa menyimpan perbaikannya.
        $syarat->update(['wajib' => true]);

        $pengajuan = $this->kirimPengajuan('domisili', [
            'dokumen' => [
                $syarat->id => UploadedFile::fake()->create('pengantar.pdf', 100, 'application/pdf'),
            ],
        ]);

        $this->assertSame(1, $pengajuan->dokumen()->count());

        $pengajuan->forceFill(['status' => StatusPengajuan::PerluPerbaikan->value])->save();

        $this->post(route('pengajuan.revisi.simpan', $pengajuan->nomor_referensi), $this->dataWarga([
            'alamat' => 'Dusun Mekar Sari RT 04/RW 02, Desa Karang Sari',
        ]))->assertSessionHasNoErrors()
            ->assertRedirect(route('cek-status.form'));

        $pengajuan->refresh();

        $this->assertSame(StatusPengajuan::MenungguVerifikasi, $pengajuan->status);
        $this->assertStringContainsString('RT 04', (string) $pengajuan->alamat);
        // Dokumen lama tidak boleh hilang hanya karena warga tidak menggantinya.
        $this->assertSame(1, $pengajuan->dokumen()->count());
    }

    public function test_dokumen_wajib_tetap_diminta_pada_pengajuan_baru(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();
        $syarat = $jenis->persyaratan()->firstOrFail();
        $syarat->update(['wajib' => true]);

        $this->post(route('pengajuan.store', 'domisili'), $this->dataWarga())
            ->assertSessionHasErrors('dokumen.'.$syarat->id);

        $this->assertSame(0, Pengajuan::query()->count());
    }

    /**
     * Mengunggah ulang berkas pada revisi harus MENGGANTI dokumen lama, bukan
     * menambah baris kedua untuk persyaratan yang sama.
     *
     * Tanpa ini, petugas melihat dua dokumen untuk satu syarat (yang lama
     * berstatus "ditolak"), halaman revisi membaca yang pertama sehingga warga
     * terus diberi tahu "sudah ada" padahal berkasnya baru diganti, dan berkas
     * lama tertinggal di disk selamanya.
     */
    public function test_unggah_ulang_dokumen_saat_revisi_menggantikan_dokumen_lama(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();
        $syarat = $jenis->persyaratan()->firstOrFail();

        $pengajuan = $this->kirimPengajuan('domisili', [
            'dokumen' => [
                $syarat->id => UploadedFile::fake()->create('pengantar-lama.pdf', 100, 'application/pdf'),
            ],
        ]);

        $lama = $pengajuan->dokumen()->firstOrFail();
        Storage::disk('local')->assertExists($lama->path);

        $pengajuan->forceFill(['status' => StatusPengajuan::PerluPerbaikan->value])->save();

        $this->post(route('pengajuan.revisi.simpan', $pengajuan->nomor_referensi), $this->dataWarga([
            'dokumen' => [
                $syarat->id => UploadedFile::fake()->create('pengantar-baru.pdf', 120, 'application/pdf'),
            ],
        ]))->assertSessionHasNoErrors();

        $pengajuan->refresh();

        // Tepat satu dokumen aktif untuk persyaratan tersebut.
        $this->assertSame(
            1,
            $pengajuan->dokumen()->where('persyaratan_surat_id', $syarat->id)->count()
        );

        // Baris lama hilang dan berkasnya ikut dibersihkan dari disk.
        $this->assertDatabaseMissing('dokumen_pengajuans', ['id' => $lama->id]);
        Storage::disk('local')->assertMissing($lama->path);

        // Yang tersisa adalah berkas pengganti.
        $baru = $pengajuan->dokumen()->firstOrFail();
        $this->assertSame('pengantar-baru.pdf', $baru->nama_asli);
        Storage::disk('local')->assertExists($baru->path);
    }

    /**
     * Bila warga TIDAK mengganti dokumen, dokumen lama harus tetap utuh.
     */
    public function test_dokumen_lama_bertahan_bila_revisi_tidak_menggantinya(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();
        $syarat = $jenis->persyaratan()->firstOrFail();

        $pengajuan = $this->kirimPengajuan('domisili', [
            'dokumen' => [
                $syarat->id => UploadedFile::fake()->create('pengantar.pdf', 100, 'application/pdf'),
            ],
        ]);

        $lama = $pengajuan->dokumen()->firstOrFail();

        $pengajuan->forceFill(['status' => StatusPengajuan::PerluPerbaikan->value])->save();

        $this->post(route('pengajuan.revisi.simpan', $pengajuan->nomor_referensi), $this->dataWarga())
            ->assertSessionHasNoErrors();

        $pengajuan->refresh();

        $this->assertSame(1, $pengajuan->dokumen()->count());
        $this->assertDatabaseHas('dokumen_pengajuans', ['id' => $lama->id]);
        Storage::disk('local')->assertExists($lama->path);
    }

    public function test_unggahan_mengikuti_batas_ukuran_per_persyaratan(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();
        $syarat = $jenis->persyaratan()->firstOrFail();

        // Admin mengizinkan berkas 5 MB. Batas generik 2 MB pada aturan
        // "dokumen.*" dulu menimpanya, sehingga berkas 3 MB yang sah ditolak.
        $syarat->update(['maks_kb' => 5120]);

        $pengajuan = $this->kirimPengajuan('domisili', [
            'dokumen' => [
                $syarat->id => UploadedFile::fake()->create('pengantar.pdf', 3000, 'application/pdf'),
            ],
        ]);

        $this->assertSame(1, $pengajuan->dokumen()->count());
    }

    public function test_unggahan_melebihi_batas_persyaratan_ditolak(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();
        $syarat = $jenis->persyaratan()->firstOrFail();
        $syarat->update(['maks_kb' => 64]);

        $this->post(route('pengajuan.store', 'domisili'), $this->dataWarga([
            'dokumen' => [
                $syarat->id => UploadedFile::fake()->create('pengantar.pdf', 500, 'application/pdf'),
            ],
        ]))->assertSessionHasErrors('dokumen.'.$syarat->id);
    }

    public function test_surat_tetap_dapat_diunduh_setelah_status_maju(): void
    {
        $pengajuan = $this->kirimPengajuan();

        // Alur sesudah persetujuan: Sedang Diproses -> Siap Diambil -> Selesai.
        // Warga tidak boleh kehilangan akses ke suratnya sendiri hanya karena
        // petugas memajukan status.
        foreach ([
            StatusPengajuan::Disetujui,
            StatusPengajuan::SedangDiproses,
            StatusPengajuan::SiapDiambil,
            StatusPengajuan::Selesai,
        ] as $status) {
            $pengajuan->forceFill(['status' => $status->value])->save();

            $this->get(route('pengajuan.surat', $pengajuan->nomor_referensi))
                ->assertOk();

            $this->get(route('cek-status.saya', $pengajuan->nomor_referensi))
                ->assertOk()
                ->assertSee('Unduh surat (.docx)');
        }
    }

    public function test_surat_tidak_dapat_diunduh_saat_menunggu_atau_ditolak(): void
    {
        $pengajuan = $this->kirimPengajuan();

        foreach ([StatusPengajuan::MenungguVerifikasi, StatusPengajuan::Ditolak, StatusPengajuan::PerluPerbaikan] as $status) {
            $pengajuan->forceFill(['status' => $status->value])->save();

            $this->get(route('pengajuan.surat', $pengajuan->nomor_referensi))->assertNotFound();
        }
    }

    public function test_pengajuan_tanpa_persetujuan_data_ditolak(): void
    {
        $data = $this->dataWarga();
        unset($data['persetujuan']);

        $this->post(route('pengajuan.store', 'domisili'), $data)
            ->assertSessionHasErrors('persetujuan');

        $this->assertSame(0, Pengajuan::query()->count());
    }

    // ---------------------------------------------------------------- akses

    public function test_surat_tidak_bisa_diunduh_tanpa_kode_akses(): void
    {
        $pengajuan = $this->kirimPengajuan();
        $pengajuan->forceFill(['status' => StatusPengajuan::Disetujui->value])->save();

        // Sesi pengirim dibuang: meniru warga lain yang hanya menebak nomor
        // referensi berurutan.
        $this->flushSession();

        $this->get(route('pengajuan.surat', $pengajuan->nomor_referensi))->assertNotFound();

        $this->get(route('pengajuan.surat', [
            'nomorReferensi' => $pengajuan->nomor_referensi,
            'kode' => $pengajuan->kode_akses,
        ]))->assertOk();
    }

    public function test_revisi_orang_lain_tidak_bisa_dibuka_tanpa_kode_akses(): void
    {
        $pengajuan = $this->kirimPengajuan();
        $pengajuan->forceFill(['status' => StatusPengajuan::PerluPerbaikan->value])->save();

        $this->flushSession();

        // Nomor referensi saja tidak cukup — baik untuk melihat maupun menulis.
        $this->get(route('pengajuan.revisi', $pengajuan->nomor_referensi))->assertNotFound();
        $this->get(route('pengajuan.revisi', [
            'nomorReferensi' => $pengajuan->nomor_referensi,
            'kode' => 'SALAH123',
        ]))->assertNotFound();

        $this->post(
            route('pengajuan.revisi.simpan', $pengajuan->nomor_referensi),
            $this->dataWarga(['alamat' => 'Alamat yang tidak boleh tersimpan'])
        )->assertNotFound();

        $this->assertNotSame('Alamat yang tidak boleh tersimpan', $pengajuan->refresh()->alamat);
    }

    public function test_halaman_publik_utama_dapat_dibuka_warga(): void
    {
        foreach ([
            '/', '/profil', '/informasi', '/potensi', '/kontak',
            '/pelayanan', '/pengajuan?jenis=domisili', '/cek-status',
            '/panduan', '/kebijakan-privasi', '/pemberitahuan-data',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_jenis_surat_nonaktif_tidak_bisa_diajukan(): void
    {
        JenisSurat::query()->where('slug', 'domisili')->update(['is_aktif' => false]);

        $this->get('/pelayanan/domisili')->assertNotFound();
        $this->get('/pengajuan?jenis=domisili')->assertNotFound();
    }
}
