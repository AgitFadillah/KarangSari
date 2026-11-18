<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Perangkat desa yang tampil pada halaman profil.
 */
#[Fillable(['nama', 'jabatan', 'nip', 'foto_path', 'urutan', 'is_aktif'])]
class PerangkatDesa extends Model
{
    protected $table = 'perangkat_desas';

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
