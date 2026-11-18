<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * URL untuk berkas yang tersimpan di disk "public" (logo desa, foto perangkat).
 *
 * Kenapa tidak memakai Storage::disk('public')->url() langsung?
 *
 * Method itu membangun URL ABSOLUT dari APP_URL, mis. "http://localhost:8000/storage/…".
 * Bila situs diakses lewat host lain (127.0.0.1, IP LAN, atau domain produksi yang
 * berbeda dengan APP_URL), URL tersebut menunjuk ke origin yang berbeda sehingga:
 *
 *  - diblokir Content-Security-Policy img-src 'self' → gambar tampil rusak, dan
 *  - tetap gagal walau CSP dilonggarkan, karena host tujuannya tidak melayani situs.
 *
 * Karena itu dipakai URL relatif ("/storage/…") yang selalu mengikuti origin
 * halaman. Ini aman untuk deployment di subfolder maupun domain apa pun.
 */
class BerkasPublik
{
    /** Prefix URL untuk disk public. */
    protected const PREFIX = '/storage/';

    /**
     * URL siap pakai untuk sebuah path di disk public.
     * Mengembalikan null bila path kosong.
     */
    public static function url(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        // Sudah berupa URL absolut (mis. data lama yang menyimpan URL penuh).
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        // Normalkan pemisah Windows dan buang garis miring di depan.
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        // Hindari "/storage/storage/..." bila path sudah berawalan storage/.
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return self::PREFIX.$path;
    }

    /**
     * Apakah berkasnya benar-benar ada di disk public?
     * Dipakai agar template bisa jatuh ke placeholder alih-alih <img> rusak.
     */
    public static function ada(?string $path): bool
    {
        $path = trim((string) $path);

        return $path !== '' && Storage::disk('public')->exists($path);
    }

    /**
     * Path fisik berkas di disk public (untuk disisipkan ke berkas Word/PDF).
     * Mengembalikan null bila path kosong atau berkasnya tidak ada.
     */
    public static function path(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->path($path);
    }
}
