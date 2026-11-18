<?php

namespace Database\Seeders;

use App\Models\DataDesa;
use Illuminate\Database\Seeder;

/**
 * Baris tabel data desa sesuai dokumen "PROFIL desa.docx".
 *
 * Yang diisi di sini hanya LABEL barisnya (golongan umur, agama, pendidikan,
 * mata pencaharian, kesejahteraan, riwayat kepala desa). Angkanya sengaja
 * dikosongkan karena harus berasal dari data resmi desa yang diisi perangkat
 * desa lewat dashboard admin. Dengan cara ini tabel di panel admin langsung
 * mengikuti struktur dokumen dan tidak ada angka karangan.
 */
class DataDesaSeeder extends Seeder
{
    /** @var array<string, array<int, array{0: string, 1?: string}>> */
    private const BARIS = [
        DataDesa::GOLONGAN_UMUR => [
            ['0 Bln - 12 Bln'],
            ['13 Bln - 4 Thn'],
            ['5 Thn - 6 Thn'],
            ['7 Thn - 12 Thn'],
            ['13 Thn - 15 Thn'],
            ['16 Thn - 18 Thn'],
            ['19 Thn - 25 Thn'],
            ['26 Thn - 35 Thn'],
            ['36 Thn - 45 Thn'],
            ['46 Thn - 50 Thn'],
            ['51 Thn - 60 Thn'],
            ['61 Tahun ke atas'],
        ],
        DataDesa::AGAMA => [
            ['Islam'],
            ['Kristen'],
            ['Katolik'],
            ['Hindu'],
            ['Budha'],
        ],
        DataDesa::PENDIDIKAN => [
            ['Belum/Tidak/Sudah Tidak Sekolah'],
            ['SD'],
            ['SLTP'],
            ['SLTA / SMK'],
            ['Perguruan Tinggi'],
        ],
        DataDesa::PENCAHARIAN => [
            ['Petani'],
            ['Pegawai'],
            ['Peternak'],
            ['Pengrajin'],
            ['Buruh Tani'],
            ['Pensiunan'],
            ['Pedagang/wiraswasta'],
            ['Lain-lain'],
        ],
        DataDesa::KESEJAHTERAAN => [
            ['Jumlah penduduk sangat miskin', 'KK'],
            ['Jumlah penduduk miskin', 'KK'],
            ['Jumlah penduduk sedang', 'KK'],
            ['Jumlah penduduk kaya', 'KK'],
        ],
        DataDesa::RIWAYAT_KADES => [
            ['Cuhli', 'Kepala Desa Pertama'],
            ['Sugeng', 'Pj Kepala Desa'],
            ['Romsi', 'Kepala Desa'],
            ['Rusdiwanti', 'Pj Kepala Desa'],
            ['Romsi', 'Kepala Desa'],
        ],
    ];

    public function run(): void
    {
        foreach (self::BARIS as $kelompok => $daftar) {
            foreach ($daftar as $urutan => $baris) {
                // Kunci memakai urutan juga: sebagian label memang berulang
                // pada dokumen (mis. "Romsi" menjabat dua periode), sehingga
                // mencocokkan label saja akan menimpa baris sebelumnya.
                DataDesa::query()->updateOrCreate(
                    ['kelompok' => $kelompok, 'urutan' => $urutan + 1],
                    [
                        'label' => $baris[0],
                        'keterangan' => $baris[1] ?? null,
                    ]
                );
            }
        }
    }
}
