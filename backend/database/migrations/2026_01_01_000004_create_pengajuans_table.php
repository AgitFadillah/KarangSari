<?php

use App\Enums\StatusPengajuan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_referensi', 30)->unique(); // SBM-2026-0001
            $table->foreignId('jenis_surat_id')->constrained('jenis_surats')->restrictOnDelete();

            // Data pemohon (sesuai formulir pengajuan.html)
            $table->string('nama');
            $table->char('nik', 16);
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->string('jenis_kelamin', 20);
            $table->text('alamat');
            $table->text('keperluan');
            $table->string('whatsapp', 30);

            // Persetujuan privasi (wajib, UU PDP)
            $table->boolean('persetujuan_data')->default(false);
            $table->timestamp('persetujuan_at')->nullable();
            $table->string('ip_pemohon', 45)->nullable();

            $table->string('status', 30)->default(StatusPengajuan::MenungguVerifikasi->value)->index();
            $table->text('catatan_admin')->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->timestamp('selesai_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['nik', 'created_at']);
        });

        Schema::create('dokumen_pengajuans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
            $table->foreignId('persyaratan_surat_id')->nullable()->constrained('persyaratan_surats')->nullOnDelete();
            $table->string('label');                 // snapshot label saat diajukan
            $table->string('path');                  // storage/app/private/...
            $table->string('nama_asli');
            $table->string('mime', 100);
            $table->unsignedBigInteger('ukuran');
            $table->string('status_verifikasi', 20)->default('menunggu'); // menunggu|terverifikasi|ditolak
            $table->timestamps();
        });

        Schema::create('riwayat_pengajuans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
            $table->string('status', 30);
            $table->string('catatan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aktor', 30)->default('sistem'); // warga|admin|sistem
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_pengajuans');
        Schema::dropIfExists('dokumen_pengajuans');
        Schema::dropIfExists('pengajuans');
    }
};
