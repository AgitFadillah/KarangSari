# KarangSari — Website Desa

Aplikasi web Desa KarangSari: situs publik (profil, informasi, potensi, kontak,
katalog layanan surat) dan dashboard admin (verifikasi pengajuan surat, jenis
surat, konten website, profil desa, akun, log aktivitas).

Repositori ini berisi dua bagian:

| Bagian | Lokasi | Keterangan |
| --- | --- | --- |
| Prototipe desain | akar repositori (`index.html`, `admin/`, `css/`, `js/`) | Acuan tampilan. **Tidak boleh diubah** — dipertahankan sebagai rujukan desain. |
| Aplikasi Laravel | `backend/` | Aplikasi berjalan: situs publik + dashboard admin, basis data, unggah dokumen, autentikasi. |

Aplikasi ini adalah hasil migrasi dari prototipe statis tersebut.

## Fitur

**Situs publik**

- Beranda, profil desa, informasi, potensi, dan kontak — seluruh teks
  identitas desa diambil dari basis data, bukan ditulis di kode.
- Berita, pengumuman, dan agenda desa yang dikelola lewat dashboard admin.
- Statistik ringkas desa ("Sekilas Desa") dan daftar potensi desa.
- Struktur perangkat desa, batas wilayah, dan penggunaan lahan.
- Katalog layanan surat beserta persyaratannya.
- Pengajuan surat daring: formulir tervalidasi (NIK, WhatsApp, berkas) dan
  halaman konfirmasi dengan nomor referensi + kode akses rahasia.
- **Cek status pengajuan** oleh warga memakai nomor referensi dan kode akses
  (tanpa akun).

**Dashboard admin**

- Verifikasi pengajuan: ubah status, catatan, riwayat, unduh dokumen, dan
  **verifikasi per berkas** (setujui / tolak tiap dokumen).
- Kelola berita & agenda, potensi desa, statistik beranda, dan perangkat desa.
- Kelola jenis surat, konten website, dan profil desa.
- Kelola akun admin berbasis peran.
- Log aktivitas seluruh tindakan admin, termasuk setiap pembukaan dokumen warga.

**Peran pengguna**

| Peran | Kewenangan |
| --- | --- |
| Administrator | Semua, termasuk kelola akun admin |
| Verifikator | Verifikasi & ubah status pengajuan, verifikasi dokumen, jenis surat |
| Operator Konten | Kelola berita, potensi, statistik, perangkat desa, konten, profil |

## Kebutuhan

- PHP 8.3+ dengan ekstensi `pdo_sqlite`, `fileinfo`, `gd`
- Composer
- Node.js 20+ (untuk aset Vite)

## Instalasi

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Atur kata sandi akun admin awal di `.env` (minimal 12 karakter). Tanpa nilai
ini, seeder membuat kata sandi acak dan menampilkannya sekali di konsol:

```
ADMIN_SEED_PASSWORD=ganti-dengan-sandi-kuat
```

Identitas desa juga dapat ditentukan lebih awal lewat `.env`. Nilai ini dipakai
sebagai isi awal tabel `profil_desas` **dan** sebagai cadangan bila basis data
belum siap — setelah instalasi, ubah lewat menu *Profil & Kontak*:

```
DESA_NAMA="Desa KarangSari"
DESA_KECAMATAN=
DESA_KABUPATEN=
DESA_PROVINSI=
DESA_PREFIX_REFERENSI=KRS      # awalan nomor referensi -> KRS-2026-0001
DESA_KODE_SURAT=DS-KRS         # awalan kode pada pratinjau surat
```

Lalu siapkan basis data dan aset:

```bash
php artisan migrate:fresh --seed
php artisan storage:link      # agar logo desa (disk "public") bisa ditampilkan
npm install
npm run build                 # wajib: view memakai @vite(...)
```

> `npm run build` (atau `npm run dev`) wajib dijalankan. Tanpa manifest Vite,
> semua halaman akan melempar "Vite manifest not found".

## Menjalankan

```bash
php artisan serve
```

- Situs publik: <http://127.0.0.1:8000>
- Dashboard admin: <http://127.0.0.1:8000/admin/login>

Akun hasil seeder (kata sandi mengikuti `ADMIN_SEED_PASSWORD`):

