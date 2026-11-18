<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Potensi desa: UMKM, wisata, pertanian, dan sejenisnya.
 */
#[Fillable([
    'nama', 'kategori', 'deskripsi', 'foto_path', 'kontak', 'urutan', 'is_aktif',
])]
class PotensiDesa extends Model
{
    protected $table = 'potensi_desas';

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

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_aktif', true);
    }

    public function scopeTerurut(Builder $query): Builder
    {
        return $query->orderBy('urutan')->orderBy('nama');
    }
}
