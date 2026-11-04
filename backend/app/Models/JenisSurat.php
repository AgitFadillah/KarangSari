<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug', 'kode', 'kode_layanan', 'nama', 'deskripsi',
    'estimasi_hari', 'template_surat', 'is_aktif', 'urutan',
])]
class JenisSurat extends Model
{
    protected $table = 'jenis_surats';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function persyaratan(): HasMany
    {
        return $this->hasMany(PersyaratanSurat::class)->orderBy('urutan');
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_aktif', true);
    }

    public function scopeTerurut(Builder $query): Builder
    {
        return $query->orderBy('urutan')->orderBy('nama');
    }
}
