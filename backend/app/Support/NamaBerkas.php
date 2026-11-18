<?php

namespace App\Support;

/**
 * Pembersih nama berkas dan tipe MIME sebelum dipakai pada header HTTP.
 *
 * Dua nilai ini berasal dari WARGA (nama berkas asli yang mereka unggah dan
 * tipe MIME yang dilaporkan browser mereka), lalu dipakai petugas desa saat
 * mengunduh dokumen. Tanpa pembersihan:
 *
 *  1. Nama berkas berisi garis miring membuat Symfony melempar
 *     InvalidArgumentException — petugas hanya melihat HTTP 500 dan tidak
 *     bisa membuka dokumen warga sama sekali.
 *  2. Nama berkas berisi CR/LF menyuntik header HTTP baru (header injection),
 *     mis. menambah "Set-Cookie" pada respons milik domain desa.
 *
 * Karena itu keduanya dibersihkan di sini, bukan di lapisan tampilan: nilai
 * yang dipakai header harus aman apa pun asal-usulnya.
 */
class NamaBerkas
{
    /** Batas panjang nama berkas pada header Content-Disposition. */
    private const MAKS_PANJANG = 120;

    /**
     * Nama berkas yang aman dipakai pada Content-Disposition.
     *
     * Hanya huruf, angka, titik, garis bawah, dan tanda hubung yang
     * dipertahankan; sisanya (termasuk "/", "\", CR, LF, dan tanda kutip)
     * diganti tanda hubung. Ekstensi asli tetap dipertahankan agar petugas
     * tahu jenis berkasnya.
     */
    public static function aman(?string $nama, string $bawaan = 'dokumen'): string
    {
        $nama = (string) $nama;

        // CR/LF adalah inti header injection. Alih-alih hanya membuangnya
        // (yang menyambung dua bagian nama menjadi satu kata tanpa arti),
        // potong pada karakter kontrol pertama: nama yang sah selalu berada
        // sebelum karakter itu. "ktp.pdf\r\nX-Injected: yes" -> "ktp.pdf".
        $nama = preg_split('/[\x00-\x1F\x7F]/u', $nama)[0] ?? '';

        // Buang komponen direktori: "../../etc/passwd" -> "passwd".
        $nama = basename(str_replace('\\', '/', $nama));

        // Pisahkan ekstensi supaya titik pada ekstensi tidak ikut hilang.
        $info = pathinfo($nama);
        $dasar = (string) ($info['filename'] ?? $nama);
        $ext = strtolower((string) ($info['extension'] ?? ''));

        $dasar = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $dasar), '-_.');

        if ($dasar === '') {
            $dasar = $bawaan;
        }

        $dasar = mb_substr($dasar, 0, self::MAKS_PANJANG);

        // Ekstensi hanya diterima bila benar-benar alfanumerik dan pendek.
        if ($ext !== '' && preg_match('/^[a-z0-9]{1,10}$/', $ext) === 1) {
            return $dasar.'.'.$ext;
        }

        return $dasar;
    }

    /**
     * Tipe MIME yang aman untuk header Content-Type.
     *
     * Nilai dari database bisa berisi CR/LF (header injection) atau tipe
     * berbahaya seperti "text/html" yang membuat berkas unggahan warga
     * dieksekusi sebagai halaman di origin situs desa. Hanya tipe yang
     * bentuknya sah dan bukan HTML/JavaScript yang diloloskan; selain itu
     * jatuh ke "application/octet-stream" (dipaksa unduh, tidak dirender).
     */
    public static function mimeAman(?string $mime): string
    {
        $mime = strtolower(trim((string) $mime));

        // Bentuk "type/subtype" dengan parameter opsional (charset=...).
        if (preg_match('#^[a-z0-9][a-z0-9.+-]*/[a-z0-9][a-z0-9.+-]*(\s*;.*)?$#', $mime) !== 1) {
            return 'application/octet-stream';
        }

        // Tipe yang bisa dijalankan sebagai halaman di browser.
        $berbahaya = [
            'text/html', 'application/xhtml+xml', 'image/svg+xml',
            'text/xml', 'application/xml', 'application/javascript',
            'text/javascript', 'application/x-javascript', 'text/ecmascript',
        ];

        $dasar = trim(explode(';', $mime)[0]);

        if (in_array($dasar, $berbahaya, true)) {
            return 'application/octet-stream';
        }

        return $dasar;
    }
}
