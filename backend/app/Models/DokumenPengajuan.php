<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'pengajuan_id', 'persyaratan_surat_id', 'label', 'path', 'nama_asli',
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
     */
    public function disk(): string
    {
        return 'local';
    }

    public function urlUnduhSementara(int $menit = 10): string
    {
        return Storage::disk($this->disk())->temporaryUrl($this->path, now()->addMinutes($menit));
    }
}