| Username | Nama           | Peran           |
| -------- | -------------- | --------------- |
| `sari`   | Sari Wulandari | Administrator   |
| `rangga` | Rangga Pratama | Verifikator     |
| `dewi`   | Dewi Lestari   | Operator Konten |

`DatabaseSeeder` mengisi 8 pengajuan contoh (`KRS-<tahun>-0001..0008`) dengan
status bervariasi, **hanya di lingkungan `local` dan `testing`**. Di produksi
dashboard dimulai kosong. Untuk basis data tanpa pengajuan contoh di lokal,
jalankan seeder satu per satu:

```bash
php artisan migrate:fresh
php artisan db:seed --class=ProfilDesaSeeder
php artisan db:seed --class=JenisSuratSeeder
php artisan db:seed --class=AdminSeeder
php artisan db:seed --class=KontenWebsiteSeeder
```

Seeder sengaja **tidak** mengisi berita, potensi, statistik, atau perangkat
desa: data itu milik pemerintah desa dan diisi sendiri lewat dashboard admin.

## Pengujian

```bash
cd backend
php artisan test
```

Cakupan: alur pengajuan warga (validasi WhatsApp, isian surat sesuai template,
ukuran & format berkas opsional, nomor referensi berurutan termasuk lintas 9999,
riwayat), revisi warga tanpa pengajuan baru, proteksi halaman konfirmasi, cek
status oleh warga (tanpa membocorkan NIK/alamat), autentikasi admin (throttle,
akun nonaktif), transisi status pengajuan beserta batasan peran, verifikasi
dokumen per berkas, CRUD konten desa (berita, potensi, statistik, perangkat),
sorotan berita pada halaman Informasi, panduan admin, akses dokumen privat,
seeder, dan header keamanan.

`HalamanPublikTest` juga memastikan seluruh halaman publik tetap dapat
dirender meski belum ada satu pun data konten (kondisi setelah instalasi baru).

## Alur pengajuan singkat

1. Warga memilih layanan, mengisi data pemohon (nama, alamat, keperluan,
   WhatsApp) dan **isian surat** yang otomatis diturunkan dari template resmi
   jenis surat (`{{penanda}}` kustom menjadi kolom isian, penanda otomatis
   seperti `{{nama_desa}}`/`{{tanggal}}` terisi sendiri).
2. Warga menerima **nomor referensi** (`KRS-<tahun>-<urut>`) untuk komunikasi
   dengan petugas, **plus kode akses acak** (mis. `K7QM3FZP`) yang menjadi kunci
   pelacakan sebenarnya. Nomor referensi berurutan sehingga tidak dipakai
   sebagai token.
3. Warga memantau lewat `/cek-status` dengan nomor referensi **dan** kode akses.
   Halaman hasil menampilkan kembali keduanya beserta tombol salin, jadi kode
   tidak "hilang" hanya karena pindah perangkat. Bila benar-benar lupa, warga
   memulihkannya di `/cek-status/lupa-kode` dengan nomor referensi + tanggal
   lahir (dibatasi 8 percobaan per 10 menit).
   Bila admin meminta perbaikan, warga memperbaiki isian di
   `/pengajuan/revisi/{nomorReferensi}?kode=...` — nomor referensi tetap sama,
   penghitung `revisi_ke` bertambah.
4. Panduan warga ada di `/panduan`, panduan admin di `/admin/panduan`.


## Keamanan

- **Dokumen warga bersifat privat.** KTP dan KK tidak lagi menjadi persyaratan
  bawaan (data kependudukan sudah ada di desa), tetapi mekanisme unggah tetap
  tersedia untuk persyaratan tambahan seperti surat pengantar RT/RW. Berkas
  disimpan di disk privat (`local`/`storage/app/private`, atau bucket privat
  Supabase Storage bila `DOKUMEN_DISK=s3`) dan tidak punya URL publik.
  Unduhan hanya lewat route admin `admin.pengajuan.dokumen` yang mewajibkan
  login dan peran Administrator/Verifikator.
- **Halaman pengajuan hanya untuk Administrator & Verifikator.** Operator
  Konten tidak dapat membuka daftar/detail pengajuan maupun mengunduh
  KTP/KK atau surat jadi; menu dan kotak ringkasannya pun disembunyikan.
