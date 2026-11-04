<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

#[Fillable(['user_id', 'pelaku', 'tindakan', 'subjek', 'tingkat', 'keterangan', 'ip_address'])]
class LogAktivitas extends Model
{
    public const INFO = 'info';

    public const SUKSES = 'sukses';

    public const PERINGATAN = 'peringatan';

    protected $table = 'log_aktivitets';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badgeTingkat(): string
    {
        return match ($this->tingkat) {
            self::SUKSES => 'badge-green',
            self::PERINGATAN => 'badge-amber',
            default => 'badge-blue',
        };
    }

    public function scopeCari(Builder $query, ?string $kata): Builder
    {
        if (blank($kata)) {
            return $query;
        }

        $kata = trim($kata);

        return $query->where(function (Builder $q) use ($kata) {
            $q->where('pelaku', 'like', "%{$kata}%")
                ->orWhere('tindakan', 'like', "%{$kata}%")
                ->orWhere('subjek', 'like', "%{$kata}%")
                ->orWhere('keterangan', 'like', "%{$kata}%");
        });
    }

    /** Catat satu aktivitas admin. */
    public static function catat(
        string $tindakan,
        ?string $subjek = null,
        string $tingkat = self::INFO,
        ?string $keterangan = null,
        ?User $user = null,
        ?Request $request = null,
    ): self {
        $user ??= auth()->user();
        $request ??= request();

        return static::create([
            'user_id' => $user?->id,
            'pelaku' => $user?->name ?? 'Sistem',
            'tindakan' => $tindakan,
            'subjek' => $subjek,
            'tingkat' => $tingkat,
            'keterangan' => $keterangan,
            'ip_address' => $request?->ip(),
        ]);
    }
}
