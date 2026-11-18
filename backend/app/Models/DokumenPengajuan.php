<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pengajuan_id', 'persyaratan_surat_id', 'label', 'path', 'disk', 'nama_asli',
    'mime', 'ukuran', 'status_verifikasi',
])]
class DokumenPengajuan extends Model
{
    public const MENUNGGU = 'menunggu';

    public const TERVERIFIKASI = 'terverifikasi';

    public const DITOLAK = 'ditolak';

    protected $table = 'dokumen_pengajuans';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['ukuran' => 'integer'];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function persyaratan(): BelongsTo
    {
        return $this->belongsTo(PersyaratanSurat::class, 'persyaratan_surat_id');
    }

    public function ukuranTerbaca(): string
    {
        $kb = $this->ukuran / 1024;

        return $kb > 1024 ? number_format($kb / 1024, 1).' MB' : round($kb).' KB';
    }

    public function badgeStatus(): string
    {
        return match ($this->status_verifikasi) {
            self::TERVERIFIKASI => 'badge-green',
            self::DITOLAK => 'badge-red',
            default => 'badge-amber',
        };
    }

    /**
     * Dokumen PII -> disk privat, tidak pernah publik.
     *
     * Jangan mengganti nilai ini ke disk "public". Disk privat sengaja tidak
     * punya URL publik; satu-satunya jalan membuka berkas adalah route admin
     * admin.pengajuan.dokumen yang mewajibkan login.
     *
     * Nilai disimpan per dokumen agar berkas lama (disk lokal) dan berkas baru
     * (Supabase Storage/S3) tetap bisa dibuka setelah penyimpanan dipindah.
     */
    public function disk(): string
    {
        $disk = (string) ($this->getAttribute('disk') ?: '');

        if ($disk !== '') {
            return $disk;
        }

        return (string) config('desa.disk_dokumen', 'local');
    }
}
