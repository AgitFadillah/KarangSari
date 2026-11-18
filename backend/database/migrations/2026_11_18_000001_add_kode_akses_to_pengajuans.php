<?php

use App\Support\KodeAkses;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan "kode akses" pada pengajuan.
 *
 * Sebelumnya nomor referensi (KRS-2026-0001) sekaligus menjadi kunci akses.
 * Karena berurutan dan mudah ditebak, warga lain bisa membuka surat/revisi
 * milik orang lain. Kode akses acak ini menggantikan peran itu; nomor
 * referensi tetap dipakai untuk komunikasi dengan petugas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->string('kode_akses', 12)->nullable()->after('nomor_referensi');
        });

        // Pengajuan lama belum punya kode. Buatkan kode unik untuk masing-masing
        // agar warga yang masih memegang nomor referensi lama tetap bisa
        // membuka suratnya — petugas dapat melihat & mengirimkan kode di
        // halaman detail pengajuan.
        $dipakai = [];

        DB::table('pengajuans')->orderBy('id')->select('id')->pluck('id')->each(function ($id) use (&$dipakai) {
            do {
                $kode = KodeAkses::acak();
            } while (isset($dipakai[$kode]));

            $dipakai[$kode] = true;

            DB::table('pengajuans')->where('id', $id)->update(['kode_akses' => $kode]);
        });

        Schema::table('pengajuans', function (Blueprint $table) {
            $table->unique('kode_akses');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropUnique(['kode_akses']);
            $table->dropColumn('kode_akses');
        });
    }
};
