<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Melengkapi profil desa agar seluruh isian pada dokumen "PROFIL desa.docx"
 * dapat diisi dari dashboard admin dan tampil di halaman publik.
 *
 * Dokumen tersebut memuat bagian-bagian berikut:
 *   - Kondisi umum desa : luas wilayah, jumlah penduduk, jumlah KK, iklim
 *   - Sejarah desa      : narasi + daftar dusun + riwayat kepala desa
 *   - Demografi         : jarak tempuh, penduduk L/P, golongan umur, agama,
 *                         pendidikan, mata pencaharian
 *   - Keadaan sosial    : narasi + tabel kesejahteraan masyarakat
 *
 * Nilai tunggal (luas, iklim, narasi) disimpan sebagai kolom profil_desas.
 * Data berbaris (tabel demografi, riwayat kepala desa) disimpan pada tabel
 * data_desas dengan satu bentuk baris yang sama agar dapat dikelola lewat
 * satu halaman admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profil_desas', function (Blueprint $table) {
            // Bagian "Kondisi Umum Desa".
            $table->text('kondisi_umum')->nullable()->after('sejarah');
            $table->text('iklim')->nullable()->after('kondisi_umum');
            $table->string('luas_wilayah', 30)->nullable()->after('iklim');
            // Bagian "Demografi": jarak tempuh ke ibu kota.
            $table->string('jarak_provinsi', 20)->nullable()->after('luas_wilayah');
            $table->string('jarak_kabupaten', 20)->nullable()->after('jarak_provinsi');
            $table->string('jarak_kecamatan', 20)->nullable()->after('jarak_kabupaten');
            // Jumlah penduduk menurut jenis kelamin dan kepala keluarga.
            $table->string('penduduk_laki_laki', 20)->nullable()->after('jarak_kecamatan');
            $table->string('penduduk_perempuan', 20)->nullable()->after('penduduk_laki_laki');
            $table->string('jumlah_kk', 20)->nullable()->after('penduduk_perempuan');
            // Bagian "Keadaan Sosial".
            $table->text('keadaan_sosial')->nullable()->after('jumlah_kk');
            // Daftar dusun, satu nama per baris.
            $table->text('daftar_dusun')->nullable()->after('keadaan_sosial');
        });

        Schema::create('data_desas', function (Blueprint $table) {
            $table->id();
            // golongan_umur|agama|pendidikan|pencaharian|kesejahteraan|riwayat_kades
            $table->string('kelompok', 30);
            $table->string('label', 120);
            $table->string('nilai', 40)->nullable();      // angka atau teks pendek
            $table->string('nilai_l', 20)->nullable();    // laki-laki (golongan umur)
            $table->string('nilai_p', 20)->nullable();    // perempuan (golongan umur)
            $table->string('keterangan', 120)->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['kelompok', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_desas');

        Schema::table('profil_desas', function (Blueprint $table) {
            $table->dropColumn([
                'kondisi_umum', 'iklim', 'luas_wilayah',
                'jarak_provinsi', 'jarak_kabupaten', 'jarak_kecamatan',
                'penduduk_laki_laki', 'penduduk_perempuan', 'jumlah_kk',
                'keadaan_sosial', 'daftar_dusun',
            ]);
        });
    }
};
