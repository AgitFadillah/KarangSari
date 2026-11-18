<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Melengkapi konfigurasi jenis surat agar setara halaman "Jenis Surat" pada
 * dashboard admin:
 *
 *  - field formulir tambahan dikelola terstruktur (label, kunci, tipe, wajib),
 *    bukan hanya disimpulkan dari penanda {{...}} di template;
 *  - persyaratan dokumen menyimpan format & ukuran maksimum sehingga validasi
 *    saat warga mengunggah tidak lagi hanya soal jumlah berkas.
 *
 * Kedua kolom bersifat nullable agar data lama tetap sah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_surats', function (Blueprint $table) {
            // [{"label":"Lama tinggal","kunci":"lama_tinggal","tipe":"text","wajib":true}, ...]
            $table->json('field_formulir')->nullable()->after('template_surat');
        });

        Schema::table('persyaratan_surats', function (Blueprint $table) {
            // Contoh: pdf,jpg,jpeg,png — kosong berarti semua format gambar/PDF.
            $table->string('format', 100)->nullable()->after('label');
            $table->unsignedSmallInteger('maks_kb')->nullable()->after('format');
            // Bawaan TIDAK wajib: unggah dokumen memang opsional pada sistem ini
            // (lihat StorePengajuanRequest). Admin dapat menandainya wajib.
            $table->boolean('wajib')->default(false)->after('maks_kb');
        });
    }

    public function down(): void
    {
        Schema::table('jenis_surats', function (Blueprint $table) {
            $table->dropColumn('field_formulir');
        });

        Schema::table('persyaratan_surats', function (Blueprint $table) {
            $table->dropColumn(['format', 'maks_kb', 'wajib']);
        });
    }
};
