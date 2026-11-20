# Catatan Perubahan Desain

Berkas ini mencatat **apa yang berubah** dibanding prototipe statis lama
(`index.html`, `admin/*.html` di akar repositori) dan dibanding versi handoff
sebelumnya.

---

## v1.0 — Handoff pertama (ekspor PNG)

**Frame Figma:** [UI UI Web Desa (editable)](https://www.figma.com/design/Apvdrem9fQ0YdfjKzqOAMA/UI-UI-Web-Desa--editable-?node-id=0-1)
**Halaman terdampak:** 22 halaman (10 publik + 12 admin/state)
**Sumber pembacaan:** 25 berkas PNG di `../ekspor/`, lebar 2940px (frame 1470px @2x)

### Isi handoff

- 25 berkas ekspor sudah dinamai ulang sesuai nama view
  (`01-beranda.png` … `25-log-aktivitas.png`)
- Pemetaan 22 halaman ke view Blade di `../figma/frame-figma.md`
- Inventaris lengkap di `../README.md`, termasuk 5 temuan yang harus diputuskan

### Perubahan dari prototipe lama

**Tata letak**
- Halaman publik memakai pola **dua kolom asimetris**: judul besar di kiri
  (~55%), deskripsi penjelas di kanan (~40%). Prototipe lama masih satu kolom
  rata kiri. Ini perubahan pola yang paling terasa.
- Section diberi latar bergantian putih / `#F5F7FA` untuk memisahkan blok
  panjang (terlihat jelas di `01-beranda.png`).
- Beranda diperpanjang menjadi **10 section** tersusun: hero → 4 kartu
  kepercayaan → profil ringkas + statistik → katalog layanan (daftar baris) →
  alur 4 langkah → kabar (berita besar + 4 berita samping) → potensi 3 kartu →
  pengumuman + agenda 2 kolom → kontak + peta → footer.

**Komponen baru yang belum ada di prototipe**
- **Stepper bernomor** untuk alur formulir pengajuan (`08-pengajuan.png`:
  langkah 1 Data pemohon, 2 Dokumen persyaratan, 3 Persetujuan data)
- **Bar progres sebaran status** di ringkasan admin (`12-ringkasan.png`)
- **Toggle switch** aktif/nonaktif pada tabel jenis surat (`16-jenis-surat.png`)
- **Akordeon FAQ** di halaman kontak (`10-kontak.png`)
- **Kotak tanggal kalender** (`08`, `12`, `16`) untuk agenda
- **Skip link** "Lewati ke konten utama" di semua halaman publik
- **Pil filter berjumlah** pada daftar pengajuan admin (`14-pengajuan.png`)

**Halaman admin yang berubah pendekatan**
- **Jenis surat**: dari halaman terpisah menjadi **satu halaman tabel + modal**.
  Konfigurasi field formulir dan persyaratan jadi **tab di dalam modal**
  (`17` dan `18`), bukan halaman `konfigurasi-jenis-surat.html` sendiri.
- **Konten website**: tulis/edit konten jadi **modal**, bukan halaman penuh
  (`21`, `22`).
- **Akun admin**: disederhanakan menjadi **satu akun** (profil + ganti kata
  sandi + panel keamanan), bukan tabel CRUD multipengguna seperti di
  `admin/akun-admin.html` prototipe lama.

### Masalah yang ditemukan (perlu keputusan sebelum coding)

Lima perbedaan desain vs aplikasi tercatat di `../README.md`. Semuanya
**sudah diketahui dan tidak perlu ditindaklanjuti** — desain ini dibuat sebelum
aplikasi dikembangkan, jadi wajar berbeda. Dicatat semata agar pembaca repo
tidak bingung.

| # | Perbedaan | Status |
| --- | --- | --- |
| 1 | Desain memakai identitas **Desa Sukamaju / Bogor** | Wajar — desain awal; app sudah ambil dari DB |
| 2 | Beberapa field desain belum jelas kolom DB-nya | Perlu dicek bila mockup dipakai lagi |
| 3 | Halaman berhasil **belum menampilkan kode akses** | App sudah lebih baru; desain tertinggal |
| 4 | Jenis surat desain **6**, app **7** | Satu surat ditambahkan setelah desain dibuat |
| 5 | NIK contoh berformat valid di `15-pengajuan-detail.png` | Data fiktif; hanya pengingat untuk mockup baru |

### Dampak ke kode

**Belum ada perubahan kode pada handoff ini.** Ini murni dokumentasi + gambar.

Perubahan yang **akan** diperlukan setelah desain disetujui:

| Berkas | Perkiraan perubahan |
| --- | --- |
| `backend/resources/css/style.css` | Tambah kelas: grid dua kolom asimetris, stepper, toggle, akordeon, bar progres, kotak tanggal |
| `backend/resources/views/layouts/public.blade.php` | Tambah skip link, sesuaikan breadcrumb |
| `backend/resources/views/pages/beranda.blade.php` | Susun ulang 10 section |
| `backend/resources/views/pages/pelayanan/*` | Grid kartu + kotak pencarian |
| `backend/resources/views/pages/pengajuan/create.blade.php` | Stepper 3 langkah |
| `backend/resources/views/admin/jenis-surat/*` | Ubah halaman jadi tabel + modal bertab |
| `backend/resources/views/admin/konten/*` | Ubah form jadi modal |

**Yang tidak perlu diubah:** identitas desa. Semua nama, alamat, telepon, dan
email sudah dibaca dari tabel `profil_desas`, jadi cukup diisi lewat menu
*Profil & Kontak*.

**Risiko:** beberapa tes memeriksa markup halaman publik (mis. keberadaan teks
tertentu). Setelah implementasi wajib menjalankan
`cd backend && php artisan test` — baseline **418 tes lulus**.

### Catatan untuk developer

1. **Desain ini titik awal, bukan spesifikasi wajib.** Aplikasi sudah melalui
   perbaikan lanjutan. Pakai sebagai rujukan tampilan, bukan daftar pekerjaan.
2. **Jangan hardcode identitas desa** di view. Gunakan
   `App\Support\SiteProfile` atau model `ProfilDesa`.
3. Enam komponen butuh kelas CSS baru (lihat tabel terakhir
   `../figma/frame-figma.md`). Sisanya sudah tersedia — fondasi CSS sudah dekat
   dengan desain.
4. Semua berkas ekspor sudah @2x, jadi ukuran asli frame = lebar PNG ÷ 2
   (1470px untuk desktop).
5. Bila nanti halaman pengajuan berhasil didesain ulang, **pertahankan kode
   akses**. Lihat `pages/cek-status.blade.php` sebagai acuan yang berlaku.

---

## Format entri berikutnya

Salin templat ini untuk versi `v1.1` dan seterusnya. Urutkan terbaru di atas.

```
## v1.x — YYYY-MM-DD

**Diubah oleh:** nama
**Frame Figma:** tautan node
**Halaman terdampak:** ...

### Perubahan
- ...

### Dampak ke kode
- Berkas: ...
- Risiko: ...
```

## Konvensi versi

| Perubahan | Contoh |
| --- | --- |
| Perbaikan kecil (warna, jarak, teks) | `v1.1` |
| Penambahan halaman atau komponen baru | `v1.2` |
| Perombakan besar / arah desain baru | `v2.0` |

Nomor versi juga ditulis di nama frame Figma, misalnya `Beranda v1.2`.
