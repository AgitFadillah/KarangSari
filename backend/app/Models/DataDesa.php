<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Baris data desa yang berbentuk tabel: jumlah penduduk menurut golongan
 * umur/agama/pendidikan/mata pencaharian, tingkat kesejahteraan, dan riwayat
 * kepala desa.
 *
 * Semuanya mengikuti tabel pada dokumen "PROFIL desa.docx" sehingga
 * perangkat desa cukup mengisi angka pada baris yang sudah tersedia.
 */
#[Fillable(['kelompok', 'label', 'nilai', 'nilai_l', 'nilai_p', 'keterangan', 'urutan'])]
class DataDesa extends Model
{
    protected $table = 'data_desas';

    public const GOLONGAN_UMUR = 'golongan_umur';

    public const AGAMA = 'agama';

    public const PENDIDIKAN = 'pendidikan';

    public const PENCAHARIAN = 'pencaharian';

    public const KESEJAHTERAAN = 'kesejahteraan';

    public const RIWAYAT_KADES = 'riwayat_kades';

    /** Kelompok beserta judul yang tampil di halaman profil. */
    public const KELOMPOK = [
        self::GOLONGAN_UMUR => 'Jumlah penduduk menurut golongan umur',
        self::AGAMA => 'Jumlah penduduk menurut agama',
        self::PENDIDIKAN => 'Jumlah penduduk menurut tingkat pendidikan',
        self::PENCAHARIAN => 'Jumlah penduduk menurut mata pencaharian',
        self::KESEJAHTERAAN => 'Tingkat kesejahteraan masyarakat',
        self::RIWAYAT_KADES => 'Riwayat kepala desa',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['urutan' => 'integer'];
    }

    public function scopeKelompok(Builder $query, string $kelompok): Builder
    {
        return $query->where('kelompok', $kelompok);
    }

    public function scopeTerurut(Builder $query): Builder
    {
        return $query->orderBy('urutan')->orderBy('id');
    }

    /** Baris yang benar-benar berisi, agar tabel kosong tidak ikut tampil. */
    public function adaIsi(): bool
    {
        return filled($this->nilai) || filled($this->nilai_l) || filled($this->nilai_p);
    }

    /**
     * Angka siap tampil: dipisah ribuan, dan nol tetap ditampilkan sebagai "0"
     * (dulu `$baris->nilai ?: '—'` membuat nol berubah menjadi tanda pisah).
     */
    public function nilaiTampil(): string
    {
        $angka = trim((string) $this->nilai);

        if ($angka === '') {
            return '—';
        }

        // Hanya angka murni yang diformat; nilai seperti "-" atau teks
        // dibiarkan apa adanya.
        if (! preg_match('/^\d+$/', $angka)) {
            return $angka;
        }

        return number_format((int) $angka, 0, ',', '.');
    }

    /** Jumlah L + P untuk tabel golongan umur, sudah berformat. */
    public function jumlahTampil(): string
    {
        $l = (int) preg_replace('/[^0-9]/', '', (string) $this->nilai_l);
        $p = (int) preg_replace('/[^0-9]/', '', (string) $this->nilai_p);

        if ($l + $p === 0) {
            return filled($this->nilai) ? $this->nilaiTampil() : '—';
        }

        return number_format($l + $p, 0, ',', '.');
    }

    /** Angka per kolom (L atau P) yang tetap menampilkan nol. */
    public function kolomTampil(?string $nilai): string
    {
        $angka = trim((string) $nilai);

        if ($angka === '') {
            return '—';
        }

        return preg_match('/^\d+$/', $angka)
            ? number_format((int) $angka, 0, ',', '.')
            : $angka;
    }
}
