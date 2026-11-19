<?php

namespace App\Http\Controllers;

use App\Support\BerkasPublik;
use App\Support\SiteProfile;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Gambar pratinjau tautan (Open Graph) untuk WhatsApp dan media sosial.
 *
 * Logo desa yang diunggah admin bisa berukuran kecil atau berbentuk potret.
 * WhatsApp hanya menampilkan gambar pratinjau yang cukup besar, jadi logo
 * ditempel pada kanvas 1200x630 berlatar tema desa. Hasilnya selalu berupa
 * logo desa, bukan ikon abu-abu bawaan WhatsApp.
 */
class GambarSosialController extends Controller
{
    private const LEBAR = 1200;

    private const TINGGI = 630;

    public function logo(): Response
    {
        // Bila ekstensi GD belum tersedia, jangan sampai pratinjau tautan
        // gagal dan WhatsApp menampilkan ikon kosong: pakai gambar cadangan.
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagepng')) {
            return $this->gambarCadangan();
        }

        $profil = SiteProfile::current();
        $path = BerkasPublik::path($profil->logo_path);

        try {
            $png = $this->pngDariCache($path);
        } catch (\Throwable $e) {
            report($e);

            return $this->gambarCadangan();
        }

        return response($png, 200, $this->headerGambar());
    }

    /**
     * Susun gambar dari cache (versi ikut berubah saat logo diganti). Bila
     * penyimpanan cache sedang bermasalah, gambar tetap disusun langsung.
     */
    private function pngDariCache(?string $path): string
    {
        $versi = $path !== null ? (string) @filemtime($path) : 'tanpa-logo';
        $kunci = 'gambar-sosial:'.md5($versi.'|'.(string) $path);

        try {
            return Cache::remember($kunci, now()->addDay(), fn (): string => $this->render($path));
        } catch (\Throwable $e) {
            return $this->render($path);
        }
    }

    /**
     * Gambar cadangan yang ikut disimpan di repositori. Dipakai saat GD tidak
     * tersedia atau penyusunan gambar gagal, supaya tautan tetap punya gambar
     * pratinjau.
     */
    private function gambarCadangan(): Response
    {
        $berkas = public_path('images/gambar-sosial-default.png');

        if (! is_file($berkas)) {
            abort(404);
        }

        return response((string) file_get_contents($berkas), 200, $this->headerGambar());
    }

    /**
     * @return array<string, string>
     */
    private function headerGambar(): array
    {
        return [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ];
    }

    /**
     * Susun kanvas pratinjau: latar navy, cincin dekoratif, dan kartu putih
     * berisi logo desa. Bila logo belum diunggah, dipakai ikon balai desa
     * sebagai pengganti agar pratinjau tetap layak tampil.
     */
    private function render(?string $path): string
    {
        $kanvas = imagecreatetruecolor(self::LEBAR, self::TINGGI);
        imagealphablending($kanvas, true);

        $navy = imagecolorallocate($kanvas, 11, 31, 58);
        imagefilledrectangle($kanvas, 0, 0, self::LEBAR, self::TINGGI, $navy);

        $cincin = imagecolorallocate($kanvas, 23, 58, 96);
        imagesetthickness($kanvas, 2);
        foreach ([230, 300, 370, 440] as $radius) {
            imageellipse(
                $kanvas,
                intdiv(self::LEBAR, 2),
                intdiv(self::TINGGI, 2),
                $radius * 2,
                $radius * 2,
                $cincin,
            );
        }

        $ukuranKartu = 440;
        $kartuX = intdiv(self::LEBAR - $ukuranKartu, 2);
        $kartuY = intdiv(self::TINGGI - $ukuranKartu, 2);
        $putih = imagecolorallocate($kanvas, 255, 255, 255);
        $this->kotakMembulat($kanvas, $kartuX, $kartuY, $ukuranKartu, $ukuranKartu, 36, $putih);

        $logo = $this->muatLogo($path);

        if ($logo !== null) {
            $sumberLebar = imagesx($logo);
            $sumberTinggi = imagesy($logo);
            $batas = 340;
            $skala = min($batas / $sumberLebar, $batas / $sumberTinggi);
            $tujuanLebar = max(1, (int) round($sumberLebar * $skala));
            $tujuanTinggi = max(1, (int) round($sumberTinggi * $skala));

            imagecopyresampled(
                $kanvas,
                $logo,
                $kartuX + intdiv($ukuranKartu - $tujuanLebar, 2),
                $kartuY + intdiv($ukuranKartu - $tujuanTinggi, 2),
                0,
                0,
                $tujuanLebar,
                $tujuanTinggi,
                $sumberLebar,
                $sumberTinggi,
            );

            imagedestroy($logo);
        } else {
            $this->gambarIkonDesa($kanvas, $kartuX, $kartuY, $ukuranKartu, $navy);
        }

        ob_start();
        imagepng($kanvas, null, 6);
        $png = (string) ob_get_clean();
        imagedestroy($kanvas);

        return $png;
    }

    /**
     * Muat logo dari disk publik. Null bila berkas tidak ada atau formatnya
     * tidak dikenali GD.
     */
    private function muatLogo(?string $path)
    {
        if ($path === null) {
            return null;
        }

        $biner = @file_get_contents($path);

        if ($biner === false) {
            return null;
        }

        $gambar = @imagecreatefromstring($biner);

        return $gambar !== false ? $gambar : null;
    }

    /**
     * Gambar kotak dengan sudut membulat memakai empat elips di tiap sudut.
     */
    private function kotakMembulat($kanvas, int $x, int $y, int $lebar, int $tinggi, int $radius, int $warna): void
    {
        imagefilledrectangle($kanvas, $x + $radius, $y, $x + $lebar - $radius, $y + $tinggi, $warna);
        imagefilledrectangle($kanvas, $x, $y + $radius, $x + $lebar, $y + $tinggi - $radius, $warna);

        imagefilledellipse($kanvas, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $warna);
        imagefilledellipse($kanvas, $x + $lebar - $radius, $y + $radius, $radius * 2, $radius * 2, $warna);
        imagefilledellipse($kanvas, $x + $radius, $y + $tinggi - $radius, $radius * 2, $radius * 2, $warna);
        imagefilledellipse($kanvas, $x + $lebar - $radius, $y + $tinggi - $radius, $radius * 2, $radius * 2, $warna);
    }

    /**
     * Ikon balai desa sederhana untuk kondisi logo belum diunggah.
     */
    private function gambarIkonDesa($kanvas, int $kartuX, int $kartuY, int $ukuranKartu, int $warna): void
    {
        $tengah = $kartuX + intdiv($ukuranKartu, 2);
        $atapBawah = $kartuY + 250;

        imagefilledpolygon($kanvas, [
            $tengah, $kartuY + 118,
            $tengah - 124, $atapBawah,
            $tengah + 124, $atapBawah,
        ], $warna);

        imagefilledrectangle($kanvas, $tengah - 88, $atapBawah, $tengah + 88, $kartuY + 344, $warna);

        $latar = imagecolorallocate($kanvas, 232, 240, 248);
        imagefilledrectangle($kanvas, $tengah - 24, $kartuY + 272, $tengah + 24, $kartuY + 344, $latar);
    }
}
