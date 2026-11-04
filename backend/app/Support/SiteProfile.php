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
     * Nilai cadangan bila tabel profil_desas masih kosong.
     */
    public static function bawaan(): ProfilDesa
    {
        return new ProfilDesa([
            'nama_desa' => 'Desa Sukamaju',
            'kecamatan' => 'Cisarua',
            'kabupaten' => 'Kabupaten Bogor',
            'provinsi' => 'Jawa Barat',
            'telepon' => '(0251) 830 1122',
            'email' => 'kantorpemdesa@sukamaju.desa.id',
            'jam_layanan' => 'Senin–Jumat, 08.00–15.00 WIB',
        ]);
    }

    public static function lupakan(): void
    {
        static::$cache = null;
        static::$dicoba = false;
    }
}
