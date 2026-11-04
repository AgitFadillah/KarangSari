<?php

namespace App\Models;

use App\Enums\StatusPengajuan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pengajuan_id', 'status', 'catatan', 'user_id', 'aktor'])]
class RiwayatPengajuan extends Model
{
    protected $table = 'riwayat_pengajuans';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => StatusPengajuan::class];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badgeAktor(): string
    {
        return match ($this->aktor) {
            'admin' => 'badge-blue',
            default => 'badge-gray',
        };
    }
}
