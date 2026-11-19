<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Penyimpanan gambar publik (logo, foto perangkat, foto berita, foto potensi).
 *
 * Kenapa tidak memakai `store()` langsung seperti di controller lain?
 *
 * `getClientOriginalExtension()` mengembalikan apa pun yang dikirim pengguna.
 * Berkas PNG sah yang dinamai "foto.html" akan tersimpan sebagai
 * "uuid.html" di disk PUBLIC — dan karena disk itu dilayani langsung oleh
 * web server, berkas tersebut menjadi halaman HTML di domain situs desa.
 * Itu celah stored XSS: penyerang dapat menjalankan JavaScript pada origin
 * yang sama dengan sesi admin.
 *
 * Karena itu nama berkas dibuat sendiri (UUID) dan ekstensinya ditentukan
 * dari TIPE ISI berkas, bukan dari namanya.
 */
class UnggahGambar
{
    /**
     * Simpan satu berkas gambar dari request.
     *
     * @param  string  $kolom  Nama field pada request (mis. "foto", "logo").
     * @param  string  $folder Folder tujuan pada disk public.
     * @return string|null Path relatif ("berita/uuid.jpg") atau null bila
     *                     tidak ada berkas yang sah.
     */
    public static function simpan(Request $request, string $kolom, string $folder): ?string
    {
        $berkas = $request->file($kolom);

        if (! $berkas instanceof UploadedFile || ! $berkas->isValid()) {
            return null;
        }

        $nama = Str::uuid()->toString().'.'.self::ekstensiAman($berkas);
        $path = $berkas->storeAs($folder, $nama, 'public');

        return $path === false ? null : $path;
    }

    /**
     * Ekstensi berdasarkan isi berkas, bukan nama kiriman.
     *
     * Daftar putih sengaja sempit: hanya format gambar yang memang dipakai
     * halaman publik. Format lain jatuh ke "jpg" — aman, karena berkasnya
     * sudah lolos aturan validasi "image".
     */
    public static function ekstensiAman(UploadedFile $berkas): string
    {
        $mime = strtolower((string) $berkas->getMimeType());

        return match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
    }

    /** Hapus berkas lama dari disk public; abaikan bila path kosong. */
    public static function hapus(?string $path): void
    {
        if (filled($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
