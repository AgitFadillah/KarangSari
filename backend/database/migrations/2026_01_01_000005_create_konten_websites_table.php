<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konten_websites', function (Blueprint $table) {
            $table->id();
            $table->string('bagian', 60);        // hero, profil, layanan, informasi, kontak
            $table->string('judul');
            $table->longText('isi')->nullable();
            $table->string('status', 20)->default('draf'); // draf|terbit
            $table->foreignId('diperbarui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['bagian', 'status']);
        });

        Schema::create('log_aktivitets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('pelaku')->nullable();          // snapshot nama
            $table->string('tindakan');                    // "Menyetujui pengajuan", ...
            $table->string('subjek')->nullable();          // nomor referensi / nama
            $table->string('tingkat', 20)->default('info'); // info|sukses|peringatan
            $table->text('keterangan')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitets');
        Schema::dropIfExists('konten_websites');
    }
};