- **Kode akses acak per pengajuan.** Nomor referensi (`KRS-2026-0001`)
  berurutan dan mudah ditebak, sehingga bukan kunci. Warga memakai kode acak
  8 karakter (`2-9`, `A-Z` tanpa `I`/`O`) untuk membuka surat, revisi, dan
  status. Akses diberikan lewat `?kode=` atau sesi perangkat yang sudah
  ditandai; kode salah/kosong menghasilkan 404. Kode disimpan sebagai kolom
  tersembunyi (tidak ikut JSON) dan dikirim petugas lewat tombol WhatsApp.
- **Jangan mengaktifkan `serve` pada disk privat** di
  `backend/config/filesystems.php`. Opsi itu sengaja dimatikan; mengaktifkannya
  kembali membuka `/storage/{path}` untuk publik dan mengekspos dokumen warga
  tanpa autentikasi.
- **`.env` tidak pernah masuk repositori.** Selalu buat dari `.env.example`.
- **Di produksi wajib** `APP_DEBUG=false`, `APP_ENV=production`, dan `APP_KEY`
  baru hasil `php artisan key:generate`.
- **Ganti kata sandi admin** sebelum situs dipublikasikan.
- **Kata sandi tidak pernah ditulis di kode**, hanya lewat `.env`.
- **Konten berita/profil disaring** oleh `TeksBaca`: tag di luar daftar aman
  dibuang, event handler (`onclick=`), `javascript:`, dan atribut `style`
  dihapus dari semua tag.
- Sudah aktif: CSRF, rate limit per-rute (login, kirim pengajuan, revisi,
  cek status), header keamanan (CSP, `X-Frame-Options`, `nosniff`,
  `Referrer-Policy`, `Permissions-Policy`), HSTS saat HTTPS, dan dukungan
  Trusted Proxies lewat `TRUSTED_PROXIES`.

## Hosting

Aplikasi ini **tidak bisa dijalankan di GitHub Pages** karena membutuhkan PHP
dan basis data di sisi server, sedangkan GitHub Pages hanya menyajikan berkas
statis. Pilihan yang sesuai:

- Shared hosting PHP + MySQL (paling ekonomis dan umum untuk situs desa)
- VPS dengan Nginx + PHP-FPM + MySQL/Postgres
- Platform terkelola seperti Laravel Cloud

**Supabase** dapat dipakai sebagai **database Postgres + Storage** (bucket
privat), tetapi Supabase **tidak menjalankan PHP**. Backend Laravel tetap harus
di-host di layanan PHP. Konfigurasi contoh ada di
`backend/.env.production.example` (`DB_CONNECTION=pgsql`, `DOKUMEN_DISK=s3`).

## Struktur

```
.
├── index.html, *.html        prototipe desain (acuan, jangan diubah)
├── css/, js/, admin/         aset prototipe (acuan, jangan diubah)
└── backend/                  aplikasi Laravel 13
    ├── app/Http/Controllers/  controller publik + Admin/
    ├── app/Http/Middleware/   EnsureAdmin (alias "admin"), SecurityHeaders
    ├── app/Models/            ProfilDesa, JenisSurat, Berita, PotensiDesa, ...
    ├── app/Services/          PengajuanService, VerifikasiService
    ├── app/Support/           SiteProfile — profil desa untuk seluruh view
    ├── config/desa.php        identitas desa & awalan nomor (fallback + nilai awal)
    ├── resources/views/       tampilan Blade (publik & admin)
    ├── database/              migrasi, seeder, factory
    ├── routes/web.php         seluruh route publik & admin
    └── tests/                 pengujian otomatis
```

Nomor referensi memakai format `<awalan>-<tahun>-<urut>`, dengan awalan dari
`config('desa.prefix_referensi')` (bawaan `KRS`, dapat diubah lewat
`DESA_PREFIX_REFERENSI`). Nomor urut otomatis melanjutkan ke 5 digit setelah
9999, dan pembuatan nomor tahan terhadap pengajuan bersamaan.

## Lisensi

Dikembangkan untuk keperluan Desa KarangSari.
