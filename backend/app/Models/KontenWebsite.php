<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['bagian', 'judul', 'isi', 'status', 'diperbarui_oleh'])]
class KontenWebsite extends Model
{
    public const DRAF = 'draf';

    public const TERBIT = 'terbit';

    protected $table = 'konten_websites';

    public function diperbaruiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diperbarui_oleh');
    }

    public function isTerbit(): bool
    {
        return $this->status === self::TERBIT;
    }

    public function badgeStatus(): string
    {
        return $this->isTerbit() ? 'badge-green' : 'badge-amber';
    }

    public function labelStatus(): string
    {
        return $this->isTerbit() ? 'Terbit' : 'Draf';
    }

    public function scopeTerbit(Builder $query): Builder
    {
        return $query->where('status', self::TERBIT);
    }

    /** Ambil satu konten terbit berdasarkan bagian (untuk halaman publik). */
    public static function ambil(string $bagian): ?self
    {
        return static::query()->terbit()->where('bagian', $bagian)->latest('updated_at')->first();
    }
}
