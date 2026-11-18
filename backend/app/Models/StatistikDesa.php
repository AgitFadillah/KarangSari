<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Angka ringkas desa yang tampil di beranda ("Sekilas Desa").
 */
#[Fillable(['label', 'nilai', 'satuan', 'urutan', 'is_aktif'])]
class StatistikDesa extends Model
{
    protected $table = 'statistik_desas';

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
        return $query->orderBy('urutan')->orderBy('id');
    }
}
