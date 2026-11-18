<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revisi formulir pengajuan:
 *
 * 1. NIK, tempat lahir, tanggal lahir, dan jenis kelamin tidak lagi diisi
 *    warga di formulir, jadi kolomnya dibuat NULLABLE. Data lama tetap utuh
 *    dan admin masih dapat mengisinya saat verifikasi bila diperlukan.
 *
 * 2. Isian surat: warga mengisi kolom yang berasal dari placeholder template
 *    surat desa. Nilainya disimpan sebagai JSON agar fleksibel per jenis surat.
 *
 * 3. Template surat menyimpan daftar placeholder hasil parsing supaya halaman
 *    pengajuan tidak perlu mem-parse ulang setiap kali request.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            // Isian template surat: {"nama_ayah":"...","pekerjaan":"..."}
            $table->json('isian_surat')->nullable()->after('keperluan');

            // Bermacam-macam data pemohon kini opsional.
            $table->char('nik', 16)->nullable()->change();
            $table->string('tempat_lahir')->nullable()->change();
            $table->date('tanggal_lahir')->nullable()->change();
            $table->string('jenis_kelamin', 20)->nullable()->change();

            // Jejak revisi: pengajuan yang dikirim ulang setelah permintaan
            // perbaikan tidak membuat nomor referensi baru, cukup menambah
            // penghitung agar admin tahu ini kiriman ke berapa.
            $table->unsignedSmallInteger('revisi_ke')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn(['isian_surat', 'revisi_ke']);
        });
    }
};
