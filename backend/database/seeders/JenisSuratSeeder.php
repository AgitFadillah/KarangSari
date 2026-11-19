<?php

namespace Database\Seeders;

use App\Models\JenisSurat;
use App\Support\TemplateSuratDesa;
use Illuminate\Database\Seeder;

/**
 * Tujuh jenis surat resmi desa.
 *
 * Sumber kebenaran seluruh isinya adalah dokumen Word perangkat desa
 * (lihat App\Support\TemplateSuratDesa). Seeder ini hanya
 * menuangkannya ke tabel jenis_surats:
 *
 *  - template_surat  : teks resmi dengan penanda {{...}}
 *  - field_formulir  : kolom isian warga (agama, usaha, data anak asuh, ...)
 *  - persyaratan     : dokumen unggahan yang diminta desa
 *
 * Jenis surat lama yang tidak punya dokumen resmi (ktp, keramaian) tidak
 * dihapus karena sudah dipakai pengajuan warga; statusnya dinonaktifkan agar
 * tidak muncul lagi pada katalog layanan.
 */
class JenisSuratSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TemplateSuratDesa::katalog() as $slug => $data) {
            $jenisSurat = JenisSurat::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'kode' => $data['kode'],
                    'kode_layanan' => $data['kode_layanan'],
                    'nama' => $data['nama'],
                    'deskripsi' => $data['deskripsi'],
                    'estimasi_hari' => $data['estimasi_hari'],
                    'urutan' => $data['urutan'],
                    'template_surat' => $data['template'],
                    'field_formulir' => $data['isian'],
                    'is_aktif' => true,
                ]
            );

            $jenisSurat->persyaratan()->delete();

            foreach ($data['persyaratan'] as $urutan => $syarat) {
                $jenisSurat->persyaratan()->create([
                    'label' => $syarat['label'],
                    'petunjuk' => $syarat['petunjuk'],
                    'urutan' => $urutan + 1,
                ]);
            }
        }

        // Kategori lama tanpa dokumen resmi: simpan riwayat pengajuannya,
        // tetapi sembunyikan dari katalog layanan warga.
        JenisSurat::query()
            ->whereIn('slug', ['ktp', 'keramaian'])
            ->update(['is_aktif' => false]);
    }
}
