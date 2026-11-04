<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Enums\StatusPengajuan;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Models\User;
use Database\Seeders\DemoPengajuanSeeder;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPengajuanTest extends TestCase
{
    use RefreshDatabase;

    /** Sandi dummy khusus test — TIDAK sama dengan sandi admin sungguhan. */
    private const SANDI_UJI = 'sandi-uji-test-1234';

    protected User $verifikator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProfilDesaSeeder::class, JenisSuratSeeder::class]);

        $this->verifikator = User::create([
            'name' => 'Rangga Pratama',
            'username' => 'rangga',
            'password' => Hash::make(self::SANDI_UJI),
            'role' => RoleAdmin::Verifikator,
            'is_active' => true,
        ]);
    }

    protected function pengajuan(StatusPengajuan $status = StatusPengajuan::MenungguVerifikasi): Pengajuan
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();

        // nomor_referensi & status sengaja di luar fillable, jadi diisi manual.
        $pengajuan = new Pengajuan;
        $pengajuan->fill([
            'jenis_surat_id' => $jenis->id,
            'nama' => 'Budi Santoso',
            'nik' => '3201121508870003',
            'tempat_lahir' => 'Bogor',
            'tanggal_lahir' => '1987-08-15',
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Kp. Pasir Angin RT 02/RW 05',
            'keperluan' => 'Administrasi sekolah',
            'whatsapp' => '081234567890',
            'persetujuan_data' => true,
            'persetujuan_at' => now(),
        ]);
        $pengajuan->nomor_referensi = 'SBM-'.now()->year.'-0001';
        $pengajuan->isiStatus($status);
        $pengajuan->save();

        return $pengajuan;
    }

    public function test_daftar_pengajuan_tampil_dengan_filter_dan_pencarian(): void
    {
        $this->pengajuan();

        $this->actingAs($this->verifikator)
            ->get('/admin/pengajuan')
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('SBM-'.now()->year.'-0001');

        $this->actingAs($this->verifikator)
            ->get('/admin/pengajuan?q=tidak-ada-yang-cocok')
            ->assertOk()
            ->assertSee('Pengajuan tidak ditemukan');

        $this->actingAs($this->verifikator)
            ->get('/admin/pengajuan?status=menunggu_verifikasi')
            ->assertOk()
            ->assertSee('Budi Santoso');
    }

    public function test_detail_pengajuan_tampil(): void
    {
        $pengajuan = $this->pengajuan();

        $this->actingAs($this->verifikator)
            ->get(route('admin.pengajuan.show', $pengajuan))
            ->assertOk()
            ->assertSee($pengajuan->nomor_referensi)
            ->assertSee('Ubah status pengajuan');
    }

    public function test_transisi_status_valid_mengubah_status_dan_mencatat_riwayat(): void
    {
        $pengajuan = $this->pengajuan();

        $this->actingAs($this->verifikator)
            ->patch(route('admin.pengajuan.status', $pengajuan), [
                'status' => StatusPengajuan::Disetujui->value,
                'catatan' => 'Dokumen lengkap.',
            ])
            ->assertRedirect(route('admin.pengajuan.show', $pengajuan));

        $pengajuan->refresh();

        $this->assertSame(StatusPengajuan::Disetujui, $pengajuan->status);
        $this->assertSame($this->verifikator->id, $pengajuan->diproses_oleh);
        $this->assertNotNull($pengajuan->diverifikasi_at);
        $this->assertCount(1, $pengajuan->riwayat);
        $this->assertDatabaseHas('log_aktivitets', [
            'subjek' => $pengajuan->nomor_referensi,
            'user_id' => $this->verifikator->id,
        ]);
    }

    public function test_transisi_status_tidak_valid_ditolak(): void
    {
        $pengajuan = $this->pengajuan(StatusPengajuan::Selesai);

        $this->actingAs($this->verifikator)
            ->patch(route('admin.pengajuan.status', $pengajuan), [
                'status' => StatusPengajuan::Disetujui->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(StatusPengajuan::Selesai, $pengajuan->fresh()->status);
    }

    public function test_menolak_pengajuan_wajib_catatan(): void
    {
        $pengajuan = $this->pengajuan();

        $this->actingAs($this->verifikator)
            ->patch(route('admin.pengajuan.status', $pengajuan), [
                'status' => StatusPengajuan::Ditolak->value,
            ])
            ->assertSessionHasErrors('catatan');

        $this->assertSame(StatusPengajuan::MenungguVerifikasi, $pengajuan->fresh()->status);
    }

    public function test_operator_konten_tidak_boleh_mengubah_status(): void
    {
        $operator = User::create([
            'name' => 'Dewi Lestari',
            'username' => 'dewi',
            'password' => Hash::make(self::SANDI_UJI),
            'role' => RoleAdmin::OperatorKonten,
            'is_active' => true,
        ]);

        $pengajuan = $this->pengajuan();

        $this->actingAs($operator)
            ->patch(route('admin.pengajuan.status', $pengajuan), [
                'status' => StatusPengajuan::Disetujui->value,
            ])
            ->assertForbidden();

        $this->assertSame(StatusPengajuan::MenungguVerifikasi, $pengajuan->fresh()->status);
    }

    public function test_dokumen_privat_tidak_bisa_diakses_publik(): void
    {
        $pengajuan = $this->pengajuan();

        $dokumen = $pengajuan->dokumen()->create([
            'label' => 'KTP pemohon',
            'path' => 'dokumen/2026/SBM-2026-0001/ktp.pdf',
            'nama_asli' => 'ktp.pdf',
            'mime' => 'application/pdf',
            'ukuran' => 1024,
        ]);

        $this->get('/storage/dokumen/2026/SBM-2026-0001/ktp.pdf')->assertNotFound();
        $this->get(route('admin.pengajuan.dokumen', [$pengajuan, $dokumen]))->assertRedirect(route('admin.login'));
    }

    public function test_halaman_admin_lainnya_dapat_dibuka(): void
    {
        $administrator = User::create([
            'name' => 'Sari Wulandari',
            'username' => 'sari',
            'password' => Hash::make(self::SANDI_UJI),
            'role' => RoleAdmin::Administrator,
            'is_active' => true,
        ]);

        $this->seed(DemoPengajuanSeeder::class);

        foreach (['/admin', '/admin/pengajuan', '/admin/jenis-surat', '/admin/konten', '/admin/profil', '/admin/akun', '/admin/log'] as $url) {
            $this->actingAs($administrator)->get($url)->assertOk();
        }
    }

    public function test_ubah_konfigurasi_jenis_surat_menyimpan_persyaratan(): void
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();

        $this->actingAs($this->verifikator)
            ->put(route('admin.jenis-surat.update', $jenis), [
                'kode' => 'SKD',
                'nama' => 'Surat Keterangan Domisili',
                'deskripsi' => 'Deskripsi baru.',
                'estimasi_hari' => '2 hari kerja',
                'persyaratan' => "KTP pemohon\nKartu Keluarga\nSurat pengantar RT/RW",
                'is_aktif' => '1',
            ])
            ->assertRedirect(route('admin.jenis-surat.edit', $jenis));

        $jenis->refresh();

        $this->assertSame('2 hari kerja', $jenis->estimasi_hari);
        $this->assertSame(
            ['KTP pemohon', 'Kartu Keluarga', 'Surat pengantar RT/RW'],
            $jenis->persyaratan()->orderBy('urutan')->pluck('label')->all()
        );
    }

    public function test_batas_rate_limit_form_pengajuan(): void
    {
        Storage::fake('local');

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('pengajuan.store', 'domisili'), []);
        }

        $this->post(route('pengajuan.store', 'domisili'), [])->assertStatus(429);
    }
}
