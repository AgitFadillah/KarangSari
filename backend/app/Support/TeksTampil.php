<?php

namespace App\Support;

use Illuminate\Contracts\Support\DeferringDisplayableValue;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Stringable;
use UnitEnum;

/**
 * Penampil nilai untuk formulir: ubah apa pun menjadi teks yang aman dicetak.
 *
 * Kenapa ini perlu?
 *
 * Seluruh formulir admin memakai pola Blade `{{ old('nama_field', $nilai) }}`
 * untuk mengisi ulang kolom saat validasi gagal. Blade mencetak `{{ ... }}`
 * lewat `e()`, dan `e()` menuntut string:
 *
 *     htmlspecialchars($value ?? '', ...)
 *
 * Bila warga/admin mengirim array ke kolom yang divalidasi `string`
 * (mis. `daftar_dusun[]=Dusun I`), validasi memang gagal seperti seharusnya,
 * tetapi halaman edit kemudian dirender ulang dengan `old()` berisi array
 * sehingga `e()` melempar TypeError dan halaman mati dengan HTTP 500 —
 * pesan validasi yang ramah tidak pernah sampai ke pengguna. Di SQLite hal
 * ini jarang terlihat karena yang diuji biasanya hanya jalur sukses.
 *
 * Perilaku yang diinginkan: kolom yang isinya array ditampilkan kosong
 * (atau gabungan baris untuk daftar sederhana), bukan mematikan halaman.
 * Dengan begitu admin melihat pesan "harus berupa teks" lalu bisa
 * memperbaiki isiannya.
 */
class TeksTampil
{
    /**
     * Pengganti e() yang tahan nilai non-string.
     *
     * Meniru perilaku e() bawaan Laravel:
     *  - Htmlable dikembalikan apa adanya (tidak di-escape),
     *  - DeferringDisplayableValue diselesaikan dulu,
     *  - BackedEnum memakai value-nya,
     *  - selain itu di-escape dengan htmlspecialchars().
     *
     * Bedanya hanya satu: nilai yang bukan skalar (array/Collection)
     * diubah lebih dulu menjadi teks, bukan dilempar ke
     * htmlspecialchars() yang akan melempar TypeError.
     */
    public static function e(mixed $nilai, bool $doubleEncode = true): string
    {
        if ($nilai instanceof DeferringDisplayableValue) {
            $nilai = $nilai->resolveDisplayableValue();
        }

        if ($nilai instanceof Htmlable) {
            return $nilai->toHtml() ?? '';
        }

        if ($nilai instanceof \BackedEnum) {
            $nilai = $nilai->value;
        }

        if ($nilai === null || is_scalar($nilai)) {
            return htmlspecialchars((string) ($nilai ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
        }

        return htmlspecialchars(self::teks($nilai), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }

    /**
     * Ubah nilai apa pun menjadi string yang siap dicetak.
     *
     * - string/int/float/bool -> apa adanya (bool menjadi "1"/"")
     * - Htmlable              -> HTML-nya (pemanggil yang menyaring)
     * - Stringable/UnitEnum   -> representasi teksnya
     * - array/Collection      -> digabung dengan koma; nilai bersarang dibuang
     * - objek lain            -> string kosong (tidak pernah mencetak "Array")
     */
    public static function teks(mixed $nilai): string
    {
        // Nilai yang memang sudah berupa teks: jalur tercepat dan paling umum.
        if (is_string($nilai)) {
            return $nilai;
        }

        if ($nilai === null) {
            return '';
        }

        if (is_bool($nilai)) {
            return $nilai ? '1' : '';
        }

        if (is_int($nilai) || is_float($nilai)) {
            return (string) $nilai;
        }

        if ($nilai instanceof DeferringDisplayableValue) {
            return self::teks($nilai->resolveDisplayableValue());
        }

        if ($nilai instanceof Htmlable) {
            return (string) $nilai->toHtml();
        }

        if ($nilai instanceof UnitEnum) {
            return self::teks($nilai instanceof \BackedEnum ? $nilai->value : $nilai->name);
        }

        if ($nilai instanceof Stringable) {
            return (string) $nilai;
        }

        // Array/Collection dari input yang salah bentuk. Jangan pernah
        // mencetak "Array": gabungkan hanya nilai skalar tingkat pertama.
        if (is_iterable($nilai)) {
            $bagian = [];

            foreach ($nilai as $isi) {
                if (is_scalar($isi) && (string) $isi !== '') {
                    $bagian[] = (string) $isi;
                }
            }

            return implode(', ', $bagian);
        }

        // Objek tak dikenal (mis. model yang salah tempat): kosongkan saja.
        return '';
    }

    /**
     * Sama seperti teks(), tetapi nilai yang bukan skalar dianggap kosong.
     *
     * Dipakai untuk kolom yang memang hanya boleh berisi satu nilai
     * (input teks, tanggal, angka). Bila isinya array, kolom dikosongkan
     * supaya pengguna melihat kolom kosong + pesan validasi, bukan isi
     * gabungan yang membingungkan.
     */
    public static function skalar(mixed $nilai): string
    {
        if (is_array($nilai) || $nilai instanceof Collection) {
            return '';
        }

        return self::teks($nilai);
    }
}
