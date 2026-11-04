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
├── README.md                     dokumentasi (satu-satunya berkas .md di repo)
│
├── index.html                    ── prototipe desain (acuan, JANGAN diubah) ──
├── profil.html
├── informasi.html
├── potensi.html
├── kontak.html
├── pelayanan.html
├── pelayanan-domisili.html
├── pelayanan-ktp.html
├── pelayanan-kematian.html
├── pelayanan-keramaian.html
├── pelayanan-tidak-mampu.html
├── pelayanan-usaha.html
├── pengajuan.html
├── pengajuan-berhasil.html
├── kebijakan-privasi.html
├── pemberitahuan-data.html
├── css/                          aset CSS prototipe
├── js/                           aset JavaScript prototipe
├── admin/                        halaman admin prototipe
│
└── backend/                      ── aplikasi Laravel 13 ──
    ├── app/
    │   ├── Enums/
    │   │   ├── RoleAdmin.php              peran admin + aturan kewenangan
    │   │   └── StatusPengajuan.php        status + aturan transisi
    │   ├── Http/
    │   │   ├── Controllers/
    │   │   │   ├── PageController.php             halaman publik
    │   │   │   ├── PengajuanController.php        form & simpan pengajuan
    │   │   │   └── Admin/
    │   │   │       ├── AuthController.php         login / logout
    │   │   │       ├── RingkasanController.php    dashboard
    │   │   │       ├── PengajuanController.php    verifikasi pengajuan
    │   │   │       ├── JenisSuratController.php   kelola jenis surat
    │   │   │       ├── KontenController.php       kelola konten website
    │   │   │       ├── ProfilController.php       kelola profil desa
    │   │   │       ├── AkunController.php         kelola akun admin
    │   │   │       └── LogController.php          log aktivitas
    │   │   ├── Middleware/
    │   │   │   ├── EnsureAdmin.php        wajib login + akun aktif
    │   │   │   └── SecurityHeaders.php    CSP, X-Frame-Options, nosniff, dll
    │   │   └── Requests/
    │   │       ├── StorePengajuanRequest.php          validasi form warga
    │   │       └── Admin/
    │   │           ├── LoginRequest.php               validasi + throttle login
    │   │           └── UpdateStatusPengajuanRequest.php
    │   ├── Models/
    │   │   ├── ProfilDesa.php          identitas desa
    │   │   ├── JenisSurat.php          jenis layanan surat
    │   │   ├── PersyaratanSurat.php    syarat tiap jenis surat
    │   │   ├── Pengajuan.php           pengajuan warga
    │   │   ├── DokumenPengajuan.php    berkas unggahan (privat)
    │   │   ├── RiwayatPengajuan.php    jejak perubahan status
    │   │   ├── KontenWebsite.php       konten halaman publik
    │   │   ├── LogAktivitas.php        audit trail admin
    │   │   └── User.php                akun admin (3 peran)
    │   ├── Providers/
    │   │   └── AppServiceProvider.php  view composer $profil, route binding
    │   ├── Services/
    │   │   ├── PengajuanService.php    nomor referensi, transaksi, normalisasi
    │   │   └── VerifikasiService.php   transisi status + log
    │   └── Support/
    │       └── SiteProfile.php         profil desa untuk seluruh view
    │
    ├── bootstrap/
    │   ├── app.php                     registrasi middleware & routing
    │   └── providers.php
    │
    ├── config/                         konfigurasi Laravel (10 berkas)
    │   └── filesystems.php             disk "local" — 'serve' => false (penting)
    │
    ├── database/
    │   ├── factories/
    │   │   └── UserFactory.php
    │   ├── migrations/                 8 migrasi: users, cache, jobs, profil,
    │   │                               jenis surat, pengajuan, konten
    │   └── seeders/
    │       ├── DatabaseSeeder.php      orkestrator seeder
    │       ├── ProfilDesaSeeder.php
    │       ├── JenisSuratSeeder.php
    │       ├── AdminSeeder.php         sandi dari ADMIN_SEED_PASSWORD
    │       ├── KontenWebsiteSeeder.php
    │       └── DemoPengajuanSeeder.php hanya jalan di local & testing
    │
    ├── public/                         document root
    │   ├── index.php
    │   ├── .htaccess
    │   ├── robots.txt
    │   └── favicon.ico
    │
    ├── resources/
    │   ├── css/                        app.css, site.css, style.css
    │   ├── js/                         app.js, site.js, main.js
    │   └── views/
    │       ├── layouts/                public, admin, auth + partials/
    │       ├── components/             admin-topbar
    │       ├── pages/                  beranda, profil, informasi, potensi,
    │       │                           kontak, pelayanan/, pengajuan/, privasi/
    │       └── admin/                  login, ringkasan, pengajuan/, jenis-surat/,
    │                                   konten/, profil/, akun/, log/
    │
    ├── routes/
    │   ├── web.php                     33 route publik & admin
    │   └── console.php
    │
    ├── storage/                        dokumen warga di app/private (tidak di repo)
    │
    ├── tests/
    │   ├── Feature/
    │   │   ├── PengajuanTest.php        alur pengajuan warga
    │   │   ├── AdminAuthTest.php        login, throttle, akun nonaktif
    │   │   ├── AdminPengajuanTest.php   verifikasi & batasan peran
    │   │   ├── KeamananTest.php         header, dokumen privat, enumerasi
    │   │   └── SeederTest.php           seeder demo tidak masuk produksi
    │   ├── Unit/ExampleTest.php
    │   └── TestCase.php
    │
    ├── .env.example                template konfigurasi (tanpa nilai rahasia)
    ├── .gitignore                  mengabaikan .env, *.md, konfigurasi agen AI
    ├── artisan
    ├── composer.json / composer.lock
    ├── package.json / package-lock.json
    ├── vite.config.js
    ├── phpunit.xml
    └── .editorconfig / .gitattributes / .npmrc
```

Berkas berikut **tidak ada di repositori** dan dibuat saat instalasi:
`backend/.env`, `backend/vendor/`, `backend/node_modules/`,
`backend/public/build/`, `backend/public/storage/`,
`backend/database/database.sqlite`, dan isi `backend/storage/`.

Nomor referensi memakai format `SBM-<tahun>-<urut 4 digit>`, mengikuti
prototipe (`admin/ringkasan.html`). Ubah `PengajuanService::nomorBerikutnya()`
bila format resmi desa berbeda.

## Lisensi

Dikembangkan untuk keperluan Desa KarangSari.
