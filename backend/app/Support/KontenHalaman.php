<?php

namespace App\Support;

use App\Models\KontenWebsite;
use Illuminate\Support\Collection;

/**
 * Menghubungkan isi menu "Konten Website" di dashboard admin dengan judul
 * dan pengantar halaman publik.
 *
 * Sebelumnya lima bagian konten (beranda, profil, pelayanan, informasi,
 * kontak) dapat diubah perangkat desa, tetapi tidak pernah dibaca oleh satu
 * halaman publik pun: seluruh judul dan paragraf pembuka ditulis mati di
 * dalam Blade. Akibatnya admin menyimpan perubahan lalu tidak melihat
 * efek apa pun di situs.
 *
 * Kelas ini menjadi satu-satunya tempat pemetaan bagian -> halaman, supaya
 * penambahan halaman baru cukup diubah di sini.
 */
class KontenHalaman
{
    /** Bagian konten yang mengisi judul + pengantar tiap halaman. */
    public const BAGIAN = [
        'beranda' => 'Beranda',
        'profil' => 'Profil Desa',
        'pelayanan' => 'Pelayanan',
        'informasi' => 'Informasi',
        'kontak' => 'Kontak',
    ];

    /**
     * Ambil konten terbit untuk sebuah bagian dari kumpulan konten yang sudah
     * dimuat controller (menghindari kueri tambahan di dalam view).
     *
     * @param  Collection<int, KontenWebsite>|null  $kontens
     */
    public static function ambil(?Collection $kontens, string $bagian): ?KontenWebsite
    {
        if ($kontens === null) {
            return null;
        }

        return $kontens->firstWhere('bagian', $bagian);
    }

    /**
     * Judul halaman dari konten admin, atau null bila admin mengosongkannya
     * (halaman lalu memakai judul bawaan desain).
     */
    public static function judul(?KontenWebsite $konten): ?string
    {
        $judul = trim((string) ($konten?->judul ?? ''));

        return $judul !== '' ? $judul : null;
    }

    /**
     * Paragraf pengantar dari konten admin sebagai teks polos.
     *
     * Isi konten boleh mengandung baris baru (hasil tempel dari Word/WhatsApp),
     * jadi baris kosong dipertahankan sebagai pemisah paragraf. Tag HTML
     * dibuang karena bagian ini dirender sebagai teks, bukan HTML.
     */
    public static function pengantar(?KontenWebsite $konten, int $maks = 600): ?string
    {
        $isi = trim((string) ($konten?->isi ?? ''));

        if ($isi === '') {
            return null;
        }

        $isi = strip_tags($isi);
        $isi = html_entity_decode($isi, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $isi = preg_replace('/[ \t]+/u', ' ', $isi) ?? $isi;
        $isi = preg_replace('/\n{3,}/u', "\n\n", $isi) ?? $isi;
        $isi = trim($isi);

        if ($isi === '') {
            return null;
        }

        return mb_strlen($isi) > $maks ? rtrim(mb_substr($isi, 0, $maks)).'…' : $isi;
    }
}
