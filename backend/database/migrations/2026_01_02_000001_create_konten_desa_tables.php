<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel konten yang sebelumnya ditulis langsung di Blade (data dummy):
 * statistik desa, berita/pengumuman, agenda, dan potensi desa.
 *
 * Setelah migrasi ini, perangkat desa dapat mengubah semuanya dari
 * dashboard admin tanpa menyentuh kode.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Angka "Sekilas Desa" di beranda: penduduk, KK, luas wilayah, dusun.
        Schema::create('statistik_desas', function (Blueprint $table) {
            $table->id();
            $table->string('label', 80);                 // "Penduduk"
            $table->string('nilai', 30);                 // "4812" disimpan sebagai teks agar fleksibel
            $table->string('satuan', 50)->nullable();    // "jiwa terdata"
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });

        // Berita & pengumuman desa.
        Schema::create('beritas', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('slug', 160)->unique();
            $table->string('kategori', 40)->default('berita'); // berita|pengumuman|agenda
            $table->text('ringkasan')->nullable();
            $table->longText('isi')->nullable();
            $table->string('penulis', 120)->nullable();
            $table->timestamp('terbit_at')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['kategori', 'terbit_at']);
        });

        // Potensi desa: UMKM, wisata, pertanian, dan lain-lain.
        Schema::create('potensi_desas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kategori', 60)->nullable();   // UMKM|Wisata|Pertanian
            $table->text('deskripsi')->nullable();
            $table->string('foto_path')->nullable();
            $table->string('kontak', 120)->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });

        // Perangkat desa untuk halaman profil.
        Schema::create('perangkat_desas', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 120);
            $table->string('jabatan', 120);
            $table->string('nip', 40)->nullable();
            $table->string('foto_path')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });

        // Nomor surat resmi yang diterbitkan admin (tidak lagi dikarang di view).
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->string('nomor_surat', 80)->nullable()->after('nomor_referensi');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn('nomor_surat');
        });

        Schema::dropIfExists('perangkat_desas');
        Schema::dropIfExists('potensi_desas');
        Schema::dropIfExists('beritas');
        Schema::dropIfExists('statistik_desas');
    }
};
