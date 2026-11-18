<?php

namespace Database\Seeders;

use App\Models\ProfilDesa;
use Illuminate\Database\Seeder;

/**
 * Data identitas desa. Nilai awal diambil dari config/desa.php supaya nama
 * desa tidak ditulis ulang di kode; setelah itu diubah lewat dashboard admin.
 */
class ProfilDesaSeeder extends Seeder
{
    public function run(): void
    {
        ProfilDesa::query()->updateOrCreate(
            ['id' => 1],
            [
                'nama_desa' => (string) config('desa.nama_desa'),
                'kecamatan' => (string) config('desa.kecamatan'),
                'kabupaten' => (string) config('desa.kabupaten'),
                'provinsi' => (string) config('desa.provinsi'),
                // Alamat kantor dipakai pada kop surat resmi, jadi diisi dari
                // kop dokumen desa (config/desa.php) agar surat baru langsung
                // lengkap. Perangkat desa tetap bisa mengubahnya lewat menu
                // Profil & Kontak.
                'kode_pos' => config('desa.kode_pos'),
                'alamat_kantor' => config('desa.alamat_kantor'),
                // Kolom di bawah ini sengaja dikosongkan: perangkat desa
                // mengisinya sendiri lewat menu Profil & Kontak.
                'telepon' => null,
                'whatsapp' => null,
                'email' => null,
                'jam_layanan' => null,
                'jam_kunjungan' => null,
                'kueri_peta' => null,
                'sejarah' => null,
                'visi' => null,
                'misi' => null,
            ]
        );
    }
}
