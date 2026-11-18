<?php

use App\Support\TemplateSuratDesa;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Memasang template surat resmi desa yang disalin dari dokumen Word yang
 * diberikan perangkat desa (folder docs/).
 *
 * Hanya jenis surat yang dokumennya sudah diserahkan yang diubah; jenis surat
 * lain dibiarkan memakai template bawaannya sampai dokumennya masuk. Template
 * tetap dapat disunting lagi lewat menu Jenis Surat di dashboard admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (TemplateSuratDesa::katalog() as $slug => $data) {
            DB::table('jenis_surats')
                ->where('slug', $slug)
                ->update([
                    'template_surat' => $data['template'],
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Template tidak dikembalikan: isinya teks resmi desa yang aman
        // dipakai ulang, dan mengembalikannya ke versi bawaan sistem justru
        // menghapus salinan dokumen resmi.
    }
};
