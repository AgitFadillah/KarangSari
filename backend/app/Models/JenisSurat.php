<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug', 'kode', 'kode_layanan', 'nama', 'deskripsi',
    'estimasi_hari', 'template_surat', 'field_formulir', 'is_aktif', 'urutan',
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
            'field_formulir' => 'array',
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

    /**
     * Field formulir tambahan yang dikonfigurasi admin secara eksplisit.
     *
     * @return array<int, array{kunci: string, label: string, tipe: string, wajib: bool}>
     */
    public function fieldFormulir(): array
    {
        $fields = is_array($this->field_formulir) ? $this->field_formulir : [];

        return collect($fields)
            ->filter(fn ($f) => is_array($f) && filled($f['kunci'] ?? null))
            ->map(fn (array $f) => [
                'kunci' => (string) $f['kunci'],
                'label' => (string) ($f['label'] ?? $f['kunci']),
                'tipe' => in_array($f['tipe'] ?? 'text', ['text', 'textarea', 'number', 'date'], true)
                    ? $f['tipe'] : 'text',
                'wajib' => (bool) ($f['wajib'] ?? false),
            ])
            ->values()
            ->all();
    }

    /** Jumlah kolom formulir yang akan dilihat warga (template + konfigurasi). */
    public function jumlahField(): int
    {
        return count(app(\App\Services\TemplateSuratService::class)->penandaKustom($this));
    }

    /** Jumlah persyaratan dokumen. */
    public function jumlahPersyaratan(): int
    {
        return $this->persyaratan_count ?? $this->persyaratan()->count();
    }
}
