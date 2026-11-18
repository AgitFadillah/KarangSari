<?php

namespace App\Providers;

use App\Models\JenisSurat;
use App\Support\SiteProfile;
use App\Support\TeksTampil;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Percayai header X-Forwarded-* hanya bila TRUSTED_PROXIES diisi.
        // Diatur di sini (bukan bootstrap/app.php) karena config belum siap
        // saat middleware didaftarkan. Tanpa ini, deployment di belakang
        // proxy/Cloudflare membaca IP proxy, bukan IP warga, sehingga rate
        // limit per-IP dan deteksi HTTPS/cookie Secure menjadi salah.
        $proxies = config('app.trusted_proxies');

        if (filled($proxies)) {
            \Illuminate\Http\Middleware\TrustProxies::at(
                trim((string) $proxies) === '*'
                    ? '*'
                    : array_values(array_filter(array_map('trim', explode(',', (string) $proxies))))
            );
        }

        // Setiap route ber-throttle memakai limiter bernama dengan kunci sendiri,
        // supaya pratinjau surat yang dipanggil saat warga mengetik tidak
        // menghabiskan kuota halaman revisi maupun kuota kirim revisi.
        \App\Support\BatasPermintaan::daftarkan();

        // `{{ $nilai }}` tidak boleh mematikan halaman.
        //
        // Blade mencetak `{{ ... }}` lewat e(), dan e() memanggil
        // htmlspecialchars() yang HANYA menerima string. Bila sebuah kolom
        // formulir divalidasi `string` tetapi dikirim sebagai array
        // (mis. `daftar_dusun[]=Dusun I`), validasi gagal seperti seharusnya,
        // namun halaman edit dirender ulang dengan old() berisi array
        // sehingga halaman mati dengan TypeError/HTTP 500 — pesan validasi
        // yang ramah tidak pernah terlihat. Dengan mengganti format echo
        // menjadi TeksTampil::teks(...), nilai array dicetak sebagai teks
        // biasa (atau kosong) sehingga halaman tetap tampil.
        //
        // Pemanggil tetap mendapat escaping: TeksTampil::e() meniru e() bawaan
        // (Htmlable tetap mentah, BackedEnum memakai value-nya), hanya saja
        // nilai array diubah menjadi teks alih-alih melempar TypeError.
        Blade::setEchoFormat('\App\Support\TeksTampil::e(%s)');

        // Seluruh surat desa memakai nama bulan Indonesia ("14 Maret 1990").
        // Carbon menyimpan locale sendiri; jangan bergantung pada APP_LOCALE
        // yang masih "en" agar tanggal surat tidak tercetak "March".
        \Illuminate\Support\Carbon::setLocale('id');
        \Carbon\CarbonImmutable::setLocale('id');

        // Profil desa dipakai hampir semua view (judul halaman, brand, footer).
        // Diberikan lewat composer agar @section('title', ...) di baris atas view
        // tidak mendahului @php di layout, dan tidak query saat boot.
        View::composer('*', function ($view) {
            if (! array_key_exists('profil', $view->getData()) || $view->getData()['profil'] === null) {
                $view->with('profil', SiteProfile::current());
            }
        });

        // Partial topbar memakai $activeNav; default null agar view yang tidak
        // menyetelnya tetap aman dirender.
        View::composer('layouts.partials.topbar', function ($view) {
            $view->with('activeNav', $view->getData()['activeNav'] ?? null);
        });

        // Footer menampilkan tautan layanan; dibaca dari konfigurasi jenis surat
        // agar tidak lagi ditulis manual (dan tidak putus saat slug berubah).
        View::composer('layouts.partials.footer', function ($view) {
            if (array_key_exists('layananFooter', $view->getData())) {
                return;
            }

            try {
                $layanan = JenisSurat::query()->aktif()->terurut()->take(3)->get();
            } catch (Throwable) {
                // Tabel belum dimigrasi: footer tetap tampil tanpa tautan layanan.
                $layanan = collect();
            }

            $view->with('layananFooter', $layanan);
        });

        // Route model binding publik /pelayanan/{jenisSurat} memakai slug dan
        // hanya membuka jenis surat yang aktif. Panel admin tetap boleh
        // membuka kategori nonaktif agar bisa diaktifkan kembali.
        Route::bind('jenisSurat', function (string $value) {
            $kueri = JenisSurat::query()->where('slug', $value);

            if (! str_starts_with((string) request()->route()?->getName(), 'admin.')) {
                $kueri->aktif();
            }

            return $kueri->firstOrFail();
        });
    }
}
