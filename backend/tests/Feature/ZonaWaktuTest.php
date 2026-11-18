<?php

namespace Tests\Feature;

use App\Enums\RoleAdmin;
use App\Models\User;
use App\Support\SiteProfile;
use Database\Seeders\JenisSuratSeeder;
use Database\Seeders\KontenWebsiteSeeder;
use Database\Seeders\ProfilDesaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Jam yang tampil ke warga harus jam WIB, bukan UTC.
 *
 * Kenapa ini diuji?
 *
 * Seluruh halaman menulis akhiran "WIB" secara harfiah: "Diajukan 18 November
 * 2026, 20.39 WIB", "Disetujui ... WIB", dan tanggal pada surat resmi. Bila
 * config('app.timezone') dibiarkan "UTC" seperti bawaan Laravel, aplikasi
 * menyimpan dan mencetak jam UTC, sehingga warga yang mengirim pukul 20.39 WIB
 * melihat "13.39 WIB" — selisih 7 jam pada bukti pengajuan, riwayat status, dan
 * tanggal surat. Kesalahan seperti ini tidak memunculkan galat apa pun, jadi
 * hanya terlihat kalau ada yang membandingkan jam di layar dengan jam dinding.
 *
 * Uji ini mengunci zona waktu aplikasi dan memastikan tanggal surat mengikuti
 * tanggal pengajuan yang tersimpan.
 */
class ZonaWaktuTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([ProfilDesaSeeder::class, JenisSuratSeeder::class, KontenWebsiteSeeder::class]);
        SiteProfile::lupakan();

        $this->admin = User::create([
            'name' => 'Admin Zona Waktu',
            'username' => 'adminzona',
            'password' => Hash::make('sandi-uji-zona-1234'),
            'role' => RoleAdmin::Administrator,
            'is_active' => true,
        ]);
    }

    public function test_zona_waktu_aplikasi_bukan_utc(): void
    {
        $this->assertSame(
            'Asia/Jakarta',
            config('app.timezone'),
            'Zona waktu aplikasi harus Asia/Jakarta; halaman menulis "WIB" secara harfiah.'
        );
    }

    /**
     * now() harus mengikuti jam WIB, bukan UTC.
     */
    public function test_now_mengikuti_jam_wib(): void
    {
        // Patok waktu nyata agar hasilnya tidak bergantung jam mesin uji.
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 20, 39, 0, 'Asia/Jakarta'));

        try {
            $this->assertSame('2026-11-18 20:39:00', now()->format('Y-m-d H:i:s'));
            $this->assertSame(
                '18 November 2026, 20.39',
                now()->locale('id')->translatedFormat('j F Y, H.i')
            );

            // Jam UTC pada saat yang sama memang 13.39 — inilah angka salah
            // yang dulu tampil ke warga.
            $this->assertSame('2026-11-18 13:39:00', now('UTC')->format('Y-m-d H:i:s'));
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Halaman cek status menampilkan jam pengajuan dalam WIB.
     */
    public function test_halaman_cek_status_menampilkan_jam_wib(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 20, 39, 0, 'Asia/Jakarta'));

        try {
            $jenis = \App\Models\JenisSurat::query()->where('kode', 'SKD')->firstOrFail();

            $pengajuan = app(\App\Services\PengajuanService::class)->buat($jenis, [
                'nama' => 'Warga Zona Waktu',
                'nik' => '1802111509900003',
                'tempat_lahir' => 'Tanjung Sari',
                'tanggal_lahir' => '1990-09-15',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'Dusun I RT 01',
                'keperluan' => 'Uji zona waktu',
                'whatsapp' => '081234567892',
                'persetujuan_data' => true,
            ], [], '127.0.0.1');

            $res = $this->get(route('cek-status.saya', [
                'nomorReferensi' => $pengajuan->nomor_referensi,
                'kode' => $pengajuan->kode_akses,
            ]));

            $res->assertOk();
            $res->assertSee('20.39', false);
            // Angka UTC tidak boleh muncul sebagai jam pengajuan.
            $res->assertDontSee('13.39', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Tanggal pada surat resmi mengikuti tanggal pengajuan (WIB), bukan UTC.
     *
     * Pengajuan yang dikirim pukul 00.30 WIB akan bertanggal "hari ini",
     * sedangkan dalam UTC masih "kemarin" — perbedaan yang terlihat jelas
     * pada surat yang dicetak.
     */
    public function test_tanggal_surat_mengikuti_wib_bukan_utc(): void
    {
        // 1 Desember 2026 pukul 00.30 WIB = 30 November 2026 pukul 17.30 UTC.
        Carbon::setTestNow(Carbon::create(2026, 12, 1, 0, 30, 0, 'Asia/Jakarta'));

        try {
            $jenis = \App\Models\JenisSurat::query()->where('kode', 'SKD')->firstOrFail();

            $pengajuan = app(\App\Services\PengajuanService::class)->buat($jenis, [
                'nama' => 'Warga Tengah Malam',
                'nik' => '1802111509900004',
                'tempat_lahir' => 'Tanjung Sari',
                'tanggal_lahir' => '1990-09-15',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'Dusun I RT 01',
                'keperluan' => 'Uji tanggal surat',
                'whatsapp' => '081234567893',
                'persetujuan_data' => true,
            ], [], '127.0.0.1');

            $teks = app(\App\Services\SuratWordService::class)->teks($jenis, $pengajuan);

            $this->assertStringContainsString('1 Desember 2026', $teks);
            $this->assertStringNotContainsString('30 November 2026', $teks);
        } finally {
            Carbon::setTestNow();
        }
    }
}
