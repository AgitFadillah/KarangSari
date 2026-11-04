<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_surats', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();          // domisili, usaha, tidak-mampu, dst.
            $table->string('kode', 20)->unique();          // SKD, SKU, SKTM ...
            $table->string('kode_layanan', 20)->nullable(); // kode klasifikasi (470, 471, ...)
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->string('estimasi_hari', 30)->nullable();
            $table->text('template_surat')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('persyaratan_surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_surat_id')->constrained('jenis_surats')->cascadeOnDelete();
            $table->string('label');
            $table->string('petunjuk')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persyaratan_surats');
        Schema::dropIfExists('jenis_surats');
    }
};
