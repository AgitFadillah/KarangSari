<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Berita, pengumuman, dan agenda desa.
 *
 * "Terbit" berarti terbit_at sudah lewat (atau kosong = langsung terbit).
 * Draf tidak pernah tampil di situs publik.
 */
#[Fillable([
    'judul', 'slug', 'kategori', 'is_highlight', 'ringkasan', 'isi', 'penulis',
    'foto_path', 'terbit_at', 'dibuat_oleh',
])]
class Berita extends Model
{
    public const KATEGORI = ['berita', 'pengumuman', 'agenda'];

    protected $table = 'beritas';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'terbit_at' => 'datetime',
            'is_highlight' => 'boolean',
        ];
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function scopeTerbit(Builder $query): Builder
    {
        return $query->whereNotNull('terbit_at')->where('terbit_at', '<=', now());
    }

    public function scopeKategori(Builder $query, string $kategori): Builder
    {
        return $query->where('kategori', $kategori);
    }

    /** Berita yang disorot, tampil paling atas pada halaman Informasi. */
    public function scopeSorotan(Builder $query): Builder
    {
        return $query->where('is_highlight', true);
    }

    public function isTerbit(): bool
    {
        return $this->terbit_at !== null && $this->terbit_at->isPast();
    }

    public function badgeStatus(): string
    {
        return $this->isTerbit() ? 'badge-green' : 'badge-amber';
    }

    public function labelStatus(): string
    {
        return $this->isTerbit() ? 'Terbit' : 'Draf';
    }

    public function labelKategori(): string
    {
        return match ($this->kategori) {
            'pengumuman' => 'Pengumuman',
            'agenda' => 'Agenda',
            default => 'Berita',
        };
    }

    /**
     * URL foto berita yang siap dipakai di halaman publik.
     *
     * Mengembalikan null bila foto belum diunggah atau berkasnya sudah tidak
     * ada di disk — template lalu jatuh ke blok ilustrasi bawaan, bukan
     * menampilkan gambar rusak.
     */
    public function fotoUrl(): ?string
    {
        if (! \App\Support\BerkasPublik::ada($this->foto_path)) {
            return null;
        }

        return \App\Support\BerkasPublik::url($this->foto_path);
    }

    /** Membuat slug unik dari judul. */
    public static function slugUnik(string $judul, ?int $abaikanId = null): string
    {
        $dasar = Str::slug($judul) ?: 'berita';
        $slug = $dasar;
        $urut = 1;

        while (static::query()
            ->where('slug', $slug)
            ->when($abaikanId, fn (Builder $q) => $q->whereKeyNot($abaikanId))
            ->exists()) {
            $slug = $dasar.'-'.(++$urut);
        }

        return $slug;
    }
}
