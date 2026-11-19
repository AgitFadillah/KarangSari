<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Titik koordinat peta kantor desa.
 *
 * Sebelumnya peta hanya memakai kueri teks ("Kantor Desa Karang Sari").
 * Google Maps menerjemahkan teks itu dengan caranya sendiri, sehingga
 * sematan bisa menunjuk jalan atau kecamatan yang salah — peta tampak
 * "tidak sesuai dengan lokasi kantor".
 *
 * Dengan koordinat yang diisi admin (diambil dari klik kanan pada Google
 * Maps), sematan memakai titik yang pasti: q=<lat>,<lng>. Kueri teks tetap
 * dipakai sebagai cadangan bila koordinat belum diisi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profil_desas', function (Blueprint $table) {
            // Disimpan sebagai teks agar titik desimal (mis. "-5.358421")
            // tidak dibulatkan oleh tipe desimal apa pun.
            $table->string('peta_lat', 30)->nullable()->after('kueri_peta');
            $table->string('peta_lng', 30)->nullable()->after('peta_lat');
        });
    }

    public function down(): void
    {
        Schema::table('profil_desas', function (Blueprint $table) {
            $table->dropColumn(['peta_lat', 'peta_lng']);
        });
    }
};
