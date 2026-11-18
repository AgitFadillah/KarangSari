<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

/**
 * Pembatas laju (rate limit) per route untuk halaman warga dan login admin.
 *
 * Kenapa tidak memakai "throttle:5,10" langsung di route?
 *
 * Middleware throttle bawaan Laravel membuat kunci dari IP + domain saja,
 * TANPA nama route. Akibatnya semua route ber-throttle di aplikasi ini berbagi
 * SATU ember hitungan yang sama. Contoh nyata yang pernah terjadi:
 *
 *  - Warga membuka halaman revisi (throttle:20,1).
 *  - Saat mengetik, pratinjau surat memanggil server berkali-kali
 *    (throttle:60,1) — hitungannya masuk ke ember yang sama.
 *  - Kuota halaman revisi habis, lalu warga melihat "429 Too Many Requests"
 *    padahal ia hanya mengisi formulir.
 *  - Kirim revisi (throttle:5,10) juga ikut terblokir, sehingga perbaikan
 *    warga tidak pernah tersimpan.
 *
 * Karena itu setiap route memakai limiter bernama dengan kunci sendiri
 * (nama route + identitas pemanggil), sehingga kuota satu halaman tidak
 * memakan kuota halaman lain.
 */
class BatasPermintaan
{
    /**
     * Nama limiter => [batas percobaan, jendela waktu dalam detik].
     *
     * @var array<string, array{0:int, 1:int}>
     */
    public const BATAS = [
        // Kirim pengajuan baru: cegah spam pengajuan palsu.
        'pengajuan.store' => [5, 600],

        // Pratinjau surat dipanggil otomatis saat warga mengetik, jadi
        // batasnya longgar; ini hanya komputasi teks tanpa menulis data.
        'pengajuan.pratinjau' => [120, 60],

        // Halaman revisi hanya menampilkan formulir (baca saja), tapi tetap
        // dibatasi karena nomor referensi berfungsi sebagai token akses.
        'pengajuan.revisi' => [30, 60],

        // Kirim perbaikan: tulis data, jadi ketat seperti pengajuan baru.
        'pengajuan.revisi.simpan' => [10, 600],

        // Unduh surat jadi: nomor referensi juga menjadi token.
        'pengajuan.surat' => [30, 60],

        // Cek status: cegah orang menebak-nebak nomor referensi warga lain.
        'cek-status.cari' => [10, 60],

        // Pemulihan kode akses dengan tanggal lahir: batasnya lebih ketat
        // daripada cek status, karena di sinilah kode rahasia ditampilkan.
        'cek-status.lupa' => [8, 600],

        // Login admin: cegah tebak password.
        'admin.login.store' => [6, 60],
    ];

    /** Daftarkan seluruh limiter ke RateLimiter Laravel. */
    public static function daftarkan(): void
    {
        foreach (self::BATAS as $nama => [$maks, $detik]) {
            // perSecond(maxAttempts, decaySeconds): $maks kali per $detik detik.
            RateLimiter::for($nama, fn (Request $request) => Limit::perSecond($maks, $detik)
                ->by(self::kunci($nama, $request)));
        }
    }

    /**
     * Kunci limiter: nama route + identitas pemanggil.
     *
     * Nama route disertakan supaya tiap halaman punya ember terpisah, dan
     * identitas (akun admin bila login, selain itu IP) supaya satu pengunjung
     * tidak menghabiskan kuota pengunjung lain.
     */
    protected static function kunci(string $nama, Request $request): string
    {
        $identitas = $request->user()?->getAuthIdentifier() ?? $request->ip();

        return $nama.'|'.$identitas;
    }

    /**
     * Sisa waktu tunggu (detik) untuk sebuah limiter, dipakai halaman 429 agar
     * warga tahu berapa lama harus menunggu.
     */
    public static function tunggu(string $nama, Request $request): ?int
    {
        [$maks, $detik] = self::BATAS[$nama] ?? [0, 0];

        if ($maks === 0) {
            return null;
        }

        $kunci = md5($nama.self::kunci($nama, $request));

        if (! RateLimiter::tooManyAttempts($kunci, $maks)) {
            return null;
        }

        return max(1, RateLimiter::availableIn($kunci));
    }

    /**
     * Kosongkan hitungan sebuah limiter untuk satu pemanggil.
     *
     * Dipakai saat pengujian dan saat petugas desa perlu membuka blokir warga
     * yang terlanjur kena batas (mis. setelah mencoba berkali-kali karena
     * sinyal buruk), tanpa menunggu jendela waktu habis.
     */
    public static function bersihkan(string $nama, Request $request): void
    {
        // Middleware throttle menyimpan hitungan dengan kunci
        // md5(namaLimiter . kunci) — lihat ThrottleRequests::handleRequestUsingNamedLimiter.
        RateLimiter::clear(md5($nama.self::kunci($nama, $request)));
    }

    /**
     * Kosongkan seluruh limiter untuk satu alamat IP. Berguna saat audit
     * otomatis agar tiap putaran mulai dari kondisi bersih.
     */
    public static function bersihkanUntukIp(string $ip): void
    {
        foreach (array_keys(self::BATAS) as $nama) {
            RateLimiter::clear(md5($nama.$nama.'|'.$ip));
        }
    }

    /**
     * Kosongkan seluruh limiter untuk semua alamat IP yang tercatat.
     *
     * Dipakai di awal setiap tes: hitungan limiter disimpan di cache dan tidak
     * ikut terhapus oleh RefreshDatabase, sehingga tes yang mengirim beberapa
     * pengajuan dapat gagal karena 429 dari tes sebelumnya.
     */
    public static function bersihkanSemua(): void
    {
        // Alamat yang mungkin muncul: IP bawaan pengujian dan IP lokal.
        foreach (['127.0.0.1', '::1', 'localhost'] as $ip) {
            self::bersihkanUntukIp($ip);
        }
    }
}
