# KarangSari — Website Resmi Desa Karang Sari

<div align="center">

**Sistem Informasi Desa**: situs publik + dashboard admin untuk pelayanan surat daring.

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat-square&logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![Vite](https://img.shields.io/badge/Vite-8-646CFF?style=flat-square&logo=vite&logoColor=white)](https://vitejs.dev)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-12-3C9CD7?style=flat-square&logo=phpunit&logoColor=white)](https://phpunit.de)
[![Tes](https://img.shields.io/badge/tes-418%20lulus-2ea44f?style=flat-square)](#-pengujian)

Situs live: **<https://desa-karangsari.up.railway.app>**

</div>

---

## Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Fitur](#-fitur)
- [Peran Pengguna](#-peran-pengguna)
- [Kebutuhan Sistem](#-kebutuhan-sistem)
- [Instalasi](#-instalasi)
- [Menjalankan](#-menjalankan)
- [Pengujian](#-pengujian)
- [Alur Pengajuan Surat](#-alur-pengajuan-surat)
- [Keamanan](#-keamanan)
- [Deploy ke Railway](#-deploy-ke-railway)
- [Struktur Repositori](#-struktur-repositori)
- [Pemecahan Masalah](#-pemecahan-masalah)
- [Lisensi](#-lisensi)

---

## 📖 Tentang Proyek

Website Desa Karang Sari, Kecamatan Jati Agung, Kabupaten Lampung Selatan.
Aplikasi ini menggantikan situs statis menjadi sistem terpadu: warga dapat
mengajukan surat keterangan secara daring dan memantau statusnya tanpa harus
datang ke kantor desa, sementara perangkat desa mengelola seluruh konten dan
pengajuan melalui satu dashboard.

Repositori ini berisi **dua bagian**:

| Bagian | Lokasi | Keterangan |
| --- | --- | --- |
| **Prototipe desain** | akar repositori (`index.html`, `admin/`, `css/`, `js/`) | Acuan tampilan awal. **Tidak diubah** — dipertahankan sebagai rujukan desain. |
| **Aplikasi Laravel** | `backend/` | Aplikasi yang berjalan: situs publik + dashboard admin, basis data, unggah dokumen, autentikasi. |

> Aplikasi Laravel adalah hasil migrasi dari prototipe statis tersebut. Semua
> data identitas desa diambil dari basis data, bukan ditulis di kode.

---

## ✨ Fitur

### Situs publik

| Fitur | Keterangan |
| --- | --- |
| **Beranda** | Sekilas desa, layanan populer, berita & agenda terbaru |
| **Profil desa** | Sejarah, visi & misi, struktur pemerintahan, wilayah, demografi, keadaan sosial, riwayat kepala desa |
| **Informasi** | Berita, pengumuman, dan agenda desa dengan penyaringan kategori |
| **Potensi desa** | Potensi unggulan beserta deskripsi dan foto |
| **Kontak** | Alamat, telepon, WhatsApp, jam layanan, dan peta lokasi |
| **Katalog layanan** | 7 jenis surat beserta persyaratan dan estimasi waktu |
| **Pengajuan surat daring** | Formulir tervalidasi (NIK, WhatsApp, berkas) dengan nomor referensi |
| **Cek status** | Warga memantau pengajuan memakai nomor referensi + kode akses acak |
| **Revisi mandiri** | Warga memperbaiki isian yang dikembalikan petugas tanpa membuat pengajuan baru |

### Dashboard admin

| Fitur | Keterangan |
| --- | --- |
| **Ringkasan** | Statistik pengajuan dan daftar yang menunggu tindakan |
| **Verifikasi pengajuan** | Ubah status, beri catatan, lihat riwayat, unduh dokumen |
| **Verifikasi per berkas** | Setujui/tolak tiap dokumen yang diunggah warga |
| **Unduh surat Word** | Surat jadi dibuat dari kerangka `.docx` resmi desa |
| **Kelola berita & agenda** | CRUD dengan unggah foto dan sorotan beranda |
| **Kelola potensi desa** | CRUD potensi unggulan |
| **Kelola statistik beranda** | Angka ringkas yang tampil di beranda |
| **Kelola perangkat desa** | CRUD dengan foto, jabatan, dan NIP |
| **Kelola jenis surat** | Template surat, kolom isian, dan persyaratan |
| **Kelola konten website** | Teks halaman publik yang dapat disunting |
| **Profil & kontak desa** | Identitas desa, logo, peta, batas wilayah, penggunaan lahan |
| **Kelola akun admin** | CRUD akun berbasis peran |
| **Log aktivitas** | Mencatat seluruh tindakan admin, termasuk pembukaan dokumen warga |

---

## 👥 Peran Pengguna

| Peran | Kewenangan |
| --- | --- |
| **Administrator** | Semua fitur, termasuk kelola akun admin dan pengaturan profil desa |
| **Verifikator** | Verifikasi & ubah status pengajuan, verifikasi dokumen, kelola jenis surat |
| **Operator Konten** | Kelola berita, potensi, statistik, perangkat desa, konten, profil |

> **Penting:** halaman pengajuan (daftar, detail, unduh dokumen warga) **hanya**
> dapat diakses Administrator dan Verifikator. Operator Konten tidak melihat
> data warga sama sekali — menu dan kotak ringkasannya pun disembunyikan.

---

## 💻 Kebutuhan Sistem

| Perangkat | Versi minimal | Catatan |
| --- | --- | --- |
| **PHP** | 8.4 | |
| **Composer** | 2.x | |
| **Node.js** | 20+ | Untuk build aset Vite |
| **Basis data** | PostgreSQL 16 (produksi) / SQLite (pengembangan) | |

**Ekstensi PHP wajib:**

```
fileinfo  gd  mbstring  pdo  pdo_pgsql  zip
```

> `ext-gd` diperlukan untuk membuat gambar pratinjau tautan (`/gambar-sosial.png`).
> Bila tidak tersedia, aplikasi otomatis memakai gambar cadangan sehingga tidak
> pernah error — tetapi logo desa tidak akan muncul di pratinjau WhatsApp.

---

## 🚀 Instalasi

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

### 1. Atur kata sandi akun admin awal

Minimal 12 karakter. Bila dikosongkan, seeder membuat kata sandi acak dan
menampilkannya **sekali** di konsol:

```env
ADMIN_SEED_PASSWORD=ganti-dengan-sandi-kuat
```

### 2. Atur identitas desa (opsional)

Nilai ini dipakai sebagai isi awal tabel `profil_desas` **dan** sebagai cadangan
bila basis data belum siap. Setelah instalasi, ubah lewat menu *Profil & Kontak*:

```env
DESA_NAMA="Desa Karang Sari"
DESA_KECAMATAN="Jati Agung"
DESA_KABUPATEN="Lampung Selatan"
DESA_PROVINSI="Lampung"
DESA_PREFIX_REFERENSI=KRS      # awalan nomor referensi -> KRS-2026-0001
DESA_KODE_SURAT=DS-KRS         # awalan kode pada pratinjau surat
```

### 3. Siapkan basis data dan aset

```bash
php artisan migrate:fresh --seed
php artisan storage:link      # agar logo desa (disk "public") dapat ditampilkan
npm install
npm run build                 # WAJIB: view memakai @vite(...)
```

> ⚠️ `npm run build` (atau `npm run dev` saat mengembangkan) **wajib**
> dijalankan. Tanpa manifest Vite, semua halaman akan melempar
> `Vite manifest not found`.

---

## ▶️ Menjalankan

```bash
php artisan serve
```

| Halaman | URL |
| --- | --- |
| Situs publik | <http://127.0.0.1:8000> |
| Dashboard admin | <http://127.0.0.1:8000/admin/login> |
| Panduan warga | <http://127.0.0.1:8000/panduan> |
| Panduan admin | <http://127.0.0.1:8000/admin/panduan> |

### Akun hasil seeder

Kata sandi mengikuti `ADMIN_SEED_PASSWORD`:

| Username | Nama | Peran |
| --- | --- | --- |
| `sari` | Sari Wulandari | Administrator |
| `rangga` | Rangga Pratama | Verifikator |
| `dewi` | Dewi Lestari | Operator Konten |

> 🔐 **Ganti kata sandi ini sebelum situs dipublikasikan.**

`DatabaseSeeder` mengisi 8 pengajuan contoh (`KRS-<tahun>-0001..0008`) dengan
status bervariasi, **hanya di lingkungan `local` dan `testing`**. Di produksi
dashboard dimulai kosong.

Ingin basis data tanpa pengajuan contoh? Jalankan seeder satu per satu:

```bash
php artisan migrate:fresh
php artisan db:seed --class=ProfilDesaSeeder
php artisan db:seed --class=JenisSuratSeeder
php artisan db:seed --class=AdminSeeder
php artisan db:seed --class=KontenWebsiteSeeder
```

Seeder sengaja **tidak** mengisi berita, potensi, statistik, atau perangkat
desa: data itu milik pemerintah desa dan diisi sendiri lewat dashboard admin.

---

## 🧪 Pengujian

```bash
cd backend
php artisan test
```

**Status saat ini: 418 tes / 2.122 assertion — semua lulus.**

### Cakupan pengujian

- Alur pengajuan warga: validasi WhatsApp, isian surat sesuai template,
  ukuran & format berkas, nomor referensi berurutan (termasuk lintas 9999)
- Revisi warga tanpa membuat pengajuan baru
- Proteksi halaman konfirmasi & cek status (tidak membocorkan NIK/alamat)
- Autentikasi admin: throttle login, akun nonaktif, pembatasan peran
- Transisi status pengajuan beserta batasan tiap peran
- Verifikasi dokumen per berkas
- CRUD konten desa: berita, potensi, statistik, perangkat
- Sorotan berita pada halaman Informasi & panduan admin
- Akses dokumen privat (hanya lewat route admin ber-autentikasi)
- Header keamanan, zona waktu WIB, ketahanan formulir terhadap masukan array
- Seluruh halaman publik tetap dapat dirender meski belum ada data konten

### Menguji dengan PostgreSQL

Produksi memakai PostgreSQL, sedangkan `phpunit.xml` memakai SQLite in-memory.
Perbedaan perilaku keduanya menyembunyikan beberapa bug nyata (tanggal tidak
sah, `LIKE` case-sensitive, `bigint` menolak string kosong). Sebelum deploy:

```bash
DB_PORT=5432 DB_DATABASE=karangsari_test DB_USERNAME=postgres DB_PASSWORD=... \
  php artisan test --configuration=phpunit-pgsql.xml
```

> ⚠️ `RefreshDatabase` **mengosongkan** basis data yang dipakai. Arahkan ke
> basis data khusus pengujian — **jangan** ke basis data produksi.

---

## 📬 Alur Pengajuan Surat

```
1. Warga pilih layanan
   └─ isi data pemohon (nama, alamat, keperluan, WhatsApp)
   └─ isian surat otomatis diturunkan dari template resmi ({{penanda}})

2. Warga menerima
   ├─ nomor referensi  → KRS-2026-0001   (untuk komunikasi dengan petugas)
   └─ kode akses acak  → K7QM3FZP        (kunci pelacakan sebenarnya)

3. Warga memantau lewat /cek-status
   └─ butuh nomor referensi + kode akses
   └─ lupa kode? pulihkan di /cek-status/lupa-kode
      (nomor referensi + tanggal lahir, maks. 8 percobaan / 10 menit)

4. Petugas verifikasi
   ├─ setujui / tolak per berkas
   └─ minta perbaikan → warga revisi di /pengajuan/revisi/{nomorReferensi}?kode=...
      (nomor referensi tetap, penghitung revisi_ke bertambah)

5. Surat jadi
   └─ diunduh petugas sebagai .docx dari kerangka resmi desa
```

**Mengapa nomor referensi tidak dipakai sebagai kunci?**
Nomor referensi berurutan dan mudah ditebak (`KRS-2026-0001`), sehingga **bukan**
token. Kunci sebenarnya adalah kode akses acak 8 karakter (karakter `2-9`,
`A-Z` tanpa `I`/`O`). Kode salah atau kosong menghasilkan `404`.

---

## 🔒 Keamanan

### Dokumen warga bersifat privat

KTP dan KK tidak lagi menjadi persyaratan bawaan (data kependudukan sudah ada
di desa), tetapi mekanisme unggah tetap tersedia untuk persyaratan tambahan
seperti surat pengantar RT/RW.

- Berkas disimpan di disk privat (`local` → `storage/app/private`, atau bucket
  privat Supabase Storage bila `DOKUMEN_DISK=s3`)
- **Tidak punya URL publik**
- Unduhan hanya lewat route `admin.pengajuan.dokumen` yang mewajibkan login
  dan peran Administrator/Verifikator

### Hal yang sudah aktif

| Perlindungan | Keterangan |
| --- | --- |
| **CSRF** | Semua form |
| **Rate limiting** | Per-route: login, kirim pengajuan, revisi, cek status |
| **Content-Security-Policy** | `default-src 'self'`, frame & object dikunci |
| **X-Frame-Options** | `DENY` (anti-clickjacking) |
| **X-Content-Type-Options** | `nosniff` |
| **Referrer-Policy** | `strict-origin-when-cross-origin` |
| **Permissions-Policy** | Geolocation, microphone, camera dimatikan |
| **HSTS** | Aktif otomatis saat HTTPS |
| **Trusted Proxies** | Lewat `TRUSTED_PROXIES` |
| **Sanitasi konten** | `TeksBaca` membuang tag berbahaya, `onclick=`, `javascript:`, atribut `style` |
| **Kode akses acak** | Disimpan sebagai kolom tersembunyi (tidak ikut JSON) |

### Hal yang wajib diperhatikan saat produksi

- ✅ `APP_DEBUG=false` dan `APP_ENV=production`
- ✅ `APP_KEY` baru hasil `php artisan key:generate`
- ✅ **Ganti kata sandi admin** sebelum situs dipublikasikan
- ✅ `.env` **tidak pernah** masuk repositori
- ⚠️ **Jangan** mengaktifkan opsi `serve` pada disk privat di
  `backend/config/filesystems.php`. Opsi itu sengaja dimatikan; mengaktifkannya
  kembali membuka `/storage/{path}` untuk publik dan mengekspos dokumen warga
  tanpa autentikasi.

---

## ☁️ Deploy ke Railway

Aplikasi ini **tidak bisa** dijalankan di GitHub Pages karena membutuhkan PHP
dan basis data di sisi server. Target deploy utama adalah **Railway**.

### Langkah deploy

1. **Buat service** dari repositori ini.
   - **Root Directory: `backend`**
   - `railway.json` di root repositori menentukan `buildCommand` & `startCommand`

2. **Tambahkan plugin PostgreSQL**, lalu set:
   ```env
   DB_CONNECTION=pgsql
   DB_URL=${{Postgres.DATABASE_URL}}
   ```
   > Satu variabel `DB_URL` sudah memuat host, port, nama basis data, user, dan sandi.

3. **Isi variabel lingkungan:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=base64:...            # php artisan key:generate --show
   APP_URL=https://domain-anda
   TRUSTED_PROXIES=*
   SESSION_SECURE_COOKIE=true
   APP_TIMEZONE=Asia/Jakarta
   ```

4. **Pasang volume** pada `storage/` agar dokumen warga tidak hilang setiap
   deploy, **atau** set `DOKUMEN_DISK=s3` dengan kredensial S3/Supabase.

5. **Setelah deploy pertama**, jalankan sekali lewat tab Shell:
   ```bash
   php artisan db:seed --force
   ```
   untuk mengisi profil desa, jenis surat, dan akun admin.

### Cara kerja skrip deploy

| Berkas | Fungsi |
| --- | --- |
| `railway.json` | Menentukan `buildCommand`, `startCommand`, dan healthcheck `/up` |
| `backend/build.sh` | `composer install --no-dev` + build aset Vite bila manifest belum ada |
| `backend/deploy-start.sh` | Menunggu basis data siap → migrate → `storage:link` → optimize → serve |

`deploy-start.sh` sengaja **tidak** memakai `optimize:clear`, karena perintah itu
menjalankan `cache:clear` yang menghapus tabel `cache` — dan pada deployment
pertama tabel itu belum ada, sehingga seluruh proses start gagal.

### Alternatif hosting

- VPS dengan Nginx + PHP-FPM + PostgreSQL
- Shared hosting PHP + MySQL (paling ekonomis, perhatikan batas unggah)
- Platform terkelola seperti Laravel Cloud

> **Supabase** dapat dipakai sebagai **database Postgres + Storage** (bucket
> privat), tetapi Supabase **tidak menjalankan PHP**. Backend Laravel tetap harus
> di-host di layanan PHP. Konfigurasi contoh ada di
> `backend/.env.production.example`.

---

## 📂 Struktur Repositori

```
.
├── index.html, *.html           prototipe desain (acuan, jangan diubah)
├── css/, js/, admin/            aset prototipe (acuan, jangan diubah)
└── backend/                     aplikasi Laravel 13
    ├── app/
    │   ├── Enums/               RoleAdmin, StatusPengajuan
    │   ├── Http/
    │   │   ├── Controllers/     controller publik
    │   │   │   └── Admin/       controller dashboard
    │   │   ├── Middleware/      EnsureAdmin (alias "admin"), SecurityHeaders
    │   │   └── Requests/        validasi formulir
    │   ├── Models/              ProfilDesa, JenisSurat, Berita, PotensiDesa, ...
    │   ├── Services/            PengajuanService, VerifikasiService, SuratWordService
    │   └── Support/             SiteProfile, TeksBaca, BerkasPublik, TemplateSuratDesa
    ├── config/desa.php          identitas desa & awalan nomor referensi
    ├── database/
    │   ├── migrations/          22 migrasi
    │   └── seeders/             profil, jenis surat, admin, konten
    ├── resources/
    │   ├── views/               47 tampilan Blade (publik & admin)
    │   ├── css/                 style.css, lapisan-interaksi.css
    │   └── surat/               kerangka .docx resmi (7 jenis surat)
    ├── routes/web.php           79 route publik & admin
    ├── tests/                   42 berkas pengujian
    ├── build.sh                 skrip build Railway
    └── deploy-start.sh          skrip start Railway
```

### Nomor referensi

Format: `<awalan>-<tahun>-<urut>`, awalan dari `config('desa.prefix_referensi')`
(bawaan `KRS`, dapat diubah lewat `DESA_PREFIX_REFERENSI`). Nomor urut otomatis
melanjutkan ke 5 digit setelah 9999, dan pembuatannya tahan terhadap pengajuan
bersamaan.

---

## 🔧 Pemecahan Masalah

<details>
<summary><b>"Vite manifest not found" saat membuka halaman</b></summary>

Aset frontend belum dibangun. Jalankan:

```bash
cd backend && npm install && npm run build
```
</details>

<details>
<summary><b>Logo desa / foto perangkat tidak tampil</b></summary>

Symlink storage belum dibuat:

```bash
php artisan storage:link
```

Di Railway, symlink hilang setiap container diganti. `deploy-start.sh` sudah
membuatnya ulang otomatis.
</details>

<details>
<summary><b>Pratinjau tautan di WhatsApp hanya ikon abu-abu</b></summary>

Periksa apakah gambar pratinjau dapat diakses:

```
https://domain-anda/gambar-sosial.png
```

Harus membalas `200` dengan `Content-Type: image/png`. Bila `500`, ekstensi
**GD** belum terpasang di server. WhatsApp juga menyimpan cache pratinjau —
setelah diperbaiki, kirim ulang tautannya dari chat/nomor lain.
</details>

<details>
<summary><b>Deploy gagal: "relation cache does not exist"</b></summary>

Jangan memakai `php artisan optimize:clear` pada deployment pertama. Perintah
itu menjalankan `cache:clear` yang menghapus isi tabel `cache`, padahal tabel
tersebut baru dibuat oleh `migrate`. `deploy-start.sh` sudah menghindari ini
dengan memakai `config:clear`, `route:clear`, `view:clear`, dan
`clear-compiled` saja.
</details>

<details>
<summary><b>Container mati berulang tanpa penjelasan</b></summary>

Periksa log start. Penyebab paling umum:

- `APP_KEY` belum diisi
- `DB_URL` kosong atau masih berupa teks `${{...}}` yang tidak ter-resolve
- Volume dipasang pada `storage/` sehingga folder framework hilang
  (sudah ditangani `deploy-start.sh` yang membuat ulang folder di awal)
</details>

<details>
<summary><b>Tes gagal dengan "database is locked" (SQLite)</b></summary>

Jalankan ulang. Bila terus terjadi, gunakan PostgreSQL untuk pengujian:

```bash
DB_PORT=5432 DB_DATABASE=karangsari_test DB_USERNAME=postgres \
  php artisan test --configuration=phpunit-pgsql.xml
```
</details>

---

## 📄 Catatan tentang Dokumen Internal Desa

Folder `docs/` berisi dokumen Word asli dari perangkat desa (profil desa,
7 template surat resmi, kop surat) **tidak disertakan** di repositori ini karena
merupakan dokumen internal pemerintah desa.

Aplikasi tetap berjalan penuh tanpanya:

- Teks surat resmi sudah tersalin ke `App\Support\TemplateSuratDesa`
- Kerangka cetak `.docx` ada di `backend/resources/surat/`
- Kop surat sudah menjadi gambar di `backend/public/images/kop-surat.png`

Nama berkas asli hanya disimpan sebagai keterangan asal-usul di kolom `sumber`.

---

## 📜 Lisensi

Dikembangkan untuk keperluan **Desa Karang Sari**, Kecamatan Jati Agung,
Kabupaten Lampung Selatan.

<div align="center">

---

**Pemerintah Desa Karang Sari**

</div>
