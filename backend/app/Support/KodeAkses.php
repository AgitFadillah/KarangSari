<?php

namespace App\Support;

/**
 * Kode akses pengajuan untuk warga.
 *
 * Nomor referensi (mis. KRS-2026-0001) sengaja mudah dibaca dan berurutan,
 * sehingga TIDAK cocok dipakai sebagai kunci akses: siapa pun yang menebak
 * nomornya dapat membuka surat, revisi, atau status milik warga lain.
 *
 * Kode akses ini adalah kunci sebenarnya. Alphabetnya membuang karakter yang
 * mudah tertukar saat dibaca/diketik (0/O, 1/I), supaya warga tidak salah
 * memasukkan kode dari pesan WhatsApp.
 */
class KodeAkses
{
    /** 32 karakter: digit 2-9 + huruf besar tanpa I dan O. */
    private const ALFABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    /** Panjang kode bawaan, mis. "K7QM3FZP" (32^8 ≈ 1,1 triliun kemungkinan). */
    public const PANJANG = 8;

    /** Kode acak baru. Memakai random_int (CSPRNG), bukan rand/mt_rand. */
    public static function acak(int $panjang = self::PANJANG): string
    {
        $maks = strlen(self::ALFABET) - 1;
        $kode = '';

        for ($i = 0; $i < $panjang; $i++) {
            $kode .= self::ALFABET[random_int(0, $maks)];
        }

        return $kode;
    }

    /**
     * Seragamkan masukan warga: buang spasi/strip dan jadikan huruf besar,
     * sehingga "k7qm-3fzp " tetap cocok dengan kode tersimpan "K7QM3FZP".
     */
    public static function normalisasi(?string $kode): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $kode) ?? '');
    }
}
