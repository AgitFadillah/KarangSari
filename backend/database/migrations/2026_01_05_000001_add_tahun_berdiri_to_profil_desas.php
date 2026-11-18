<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tahun berdirinya desa.
 *
 * Sebelumnya kartu "Berdiri" pada halaman Profil mengambil nilai dari
 * config('desa.tahun_berdiri') yang tidak punya isi dan tidak dapat diubah
 * perangkat desa. Kolom ini membuatnya dapat diisi lewat dashboard admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profil_desas', function (Blueprint $table) {
            $table->string('tahun_berdiri', 20)->nullable()->after('jam_kunjungan');
        });
    }

    public function down(): void
    {
        Schema::table('profil_desas', function (Blueprint $table) {
            $table->dropColumn('tahun_berdiri');
        });
    }
};
