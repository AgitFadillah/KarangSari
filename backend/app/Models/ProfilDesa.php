<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nama_desa', 'kecamatan', 'kabupaten', 'provinsi', 'kode_pos',
    'alamat_kantor', 'telepon', 'whatsapp', 'email',
    'jam_layanan', 'jam_kunjungan', 'kueri_peta', 'sejarah', 'visi', 'misi',
    'logo_path', 'foto_beranda_path',
])]
class ProfilDesa extends Model
{
    protected $table = 'profil_desas';

    /** Teks untuk atribut "Pemerintah {kabupaten} · Kecamatan {kecamatan}". */
    public function getWilayahAttribute(): string
    {
        return trim(sprintf('Pemerintah %s · Kecamatan %s', $this->kabupaten, $this->kecamatan));
    }

    public function getAlamatLengkapAttribute(): string
    {
        return (string) $this->alamat_kantor;
    }
}
