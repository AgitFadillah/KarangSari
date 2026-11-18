<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Enums\StatusPengajuan;
use App\Models\DokumenPengajuan;
use App\Models\JenisSurat;
use App\Models\LogAktivitas;
use App\Models\Pengajuan;
use App\Models\User;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Verifikasi dokumen per berkas oleh admin, dan pencarian status oleh warga.
 */
class VerifikasiDokumenTest extends TestCase
{
    use RefreshDatabase;

    private const SANDI = 'sandi-uji-test-1234';

    protected User $verifikator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProfilDesaSeeder::class, JenisSuratSeeder::class]);

        $this->verifikator = User::create([
            'name' => 'Rangga Pratama',
            'username' => 'rangga',
            'role' => RoleAdmin::Verifikator,
            'is_active' => true,
            'password' => Hash::make(self::SANDI),
        ]);
    }

    protected function pengajuan(string $nomor = 'KRS-2026-0001'): Pengajuan
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();

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
        $pengajuan->nomor_referensi = $nomor;
        // Kode unik per pengajuan (kolomnya berindeks unik).
        $pengajuan->kode_akses = 'V'.strtoupper(substr(md5($nomor), 0, 9));
        $pengajuan->isiStatus(StatusPengajuan::MenungguVerifikasi);
        $pengajuan->save();

        return $pengajuan;
    }

    protected function dokumen(Pengajuan $pengajuan): DokumenPengajuan
    {
        return $pengajuan->dokumen()->create([
            'label' => 'KTP pemohon',
            'path' => 'dokumen/2026/'.$pengajuan->nomor_referensi.'/ktp.pdf',
            'nama_asli' => 'ktp.pdf',
            'mime' => 'application/pdf',
            'ukuran' => 2048,
            'status_verifikasi' => DokumenPengajuan::MENUNGGU,
        ]);
    }

    public function test_verifikator_dapat_menyetujui_dokumen(): void
    {
        $pengajuan = $this->pengajuan();
        $dokumen = $this->dokumen($pengajuan);

        $this->actingAs($this->verifikator)
            ->patch(route('admin.pengajuan.dokumen.status', [$pengajuan, $dokumen]), [
                'status_verifikasi' => DokumenPengajuan::TERVERIFIKASI,
            ])
            ->assertRedirect(route('admin.pengajuan.show', $pengajuan));

        $this->assertSame(DokumenPengajuan::TERVERIFIKASI, $dokumen->fresh()->status_verifikasi);
    }

    public function test_verifikator_dapat_menolak_dokumen(): void
    {
        $pengajuan = $this->pengajuan();
        $dokumen = $this->dokumen($pengajuan);

        $this->actingAs($this->verifikator)
            ->patch(route('admin.pengajuan.dokumen.status', [$pengajuan, $dokumen]), [
                'status_verifikasi' => DokumenPengajuan::DITOLAK,
            ])
            ->assertRedirect(route('admin.pengajuan.show', $pengajuan));

        $this->assertSame(DokumenPengajuan::DITOLAK, $dokumen->fresh()->status_verifikasi);
    }

    public function test_status_dokumen_tidak_dikenal_ditolak(): void
    {
        $pengajuan = $this->pengajuan();
        $dokumen = $this->dokumen($pengajuan);

        $this->actingAs($this->verifikator)
            ->patch(route('admin.pengajuan.dokumen.status', [$pengajuan, $dokumen]), [
                'status_verifikasi' => 'entah',
            ])
            ->assertSessionHasErrors('status_verifikasi');

        $this->assertSame(DokumenPengajuan::MENUNGGU, $dokumen->fresh()->status_verifikasi);
    }

    public function test_dokumen_milik_pengajuan_lain_tidak_bisa_diverifikasi(): void
    {
        $pengajuanA = $this->pengajuan('KRS-2026-0001');
        $pengajuanB = $this->pengajuan('KRS-2026-0002');
        $dokumenB = $this->dokumen($pengajuanB);

        // Dokumen B diakses lewat URL pengajuan A harus 404, bukan diam-diam lolos.
        $this->actingAs($this->verifikator)
            ->patch(route('admin.pengajuan.dokumen.status', [$pengajuanA, $dokumenB]), [
                'status_verifikasi' => DokumenPengajuan::TERVERIFIKASI,
            ])
            ->assertNotFound();

        $this->assertSame(DokumenPengajuan::MENUNGGU, $dokumenB->fresh()->status_verifikasi);
    }

    public function test_operator_konten_tidak_boleh_memverifikasi_dokumen(): void
    {
        $pengajuan = $this->pengajuan();
        $dokumen = $this->dokumen($pengajuan);

        $operator = User::create([
            'name' => 'Operator',
            'username' => 'operator',
            'role' => RoleAdmin::OperatorKonten,
            'is_active' => true,
            'password' => Hash::make(self::SANDI),
        ]);

        $this->actingAs($operator)
            ->patch(route('admin.pengajuan.dokumen.status', [$pengajuan, $dokumen]), [
                'status_verifikasi' => DokumenPengajuan::TERVERIFIKASI,
            ])
            ->assertForbidden();
    }

    public function test_membuka_dokumen_dicatat_di_log(): void
    {
        $pengajuan = $this->pengajuan();
        $dokumen = $this->dokumen($pengajuan);

        // Berkas tidak ada di disk -> 404, tapi akses tetap tercatat.
        $this->actingAs($this->verifikator)
            ->get(route('admin.pengajuan.dokumen', [$pengajuan, $dokumen]))
            ->assertNotFound();

        $this->assertTrue(
            LogAktivitas::query()
                ->where('tindakan', 'Membuka dokumen pengajuan')
                ->where('subjek', $pengajuan->nomor_referensi)
                ->exists()
        );
    }

    public function test_warga_dapat_mencari_status_dengan_nomor_referensi(): void
    {
        $pengajuan = $this->pengajuan();

        $this->post(route('cek-status.cari'), [
            'nomor_referensi' => $pengajuan->nomor_referensi,
            'kode_akses' => $pengajuan->kode_akses,
        ])
            ->assertOk()
            ->assertSee($pengajuan->nomor_referensi)
            ->assertSee($pengajuan->jenisSurat->nama);
    }

    public function test_cek_status_tidak_membocorkan_nik_atau_alamat(): void
    {
        $pengajuan = $this->pengajuan();

        $this->post(route('cek-status.cari'), [
            'nomor_referensi' => $pengajuan->nomor_referensi,
            'kode_akses' => $pengajuan->kode_akses,
        ])
            ->assertOk()
            ->assertDontSee($pengajuan->nik)
            ->assertDontSee($pengajuan->alamat);
    }

    public function test_cek_status_menerima_huruf_kecil(): void
    {
        $pengajuan = $this->pengajuan();

        $this->post(route('cek-status.cari'), [
            'nomor_referensi' => strtolower($pengajuan->nomor_referensi),
            'kode_akses' => strtolower($pengajuan->kode_akses),
        ])
            ->assertOk()
            ->assertSee($pengajuan->nomor_referensi);
    }

    public function test_nomor_referensi_tidak_dikenal_ditolak(): void
    {
        $this->from(route('cek-status.form'))
            ->post(route('cek-status.cari'), ['nomor_referensi' => 'KRS-2026-9999'])
            ->assertRedirect(route('cek-status.form'))
            ->assertSessionHasErrors('kode_akses');
    }
}
