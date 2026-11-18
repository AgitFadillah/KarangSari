<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Menyiapkan teks panjang (isi berita, pengumuman, agenda, narasi sejarah)
 * agar layak dibaca di halaman publik.
 *
 * Masalah yang diselesaikan:
 *  - Isi yang ditulis perangkat desa sering berupa satu blok tanpa baris
 *    baru (hasil tempel dari Word/WhatsApp). Tanpa dipecah, tampilannya
 *    menjadi "tembok teks" raksasa yang sulit dibaca.
 *  - Isi bisa mengandung tag HTML dari editor. Hanya sebagian kecil tag
 *    teks yang diizinkan agar tidak membuka celah skrip.
 */
class TeksBaca
{
    /** Tag teks yang dianggap aman untuk ditampilkan. */
    private const TAG_AMAN = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><blockquote><a><hr>';

    /**
     * Mengubah teks isi menjadi HTML paragraf yang terbaca.
     *
     * Teks yang sudah berisi tag blok (mis. <p>, <ul>) dibiarkan apa adanya
     * setelah disaring. Teks polos dipecah menjadi paragraf pada baris baru,
     * dan paragraf yang terlalu panjang dipecah lagi pada batas kalimat.
     */
    public static function keHtml(?string $teks): string
    {
        $teks = trim((string) $teks);

        if ($teks === '') {
            return '';
        }

        // Sudah berbentuk HTML blok dari editor: cukup disaring.
        if (preg_match('/<(p|div|ul|ol|h2|h3|h4|blockquote)\b/i', $teks)) {
            return self::saring($teks);
        }

        // Teks polos: pecah per baris, lalu per kelompok kalimat.
        $hasil = [];

        foreach (preg_split('/\R{2,}/u', $teks) ?: [] as $blok) {
            $blok = trim(str_replace(["\r\n", "\r"], "\n", $blok));

            if ($blok === '') {
                continue;
            }

            // Baris tunggal dalam satu blok digabung, lalu dipecah menjadi
            // paragraf ±600 karakter pada batas kalimat.
            $kalimat = preg_split('/(?<=[.!?])\s+/u', preg_replace('/\s+/u', ' ', $blok) ?? '');

            $paragraf = '';
            foreach ($kalimat ?: [] as $k) {
                if ($paragraf !== '' && mb_strlen($paragraf.' '.$k) > 600) {
                    $hasil[] = $paragraf;
                    $paragraf = $k;
                } else {
                    $paragraf = $paragraf === '' ? $k : $paragraf.' '.$k;
                }
            }

            if ($paragraf !== '') {
                $hasil[] = $paragraf;
            }
        }

        return implode('', array_map(
            fn (string $p) => '<p>'.self::saring($p).'</p>',
            $hasil
        ));
    }

    /**
     * Saringan ringan: buang blok skrip/gaya, lalu escape entitas editornya.
     * Teks polos di-escape lebih dulu agar karakter seperti "<" pada
     * "tinggi < 5 m" tidak menghapus isi.
     */
    protected static function saring(string $teks): string
    {
        // Tag blok berbahaya dibuang utuh beserta isinya.
        $teks = preg_replace('/<\s*(script|style|iframe|object|embed)\b[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $teks) ?? $teks;

        // Hanya tag teks yang diizinkan.
        $teks = strip_tags($teks, self::TAG_AMAN);

        // Event handler (onclick=, onerror=, ...) dibuang dari SEMUA tag,
        // bukan hanya <a>: <p onclick="..."> juga bisa menjalankan skrip
        // bila konten ditempel dari sumber luar.
        $teks = preg_replace('/(<[a-z][^>]*?)\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '$1', $teks) ?? $teks;

        // Atribut style dapat memuat url()/expression(); tampilan desa diatur
        // tema, jadi atribut ini tidak diperlukan di konten.
        $teks = preg_replace('/(<[a-z][^>]*?)\sstyle\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '$1', $teks) ?? $teks;

        // Tautan ditangani paling akhir dan secara utuh: seluruh atribut
        // <a> dibangun ulang, bukan disaring sepotong-sepotong.
        return self::bersihkanTautan($teks);
    }

    /**
     * Bangun ulang setiap <a> hanya dengan atribut yang benar-benar aman.
     *
     * Kenapa tidak cukup menghapus teks "javascript:"?
     *
     * Peramban men-dekode entitas HTML pada nilai atribut SEBELUM membaca
     * skemanya. Jadi `href="javas&#99;ript:alert(1)"` tidak mengandung teks
     * "javascript:" sama sekali, tetapi tetap menjadi tautan javascript: saat
     * diklik. Hal yang sama berlaku untuk `jav&#x61;script:`, `&#106;avascript:`,
     * tab/baris baru di tengah skema (`java&#9;script:`), dan penyandian ganda
     * (`&amp;#106;avascript:`).
     *
     * Karena itu nilainya didekode berulang sampai stabil, karakter kontrol
     * dibuang (peramban juga membuangnya), lalu skemanya diperiksa terhadap
     * daftar putih. Tautan yang tidak lolos kehilangan href-nya, sedangkan
     * teksnya tetap tampil.
     */
    protected static function bersihkanTautan(string $teks): string
    {
        return preg_replace_callback('/<a\b([^>]*)>/i', function (array $cocok): string {
            $atribut = $cocok[1];

            if (preg_match('/\bhref\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $atribut, $h) !== 1) {
                return '<a>';
            }

            $href = $h[2] ?? '';

            if ($href === '') {
                $href = $h[3] ?? '';
            }

            if ($href === '') {
                $href = $h[4] ?? '';
            }

            // Dekode berulang: entitas ganda harus habis sebelum diperiksa.
            for ($i = 0; $i < 3; $i++) {
                $baru = html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                if ($baru === $href) {
                    break;
                }

                $href = $baru;
            }

            // Peramban membuang karakter kontrol (tab, baris baru, NUL) di
            // mana pun dalam URL, lalu memangkas spasi di kedua ujung.
            $href = preg_replace('/[\x00-\x1F\x7F]/u', '', $href) ?? '';
            $href = trim($href);

            if ($href === '' || ! self::skemaAman($href)) {
                return '<a>';
            }

            return '<a href="'.e($href).'" rel="noopener noreferrer">';
        }, $teks) ?? $teks;
    }

    /** Skema tautan yang boleh dipakai konten desa. */
    private static function skemaAman(string $href): bool
    {
        // Tanpa skema = tautan relatif di situs sendiri.
        if (preg_match('/^[a-z][a-z0-9+.\-]*:/i', $href, $skema) !== 1) {
            return true;
        }

        return in_array(strtolower($skema[0]), ['http:', 'https:', 'mailto:', 'tel:'], true);
    }

    /**
     * Versi teks polos untuk ringkasan/meta description.
     */
    public static function ringkas(?string $teks, int $panjang = 160): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $teks)) ?? ''), $panjang);
    }
}
