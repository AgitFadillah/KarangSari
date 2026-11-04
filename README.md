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
- Katalog layanan surat beserta persyaratannya.
- Pengajuan surat daring: formulir tervalidasi (NIK, WhatsApp, berkas) dan
  halaman konfirmasi dengan nomor referensi.

**Dashboard admin**

- Verifikasi pengajuan: ubah status, catatan, riwayat, dan unduh dokumen.
- Kelola jenis surat, konten website, dan profil desa.
- Kelola akun admin berbasis peran.
- Log aktivitas seluruh tindakan admin.

**Peran pengguna**

| Peran | Kewenangan |
| --- | --- |
| Administrator | Semua, termasuk kelola akun admin |
| Verifikator | Verifikasi & ubah status pengajuan |
| Operator Konten | Kelola jenis surat, konten, profil desa |

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

`DatabaseSeeder` mengisi 8 pengajuan contoh (`SBM-<tahun>-0001..0008`) dengan
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

## Pengujian

```bash
cd backend
php artisan test
```

Cakupan: alur pengajuan warga (validasi NIK/WhatsApp/ukuran & format berkas,
nomor referensi berurutan, riwayat), proteksi halaman konfirmasi, autentikasi
admin (throttle, akun nonaktif), transisi status pengajuan beserta batasan
peran, akses dokumen privat, seeder, dan header keamanan.

## Keamanan

- **Dokumen warga (KTP/KK) bersifat privat.** Berkas disimpan di disk `local`
  (`storage/app/private`) dan tidak punya URL publik. Unduhan hanya lewat route
  admin `admin.pengajuan.dokumen` yang mewajibkan login.
- **Jangan mengaktifkan `serve` pada disk `local`** di
  `backend/config/filesystems.php`. Opsi itu sengaja dimatikan; mengaktifkannya
  kembali membuka `/storage/{path}` untuk publik dan mengekspos dokumen warga
  tanpa autentikasi.
- **`.env` tidak pernah masuk repositori.** Selalu buat dari `.env.example`.
- **Di produksi wajib** `APP_DEBUG=false`, `APP_ENV=production`, dan `APP_KEY`
  baru hasil `php artisan key:generate`.
- **Ganti kata sandi admin** sebelum situs dipublikasikan.
- **Kata sandi tidak pernah ditulis di kode**, hanya lewat `.env`.
- Halaman konfirmasi (`/pengajuan/berhasil/{id}`) dilindungi nomor referensi
  sebagai token, sehingga data warga tidak bisa dienumerasi lewat ID.
- Sudah aktif: CSRF, rate limit login, header keamanan (CSP, `X-Frame-Options`,
  `nosniff`, `Referrer-Policy`, `Permissions-Policy`), dan HSTS saat HTTPS.

## Hosting

Aplikasi ini **tidak bisa dijalankan di GitHub Pages** karena membutuhkan PHP
dan basis data di sisi server, sedangkan GitHub Pages hanya menyajikan berkas
statis. Pilihan yang sesuai:

- Shared hosting PHP + MySQL (paling ekonomis dan umum untuk situs desa)
- VPS dengan Nginx + PHP-FPM + MySQL
- Platform terkelola seperti Laravel Cloud

## Struktur

```
.
├── index.html, *.html        prototipe desain (acuan, jangan diubah)
├── css/, js/, admin/         aset prototipe (acuan, jangan diubah)
└── backend/                  aplikasi Laravel 13
    ├── app/Http/Controllers/  controller publik + Admin/
    ├── app/Http/Middleware/   EnsureAdmin (alias "admin"), SecurityHeaders
    ├── app/Models/            ProfilDesa, JenisSurat, Persyaratan, Pengajuan, ...
    ├── app/Services/          PengajuanService, VerifikasiService
    ├── app/Support/           SiteProfile — profil desa untuk seluruh view
    ├── resources/views/       tampilan Blade (publik & admin)
    ├── database/              migrasi, seeder, factory
    ├── routes/web.php         seluruh route publik & admin
    └── tests/                 pengujian otomatis
```

Nomor referensi memakai format `SBM-<tahun>-<urut 4 digit>`, mengikuti
prototipe (`admin/ringkasan.html`). Ubah `PengajuanService::nomorBerikutnya()`
bila format resmi desa berbeda.

## Lisensi

Dikembangkan untuk keperluan Desa KarangSari.
