<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Pencarian teks yang berperilaku SAMA di SQLite, MySQL, dan PostgreSQL.
 *
 * Dua masalah yang diselesaikan:
 *
 * 1. Huruf besar/kecil.
 *    `LIKE` di PostgreSQL peka huruf besar/kecil, sedangkan di SQLite (yang
 *    dipakai saat pengembangan dan pengujian) tidak. Akibatnya pencarian
 *    "budi" menemukan "Budi Santoso" di komputer pengembang tetapi TIDAK
 *    menemukan apa pun setelah situs dipasang di PostgreSQL — petugas desa
 *    akan mengira datanya hilang. Karena itu kolom dan kata kunci sama-sama
 *    diturunkan ke huruf kecil sebelum dibandingkan.
 *
 * 2. Karakter liar.
 *    `%` dan `_` adalah wildcard pada LIKE. Petugas yang mengetik "50%" atau
 *    nomor surat bertanda garis bawah akan mendapat hasil yang membingungkan
 *    (bahkan seluruh data) karena karakternya diperlakukan sebagai pola.
 *    Karakter tersebut di-escape lebih dulu, dan ESCAPE diberitahukan ke
 *    database agar diperlakukan sebagai huruf biasa.
 *
 * Catatan: `LOWER(...) LIKE ...` tidak memakai indeks biasa. Untuk data desa
 * (ribuan baris) ini tidak terasa, dan pola "%kata%" memang tidak pernah bisa
 * memakai indeks B-tree.
 */
class Pencarian
{
    /** Karakter escape untuk LIKE. */
    private const ESCAPE = '\\';

    /**
     * Siapkan kata kunci: rapikan spasi, kecilkan huruf, escape wildcard.
     */
    public static function siapkan(?string $kata): ?string
    {
        if ($kata === null) {
            return null;
        }

        $kata = trim($kata);

        if ($kata === '') {
            return null;
        }

        // Escape karakter escape lebih dulu, baru wildcard-nya.
        $kata = str_replace(self::ESCAPE, self::ESCAPE.self::ESCAPE, $kata);
        $kata = str_replace(['%', '_'], [self::ESCAPE.'%', self::ESCAPE.'_'], $kata);

        return mb_strtolower($kata);
    }

    /**
     * Tambahkan kondisi "kolom mengandung kata" (tidak peka huruf besar/kecil).
     *
     * @param  Builder|QueryBuilder  $query
     * @param  string|array<int, string>  $kolom  satu kolom, atau beberapa kolom
     *                                            yang dihubungkan dengan OR
     */
    public static function cocok(Builder|QueryBuilder $query, string|array $kolom, string $kata, string $boolean = 'and'): void
    {
        $pola = '%'.$kata.'%';

        foreach ((array) $kolom as $i => $nama) {
            $query->whereRaw(
                'LOWER('.self::bungkus($query, $nama).') LIKE ? ESCAPE ?',
                [$pola, self::ESCAPE],
                $boolean === 'or' || $i > 0 ? 'or' : $boolean
            );
        }
    }

    /**
     * Tambahkan kondisi "kolom DIAWALI kata" (untuk NIK, nomor referensi).
     *
     * @param  Builder|QueryBuilder  $query
     */
    public static function awalan(Builder|QueryBuilder $query, string $kolom, string $kata, string $boolean = 'and'): void
    {
        $query->whereRaw(
            'LOWER('.self::bungkus($query, $kolom).') LIKE ? ESCAPE ?',
            [$kata.'%', self::ESCAPE],
            $boolean
        );
    }

    /**
     * Bungkus nama kolom sesuai dialek database (mis. `"nama"` atau `` `nama` ``).
     *
     * @param  Builder|QueryBuilder  $query
     */
    private static function bungkus(Builder|QueryBuilder $query, string $kolom): string
    {
        $grammar = $query instanceof Builder
            ? $query->getQuery()->getGrammar()
            : $query->getGrammar();

        return $grammar->wrap($kolom);
    }
}
