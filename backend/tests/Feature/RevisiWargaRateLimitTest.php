<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuan;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug yang dilaporkan warga: setelah admin meminta perbaikan, warga membuka
 * halaman revisi dan mengirim ulang isiannya, tetapi yang muncul halaman
 * "429 Too Many Requests" alih-alih formulir revisi / konfirmasi terkirim.
 *
 * Penyebabnya: setiap route throttle di aplikasi memakai bucket yang SAMA,
 * karena Laravel 13 hanya memakai IP + domain sebagai kunci throttle dan
 * mengabaikan nama route. Akibatnya pratinjau surat yang dipanggil berkali-kali
 * saat warga mengetik (throttle:60,1) menghabiskan kuota yang sama dengan
 * halaman revisi (throttle:20,1) dan kirim revisi (throttle:5,10).
 */
class RevisiWargaRateLimitTest extends TestCase
{
    use RefreshDatabase;

    /** Kode akses pengajuan uji (bukan nomor referensi). */
    private const KODE = 'UJI2345A';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ProfilDesaSeeder::class, JenisSuratSeeder::class]);
    }

    /** Pengajuan berstatus "Perlu Perbaikan" yang siap direvisi warga. */
    protected function pengajuanPerluPerbaikan(): Pengajuan
    {
        $jenis = JenisSurat::query()->where('slug', 'domisili')->firstOrFail();

        $pengajuan = new Pengajuan;
        $pengajuan->forceFill([
            'jenis_surat_id' => $jenis->id,
            'nomor_referensi' => 'KRS-2026-0011',
            'kode_akses' => self::KODE,
            'nama' => 'Faiz Ridho',
            'nik' => '1801010101010001',
            'tempat_lahir' => 'Lampung Selatan',
            'tanggal_lahir' => '1990-03-14',
            'alamat' => 'Sukamaju RT 02/RW 05',
            'keperluan' => 'Uji',
            'whatsapp' => '082237751273',
            'isian_surat' => [
                'agama' => 'Islam',
                'status_perkawinan' => 'Kawin',
                'pekerjaan' => 'Petani',
            ],
            'persetujuan_data' => true,
            'persetujuan_at' => now(),
        ]);
        $pengajuan->isiStatus(StatusPengajuan::PerluPerbaikan);
        $pengajuan->catatan_admin = 'Acc bro';
        $pengajuan->save();

        return $pengajuan->refresh();
    }

    /** Data formulir revisi yang lengkap dan sah. */
    protected function dataRevisi(): array
    {
        return [
            'nama' => 'Faiz Ridho',
            'nik' => '1801010101010001',
            'tempat_lahir' => 'Lampung Selatan',
            'tanggal_lahir' => '1990-03-14',
            'alamat' => 'Sukamaju RT 02/RW 05',
            'keperluan' => 'Uji revisi',
            'whatsapp' => '082237751273',
            'persetujuan' => '1',
            'isian_surat' => [
                'agama' => 'Islam',
                'status_perkawinan' => 'Kawin',
                'pekerjaan' => 'Petani',
            ],
        ];
    }

    /**
     * Pratinjau surat yang dipanggil saat warga mengetik TIDAK boleh
     * menghabiskan kuota halaman revisi maupun kuota kirim revisi.
     */
    public function test_pratinjau_saat_mengetik_tidak_menghabiskan_kuota_revisi(): void
    {
        $pengajuan = $this->pengajuanPerluPerbaikan();

        // Warga membuka halaman revisi.
        $this->get(route('pengajuan.revisi', ['nomorReferensi' => $pengajuan->nomor_referensi, 'kode' => self::KODE]))->assertOk();

        // Lalu mengetik: pratinjau dipanggil berkali-kali (debounce 260 ms,
        // tapi warga bisa mengisi puluhan kolom).
        for ($i = 0; $i < 25; $i++) {
            $this->postJson(route('pengajuan.pratinjau', $pengajuan->jenisSurat), [
                'nama' => 'Faiz Ridho',
                'isian' => ['agama' => 'Islam'],
            ])->assertOk();
        }

        // Halaman revisi masih harus terbuka (bukan 429).
        $this->get(route('pengajuan.revisi', ['nomorReferensi' => $pengajuan->nomor_referensi, 'kode' => self::KODE]))->assertOk();

        // Dan kirim revisi harus berhasil.
        $this->post(route('pengajuan.revisi.simpan', ['nomorReferensi' => $pengajuan->nomor_referensi, 'kode' => self::KODE]), $this->dataRevisi())
            ->assertRedirect(route('cek-status.form'));

        $pengajuan->refresh();
        $this->assertSame(StatusPengajuan::MenungguVerifikasi, $pengajuan->status);
        $this->assertSame(1, (int) $pengajuan->revisi_ke);
    }

    /**
     * Halaman revisi dan kirim revisi juga tidak boleh saling menghabiskan
     * kuota, karena keduanya dipakai berurutan oleh warga yang sama.
     */
    public function test_halaman_revisi_dan_kirim_revisi_punya_kuota_terpisah(): void
    {
        $pengajuan = $this->pengajuanPerluPerbaikan();

        // Warga membuka halaman revisi berkali-kali (mis. me-refresh karena
        // ragu, atau kembali dari halaman pratinjau).
        for ($i = 0; $i < 19; $i++) {
            $this->get(route('pengajuan.revisi', ['nomorReferensi' => $pengajuan->nomor_referensi, 'kode' => self::KODE]))->assertOk();
        }

        // Kirim revisi tetap harus bisa, tidak ikut kena 429.
        $this->post(route('pengajuan.revisi.simpan', ['nomorReferensi' => $pengajuan->nomor_referensi, 'kode' => self::KODE]), $this->dataRevisi())
            ->assertRedirect(route('cek-status.form'));

        $this->assertSame(1, (int) $pengajuan->refresh()->revisi_ke);
    }

    /**
     * Revisi harus divalidasi sama ketatnya dengan pengajuan baru.
     *
     * Sebelumnya rute revisi tidak punya parameter {jenisSurat}, sehingga
     * aturan isian surat dan dokumen persyaratan dilewati seluruhnya: warga
     * bisa mengirim perbaikan dengan isian kosong dan suratnya tercetak
     * titik-titik.
     */
    public function test_revisi_tetap_mewajibkan_isian_surat(): void
    {
        $pengajuan = $this->pengajuanPerluPerbaikan();

        $data = $this->dataRevisi();
        $data['isian_surat'] = []; // isian dikosongkan

        $this->post(route('pengajuan.revisi.simpan', ['nomorReferensi' => $pengajuan->nomor_referensi, 'kode' => self::KODE]), $data)
            ->assertSessionHasErrors([
                'isian_surat.agama',
                'isian_surat.status_perkawinan',
                'isian_surat.pekerjaan',
            ]);

        // Status tidak boleh berubah bila validasi gagal.
        $pengajuan->refresh();
        $this->assertSame(StatusPengajuan::PerluPerbaikan, $pengajuan->status);
        $this->assertSame(0, (int) $pengajuan->revisi_ke);
    }

    /**
     * Halaman 429 harus ramah warga, bukan halaman putih bawaan Laravel.
     *
     * Diuji lewat pencarian status, karena route itu selalu bisa diakses
     * (tidak bergantung pada status pengajuan seperti halaman revisi).
     */
    public function test_halaman_429_menampilkan_pesan_ramah(): void
    {
        $this->pengajuanPerluPerbaikan();

        // Batas pencarian status 10 percobaan per menit.
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('cek-status.cari'), [
                'nomor_referensi' => 'KRS-2026-0011',
                'kode_akses' => self::KODE,
            ])->assertOk();
        }

        $respons = $this->post(route('cek-status.cari'), [
            'nomor_referensi' => 'KRS-2026-0011',
            'kode_akses' => self::KODE,
        ]);

        $respons->assertStatus(429);
        // Halaman memakai tema desa, bukan halaman bawaan Laravel berbahasa Inggris.
        $respons->assertSee('Permintaan Anda terlalu sering');
        $respons->assertSee('Kembali ke beranda');
        $respons->assertDontSee('Too Many Requests');
    }
}
