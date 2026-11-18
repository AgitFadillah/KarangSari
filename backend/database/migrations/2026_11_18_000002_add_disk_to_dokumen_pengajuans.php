<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mencatat disk asal setiap dokumen.
 *
 * Sebelumnya semua dokumen warga dianggap berada di disk "local". Saat
 * penyimpanan dipindah ke Supabase Storage/S3, berkas lama tetap di server
 * lama — kolom ini membuat keduanya bisa hidup berdampingan tanpa salah
 * mencari berkas di bucket yang belum tentu memuatnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumen_pengajuans', function (Blueprint $table) {
            $table->string('disk', 20)->default('local')->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('dokumen_pengajuans', function (Blueprint $table) {
            $table->dropColumn('disk');
        });
    }
};