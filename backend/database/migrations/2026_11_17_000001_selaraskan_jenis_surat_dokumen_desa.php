<?php

use App\Models\JenisSurat;
use App\Support\TemplateSuratDesa;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menyelaraskan tujuh jenis surat dengan dokumen Word resmi desa.
 *
 * Sebelumnya hanya template teks yang dipasang; kolom isian formulir dan
 * persyaratan masih bawaan sistem lama (ditambah kategori KTP dan keramaian
 * yang tidak punya dokumen desa). Migrasi ini:
 *
 *  - memperbarui nama, kode, urutan, template, dan field formulir ketujuh
 *    kategori resmi;
 *  - menambahkan persyaratan bawaan hanya bila kategori belum punya
 *    persyaratan sama sekali (konfigurasi admin tidak dihapus);
 *  - menonaktifkan KTP dan keramaian, tanpa menghapus pengajuan lamanya.
 */
return new class extends Migration
{
    public function up(): void
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

            if ($jenisSurat->persyaratan()->exists()) {
                continue;
            }

            foreach ($data['persyaratan'] as $urutan => $syarat) {
                $jenisSurat->persyaratan()->create([
                    'label' => $syarat['label'],
                    'petunjuk' => $syarat['petunjuk'],
                    'urutan' => $urutan + 1,
                ]);
            }
        }

        DB::table('jenis_surats')
            ->whereIn('slug', ['ktp', 'keramaian'])
            ->update(['is_aktif' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Data pengajuan lama tetap; kategori tidak dikembalikan otomatis.
    }
};
