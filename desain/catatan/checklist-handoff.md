# Checklist Handoff Desain

Status di sini sudah diisi berdasarkan **pembacaan langsung** 25 berkas PNG di
`../ekspor/`. Centang sisa baris yang masih terbuka sebelum desain dikerjakan
developer.

---

## 1. Berkas Figma

- [x] Ekspor PNG sudah masuk repositori (25 berkas, 2940px lebar)
- [ ] Berkas Figma bisa dibuka oleh minimal satu developer
- [ ] Nama frame sudah diberi versi, contoh `Beranda v1.0`
- [ ] Layer sudah dinamai rapi (bukan `Frame 12`)
- [ ] Warna & teks memakai *Styles* / *Variables* Figma
- [ ] Komponen berulang sudah dijadikan *Component* / *Variant*

## 2. Ekspor gambar

- [x] Semua halaman yang didesain punya berkas ekspor
- [x] Nama berkas sudah seragam (`01-beranda.png` … `25-log-aktivitas.png`)
- [x] Resolusi 2x (2940px untuk frame 1470px)
- [ ] **Versi mobile** — belum ada satu pun ekspor lebar ponsel (360–430px)
- [x] Tidak ada data warga asli *(catatan: NIK contoh berformat valid, lihat #5)*
- [x] Berkas ZIP mentah sudah dihapus dari folder ekspor

## 3. Token desain

- [x] `tokens/tokens.json` berisi warna, tipografi, radius, shadow
- [ ] Nilai token sudah **diverifikasi** dengan panel Styles Figma
      *(saat ini berasal dari `style.css`, bukan dari Figma)*
- [x] Warna status konsisten dengan makna status pengajuan
- [ ] Ukuran font mobile sudah didefinisikan

## 4. Perbedaan desain vs aplikasi

Desain ini adalah **desain awal**; aplikasi sudah berkembang setelahnya.
Perbedaan berikut sudah diketahui dan **tidak perlu ditindaklanjuti** — dicatat
hanya agar tidak membingungkan. Rincian di `../README.md`.

- [x] Identitas "Desa Sukamaju / Bogor" — wajar, desain dibuat sebelum migrasi
- [x] Halaman berhasil tanpa kode akses — aplikasi sudah lebih baru & lebih aman
- [x] Jenis surat 6 vs 7 — satu surat ditambahkan setelah desain dibuat
- [x] Akun admin 1 vs multi-akun — aplikasi sudah berbasis peran

Bila tim memutuskan ingin **menyelaraskan tampilan** dengan desain, baru cek:

- [ ] Bagian mana yang mau diselaraskan (lihat `../README.md` bagian Rekomendasi)
- [ ] Konfirmasi ulang ke pemilik desain sebelum mengubah apa pun

## 5. Kelengkapan state

- [ ] Kosong (belum ada data) — hanya `22-konten-baru.png` yang menunjukkan state kosong
- [ ] Memuat (*loading*) — belum ada
- [ ] Galat validasi form — belum ada
- [x] Sukses / berhasil — ada di `09-pengajuan-berhasil.png`
- [ ] Hover, fokus, nonaktif tombol — belum ada
- [ ] Halaman galat `404`, `419`, `429`, `500` — belum ada

## 6. Sebelum merge ke `main`

- [x] Tidak menimpa prototipe statis di akar repositori
- [ ] `cd backend && php artisan test` masih lulus (baseline **418 tes**) —
      belum relevan, belum ada perubahan kode
- [ ] Dicek di lebar 360px, 768px, 1280px

---

## Status per halaman

`final` = desain tahap awal, siap dijadikan rujukan · `belum` = belum didesain

| Halaman | Desain | Ekspor | State lengkap | Catatan |
| --- | --- | --- | --- | --- |
| Beranda | `final` | ✅ | ✗ | 10 section, perlu grid 2 kolom |
| Profil desa | `final` | ✅ | ✗ | Tab + bagan struktur |
| Informasi | `final` | ✅ | ✗ | 3 blok: berita, pengumuman, agenda |
| Detail informasi | `final` | ✅ | ✗ | Ada sidebar berita lain |
| Potensi desa | `final` | ✅ | ✗ | Filter 4 kategori |
| Katalog layanan | `final` | ✅ | ✗ | 6 layout kartu (app sudah 7 surat) |
| Detail layanan | `final` | ✅ | ✗ | |
| Formulir pengajuan | `final` | ✅ | ✗ | Stepper 3 langkah |
| Pengajuan berhasil | `final` | ✅ | ✗ | Versi lama, app sudah + kode akses |
| Kontak | `final` | ✅ | ✗ | FAQ akordeon |
| Cek status | `belum` | ✗ | ✗ | Sudah ada di app, belum didesain |
| Lupa kode akses | `belum` | ✗ | ✗ | Sudah ada di app, belum didesain |
| Revisi pengajuan | `belum` | ✗ | ✗ | Sudah ada di app, belum didesain |
| Panduan warga | `belum` | ✗ | ✗ | |
| Kebijakan privasi | `belum` | ✗ | ✗ | |
| Pemberitahuan data | `belum` | ✗ | ✗ | |
| Login admin | `final` | ✅ | ✗ | Split-screen |
| Ringkasan admin | `final` | ✅ | ✗ | + state dropdown akun |
| Daftar pengajuan | `final` | ✅ | ✗ | 8 pil filter |
| Detail pengajuan | `final` | ✅ | ✗ | NIK contoh fiktif |
| Jenis surat | `final` | ✅ | ✗ | 6 layout (app sudah 7 surat) |
| Jenis surat — modal | `final` | ✅ | ✗ | 2 tab + state baru |
| Konten website | `final` | ✅ | ✗ | Modal edit & baru |
| Profil & Kontak | `final` | ✅ | ✗ | Termasuk kode pos & email |
| Akun admin | `final` | ✅ | ✗ | Versi lama, app sudah multi-akun |
| Log aktivitas | `final` | ✅ | ✗ | |
| Kelola berita | `belum` | ✗ | ✗ | |
| Kelola potensi | `belum` | ✗ | ✗ | |
| Kelola statistik | `belum` | ✗ | ✗ | |
| Kelola perangkat | `belum` | ✗ | ✗ | |
| Panduan admin | `belum` | ✗ | ✗ | |
