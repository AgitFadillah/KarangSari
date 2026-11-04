<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['jenis_surat_id', 'label', 'petunjuk', 'urutan'])]
class PersyaratanSurat extends Model
{
    protected $table = 'persyaratan_surats';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['urutan' => 'integer'];
    }

    public function jenisSurat(): BelongsTo
    {
        return $this->belongsTo(JenisSurat::class);
    }
}
