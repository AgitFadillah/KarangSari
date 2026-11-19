<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Isi koordinat Balai Desa Karang Sari pada profil yang sudah ada.
 *
 * Peta sebelumnya hanya memakai kueri teks "Balai Desa Karang Sari". Nama
 * desa ini cukup umum sehingga Google Maps kadang memilih lokasi lain dan
 * sematan tampak seperti area kosong. Koordinat di bawah adalah titik Balai
 * Desa Karang Sari, Kecamatan Jati Agung, Kabupaten Lampung Selatan.
 *
 * Nilai hanya diisi bila admin belum pernah mengisi koordinat, sehingga
 * perubahan yang dilakukan lewat dashboard tidak tertimpa.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('profil_desas')
            ->where(function ($query) {
                $query->whereNull('peta_lat')->orWhere('peta_lat', '');
            })
            ->where(function ($query) {
                $query->whereNull('peta_lng')->orWhere('peta_lng', '');
            })
            ->update([
                'peta_lat' => '-5.3234654',
                'peta_lng' => '105.2850958',
            ]);
    }

    public function down(): void
    {
        // Koordinat tidak dihapus saat rollback: admin mungkin sudah
        // memperbaruinya, dan titik ini tetap merupakan lokasi kantor desa.
    }
};
