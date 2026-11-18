<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Models\User;
use App\Services\TemplateSuratService;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Halaman admin "Jenis Surat": daftar, pencarian, tambah, konfigurasi,
 * aktif/nonaktif, dan hapus.
 *
 * Fokus pengujian: perubahan konfigurasi di sini harus benar-benar sampai ke
 * formulir warga (field & persyaratan), karena itulah inti halaman ini.
 */
class AdminJenisSuratTest extends TestCase
{
    use RefreshDatabase;

    protected const SANDI = 'sandi-uji-yang-panjang';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProfilDesaSeeder::class);
        $this->seed(JenisSuratSeeder::class);
    }

    protected function admin(?RoleAdmin $peran = null): User
    {
        return User::query()->firstOrCreate(
            ['username' => 'uji'],
            [
                'name' => 'Uji Admin',
                'email' => 'uji@karangsari.desa.id',
                'password' => Hash::make(self::SANDI),
                'role' => $peran ?? RoleAdmin::Administrator,
                'is_active' => true,
            ]
        );
    }

    public function test_daftar_menampilkan_ringkasan_dan_jumlah_kolom(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.jenis-surat.index'))
            ->assertOk()
            ->assertSee('Total layanan')
            ->assertSee('Field formulir')
            ->assertSee('Persyaratan dokumen')
            ->assertSee('kolom')
            ->assertSee('dokumen');
    }

    public function test_pencarian_dan_filter_aktif(): void
    {
        JenisSurat::query()->where('slug', 'domisili')->update(['is_aktif' => false]);

        $this->actingAs($this->admin())
            ->get(route('admin.jenis-surat.index', ['q' => 'Usaha']))
            ->assertOk()
            ->assertSee('Surat Keterangan Usaha')
            ->assertDontSee('Surat Keterangan Domisili');

        $this->actingAs($this->admin())
            ->get(route('admin.jenis-surat.index', ['aktif' => 1]))
            ->assertOk()
            ->assertDontSee('Surat Keterangan Domisili');
    }

    public function test_pencarian_tanpa_hasil_menampilkan_keadaan_kosong(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.jenis-surat.index', ['q' => 'tidakadasamasekali']))
            ->assertOk()
            ->assertSee('Tidak ada jenis surat yang cocok');
    }

    public function test_admin_dapat_menambah_jenis_surat_baru(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.jenis-surat.store'), [
                'kode' => 'SKTM2',
                'nama' => 'Surat Keterangan Penghasilan',
                'deskripsi' => 'Untuk keperluan beasiswa.',
                'estimasi_hari' => '3 hari kerja',
                'template_surat' => 'Menerangkan bahwa {{nama}} berpenghasilan {{penghasilan}}.',
                'field' => [
                    ['label' => 'Penghasilan per bulan', 'kunci' => 'penghasilan', 'tipe' => 'number', 'wajib' => '1'],
                ],
                'persyaratan' => [
                    ['label' => 'Slip gaji', 'format' => 'PDF', 'maks_kb' => 1024, 'wajib' => '1'],
                ],
                'is_aktif' => '1',
            ])
            ->assertRedirect();

        $jenis = JenisSurat::query()->where('kode', 'SKTM2')->firstOrFail();

        $this->assertSame('Surat Keterangan Penghasilan', $jenis->nama);
        $this->assertSame('surat-keterangan-penghasilan', $jenis->slug);
        $this->assertTrue($jenis->is_aktif);
        $this->assertSame('penghasilan', $jenis->fieldFormulir()[0]['kunci']);
        $this->assertSame('number', $jenis->fieldFormulir()[0]['tipe']);
        $this->assertSame('pdf', $jenis->persyaratan()->firstOrFail()->format);
    }

    public function test_jenis_surat_baru_langsung_tampil_di_formulir_warga(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.jenis-surat.store'), [
                'kode' => 'SKD2',
                'nama' => 'Surat Keterangan Beda Nama',
                'template_surat' => 'Menerangkan bahwa {{nama}} dengan nama lain {{nama_lain}}.',
                'is_aktif' => '1',
            ]);

        $jenis = JenisSurat::query()->where('kode', 'SKD2')->firstOrFail();

        // Muncul di katalog publik...
        $this->get('/pelayanan')->assertOk()->assertSee('Surat Keterangan Beda Nama');

        // ...dan kolom isiannya terbentuk otomatis dari template.
        $this->get('/pengajuan?jenis='.$jenis->slug)
            ->assertOk()
            ->assertSee('Nama lain')
            ->assertSee('name="isian_surat[nama_lain]"', false);
    }

    public function test_field_tanpa_kunci_diturunkan_dari_label(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.jenis-surat.store'), [
                'kode' => 'SKX',
                'nama' => 'Surat Uji Kunci',
                'field' => [
                    ['label' => 'Jumlah Tanggungan', 'kunci' => '', 'tipe' => 'text', 'wajib' => ''],
                ],
                'is_aktif' => '1',
            ]);

        $jenis = JenisSurat::query()->where('kode', 'SKX')->firstOrFail();

        $this->assertSame('jumlah_tanggungan', $jenis->fieldFormulir()[0]['kunci']);
        $this->assertFalse($jenis->fieldFormulir()[0]['wajib']);
    }

    public function test_kode_ganda_ditolak(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.jenis-surat.store'), [
                'kode' => 'SKD',
                'nama' => 'Duplikat',
                'is_aktif' => '1',
            ])
            ->assertSessionHasErrors('kode');
    }

    public function test_toggle_aktif_menyembunyikan_dari_katalog_publik(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();

        $this->actingAs($this->admin())
            ->patch(route('admin.jenis-surat.toggle', $jenis))
            ->assertRedirect();

        $this->assertFalse($jenis->refresh()->is_aktif);

        $this->get('/pelayanan')->assertOk()->assertDontSee('Surat Keterangan Domisili');
    }

    public function test_jenis_surat_yang_sudah_dipakai_tidak_boleh_dihapus(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();

        // Nomor referensi dibangkitkan service, bukan diisi manual.
        $pengajuan = app(\App\Services\PengajuanService::class)->buat(
            $jenis,
            [
                'nama' => 'Warga Uji',
                'alamat' => 'Karang Sari',
                'keperluan' => 'Uji',
                'whatsapp' => '081234567890',
                'persetujuan_data' => true,
            ],
            [],
            '127.0.0.1',
        );

        $this->assertNotNull($pengajuan->nomor_referensi);

        $this->actingAs($this->admin())
            ->delete(route('admin.jenis-surat.destroy', $jenis))
            ->assertSessionHasErrors('jenis_surat');

        $this->assertNotNull($jenis->fresh());
    }

    public function test_jenis_surat_tanpa_pengajuan_dapat_dihapus(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();

        $this->actingAs($this->admin())
            ->delete(route('admin.jenis-surat.destroy', $jenis))
            ->assertRedirect(route('admin.jenis-surat.index'));

        $this->assertNull($jenis->fresh());
    }

    public function test_operator_konten_tidak_boleh_mengubah_jenis_surat(): void
    {
        $this->actingAs($this->admin(RoleAdmin::OperatorKonten))
            ->get(route('admin.jenis-surat.index'))
            ->assertForbidden();
    }

    public function test_konfigurasi_field_eksplisit_mengalahkan_tebakan_template(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();
        $jenis->update([
            'template_surat' => 'Menerangkan {{nama}} tinggal sejak {{lama_tinggal}}.',
            'field_formulir' => [
                ['kunci' => 'lama_tinggal', 'label' => 'Sudah tinggal berapa lama', 'tipe' => 'text', 'wajib' => false],
            ],
        ]);

        $penanda = app(TemplateSuratService::class)->penandaKustom($jenis->fresh());

        $this->assertCount(1, $penanda);
        // Label dari konfigurasi dipakai, bukan hasil olahan kunci.
        $this->assertSame('Sudah tinggal berapa lama', $penanda[0]['label']);
        $this->assertFalse($penanda[0]['wajib']);
    }

    public function test_halaman_edit_dan_tambah_dapat_dibuka(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();

        $this->actingAs($this->admin())
            ->get(route('admin.jenis-surat.create'))
            ->assertOk()
            ->assertSee('Tambah field')
            ->assertSee('Tambah persyaratan')
            ->assertSee('Template surat');

        $this->actingAs($this->admin())
            ->get(route('admin.jenis-surat.edit', $jenis))
            ->assertOk()
            ->assertSee($jenis->nama);
    }
}
