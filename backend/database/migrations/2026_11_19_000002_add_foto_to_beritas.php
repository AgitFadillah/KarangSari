<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto untuk berita, pengumuman, dan agenda.
 *
 * Sebelumnya kartu berita hanya menampilkan blok warna navy tanpa gambar,
 * sehingga berita kegiatan desa tidak bisa menyertakan dokumentasi foto.
 * Berkas disimpan pada disk "public" (bukan disk privat dokumen warga),
 * karena foto ini memang untuk ditampilkan di halaman publik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beritas', function (Blueprint $table) {
            $table->string('foto_path')->nullable()->after('penulis');
        });
    }

    public function down(): void
    {
        Schema::table('beritas', function (Blueprint $table) {
            $table->dropColumn('foto_path');
        });
    }
};
