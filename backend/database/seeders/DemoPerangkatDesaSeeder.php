<?php

namespace Database\Seeders;

use App\Models\PerangkatDesa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Struktur pemerintahan contoh untuk lingkungan lokal/testing.
 *
 * Seperti DemoPengajuanSeeder, seeder ini HANYA dijalankan di local/testing
 * (lihat DatabaseSeeder) agar situs produksi tidak menampilkan nama perangkat
 * karangan. Nama sengaja berupa penanda, bukan nama orang sungguhan.
 *
 * Penanda diberi awalan "Contoh" (bukan "Nama") supaya tidak pernah ikut
 * tercetak sebagai nama pejabat pada surat resmi bila data uji dipakai.
 */
class DemoPerangkatDesaSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    protected array $data = [
        'Kepala Desa',
        'Sekretaris Desa',
        'Kaur Keuangan',
        'Kaur Umum',
        'Kasi Pelayanan',
        'Kepala Dusun I',
    ];

    public function run(): void
    {
        DB::transaction(function () {
            foreach ($this->data as $urutan => $jabatan) {
                PerangkatDesa::query()->updateOrCreate(
                    ['jabatan' => $jabatan],
                    [
                        'nama' => 'Contoh '.$jabatan,
                        'urutan' => $urutan + 1,
                        'is_aktif' => true,
                    ]
                );
            }
        });
    }
}
