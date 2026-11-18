<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data wilayah desa yang sebelumnya ditulis sebagai ilustrasi di
 * pages/profil.blade.php (batas wilayah dan penggunaan lahan).
 *
 * Disimpan sebagai teks sederhana agar perangkat desa dapat mengisinya dari
 * dashboard admin tanpa perlu tabel relasional baru:
 *
 *   batas_wilayah    -> satu baris "Arah: Nama desa/kecamatan"
 *   penggunaan_lahan -> satu baris "Label: persen"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profil_desas', function (Blueprint $table) {
            $table->text('batas_wilayah')->nullable()->after('misi');
            $table->text('penggunaan_lahan')->nullable()->after('batas_wilayah');
        });
    }

    public function down(): void
    {
        Schema::table('profil_desas', function (Blueprint $table) {
            $table->dropColumn(['batas_wilayah', 'penggunaan_lahan']);
        });
    }
};
