<?php

/*
|--------------------------------------------------------------------------
| Identitas & konfigurasi desa
|--------------------------------------------------------------------------
|
| Nilai di sini adalah nilai CADANGAN (fallback). Data yang tampil di situs
| tetap diambil dari tabel profil_desas sehingga bisa diubah lewat dashboard
| admin. File ini dipakai untuk dua hal:
|
| 1. Nilai awal saat seeder pertama kali dijalankan di instalasi baru.
| 2. Nilai cadangan bila basis data belum siap (mis. saat migrate).
|
| Ubah nama desa di sini SEBELUM menjalankan "php artisan db:seed", atau
| langsung lewat menu Profil & Kontak di dashboard admin.
|
*/

return [

    // Awalan nomor referensi pengajuan, mis. "KRS" -> KRS-2026-0001.
    'prefix_referensi' => env('DESA_PREFIX_REFERENSI', 'KRS'),

    // Awalan kode surat untuk nomor surat pada pratinjau, mis. "DS-KRS".
    'kode_surat' => env('DESA_KODE_SURAT', 'DS-KRS'),

    // Tahun pembentukan desa, dipakai untuk teks profil bila kosong.
    'tahun_berdiri' => env('DESA_TAHUN_BERDIRI', ''),

    // Versi pemberitahuan data yang disetujui warga saat mengajukan. Tampil
    // pada detail pengajuan sebagai bukti persetujuan yang sah.
    'versi_pemberitahuan_data' => env('DESA_VERSI_PEMBERITAHUAN_DATA', '2026-01'),

    // Disk penyimpanan dokumen warga (KTP/KK/foto usaha).
    //   local = privat di server (storage/app/private)
    //   s3    = bucket privat Supabase Storage / S3
    // JANGAN memakai "public": berkas berisi data pribadi warga.
    'disk_dokumen' => env('DOKUMEN_DISK', 'local'),

    // Identitas dasar. Seeder menyalin nilai ini ke tabel profil_desas.
    // Nilainya dibaca dari kop dokumen resmi desa (docs/SURAT KET. DOMISILI.docx):
    //   PEMERINTAH KABUPATEN LAMPUNG SELATAN
    //   KECAMATAN JATI AGUNG
    //   DESA KARANG SARI
    //   Jl. RA. Basyid Desa Karang Sari Kecamatan Jati Agung
    //   Kabupaten Lampung Selatan 35365
    'nama_desa' => env('DESA_NAMA', 'Desa Karang Sari'),
    'kecamatan' => env('DESA_KECAMATAN', 'Jati Agung'),
    'kabupaten' => env('DESA_KABUPATEN', 'Lampung Selatan'),
    'provinsi' => env('DESA_PROVINSI', 'Lampung'),

    // Alamat kantor dan kode pos, dipakai pada kop surat.
    'alamat_kantor' => env(
        'DESA_ALAMAT_KANTOR',
        'Jl. RA. Basyid Desa Karang Sari Kecamatan Jati Agung Kabupaten Lampung Selatan'
    ),
    'kode_pos' => env('DESA_KODE_POS', '35365'),

    // Kode wilayah pada nomor surat resmi desa, mis. "470/  /18.01.13.2019/XI/2026".
    'kode_wilayah_surat' => env('DESA_KODE_WILAYAH_SURAT', '18.01.13.2019'),

];
