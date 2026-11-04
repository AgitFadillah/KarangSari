<?php

namespace App\Models;

use App\Enums\StatusPengajuan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'jenis_surat_id', 'nama', 'nik', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
    'alamat', 'keperluan', 'whatsapp', 'persetujuan_data', 'persetujuan_at', 'ip_pemohon',
])]
class Pengajuan extends Model
{
    use SoftDeletes;

    protected $table = 'pengajuans';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'persetujuan_at' => 'datetime',
            'diverifikasi_at' => 'datetime',
            'selesai_at' => 'datetime',
            'persetujuan_data' => 'boolean',
            'status' => StatusPengajuan::class,
        ];
    }

    /** Status diisi lewat service, bukan mass-assign langsung. */
    public function isiStatus(StatusPengajuan $status): void
    {
        $this->status = $status;
    }

    public function jenisSurat(): BelongsTo
    {
        return $this->belongsTo(JenisSurat::class);
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(DokumenPengajuan::class);
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(RiwayatPengajuan::class)->latest();
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    /** NIK disimpan utuh, tetapi ditampilkan tersamar di daftar. */
    public function nikTersamar(): string
    {
        $nik = (string) $this->nik;

        return strlen($nik) === 16
            ? substr($nik, 0, 6).'******'.substr($nik, -4)
            : $nik;
    }

    public function scopePerluTindakan(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(
            fn (StatusPengajuan $s) => $s->value,
            StatusPengajuan::perluTindakan()
        ));
    }

    public function scopeCari(Builder $query, ?string $kata): Builder
    {
        if (blank($kata)) {
            return $query;
        }

        $kata = trim($kata);

        return $query->where(function (Builder $q) use ($kata) {
            $q->where('nomor_referensi', 'like', "%{$kata}%")
                ->orWhere('nama', 'like', "%{$kata}%")
                ->orWhere('nik', 'like', "%{$kata}%")
                ->orWhere('whatsapp', 'like', "%{$kata}%")
                ->orWhereHas('jenisSurat', fn (Builder $j) => $j->where('nama', 'like', "%{$kata}%"));
        });
    }
}
