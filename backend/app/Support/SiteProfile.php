<?php

namespace App\Support;

use App\Models\ProfilDesa;
use Throwable;

/**
 * Profil desa dipakai di hampir semua view (topbar, header, footer).
 * Di-cache per request agar tidak query berulang.
 */
class SiteProfile
{
    protected static ?ProfilDesa $cache = null;

    protected static bool $dicoba = false;

    public static function current(): ProfilDesa
    {
        if (static::$dicoba) {
            return static::$cache ?? static::bawaan();
        }

        static::$dicoba = true;

        try {
            static::$cache = ProfilDesa::query()->first() ?? static::bawaan();
        } catch (Throwable) {
            // Tabel belum dimigrasi (mis. saat artisan migrate / test boot).
            static::$cache = static::bawaan();
        }

        return static::$cache;
    }

    /**
     * Nilai cadangan bila tabel profil_desas masih kosong. Sumbernya
     * config('desa.*') supaya nama desa tidak lagi tersebar di kode.
     */
    public static function bawaan(): ProfilDesa
    {
        return new ProfilDesa([
            'nama_desa' => (string) config('desa.nama_desa', 'Desa'),
            'kecamatan' => (string) config('desa.kecamatan', ''),
            'kabupaten' => (string) config('desa.kabupaten', ''),
            'provinsi' => (string) config('desa.provinsi', ''),
        ]);
    }

    public static function lupakan(): void
    {
        static::$cache = null;
        static::$dicoba = false;
    }
}
