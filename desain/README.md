# Beranda Handoff Desain

Dokumen ini adalah hasil pembacaan **langsung** atas ekspor PNG Figma di
folder `ekspor/` (25 berkas, 2940px lebar = frame 1470px @2x). Isinya bukan
tebakan: setiap klaim di sini bisa diverifikasi dengan membuka gambarnya.

**Sumber:** [UI UI Web Desa (editable)](https://www.figma.com/design/Apvdrem9fQ0YdfjKzqOAMA/UI-UI-Web-Desa--editable-?node-id=0-1)
**Versi:** `v1.0` · **Tanggal dibaca:** 2026 · **Status:** ekspor terbaca lengkap

---

## Konteks: ini desain awal, bukan acuan final

> **Penting dibaca lebih dulu.**
>
> Ekspor ini adalah **desain awal** sebelum aplikasi Laravel dikembangkan.
> Implementasi di `backend/` sudah melalui banyak perbaikan setelahnya, sehingga
> **perbedaan antara desain dan aplikasi adalah hal yang wajar dan disengaja** —
> bukan kesalahan yang harus diperbaiki.
>
> Dokumen ini **tidak** dimaksudkan sebagai daftar bug desain. Tujuannya dua:
> 1. Merekam rupa desain awal sebagai dokumentasi sejarah proyek
> 2. Memberi tahu developer bagian mana yang **masih relevan** untuk dijadikan
>    acuan tampilan, dan bagian mana yang **sudah tidak berlaku**
>
> Semua keputusan akhir ada pada tim. Kalau ragu, tanyakan ke pemilik desain
> sebelum mengubah apa pun.

---

## Perbedaan yang sudah diketahui

Lima hal berikut sengaja dicatat supaya orang yang membaca repo tidak bingung
saat menemukan desain yang berbeda dari aplikasi. **Tidak ada yang perlu
ditindaklanjuti kecuali tim memutuskan sebaliknya.**

### 1. Desain memakai identitas "Desa Sukamaju" — bukan Karang Sari

Seluruh 25 halaman memakai nama **Desa Sukamaju, Kecamatan Cisaruа, Kabupaten
Bogor, Jawa Barat** — sisa dari template awal sebelum aplikasi dikembangkan.

| Elemen | Nilai di desain | Nilai di aplikasi sekarang |
| --- | --- | --- |
| Nama desa | Desa Sukamaju | Desa Karang Sari |
| Kecamatan | Cisaruа | Jati Agung |
| Kabupaten | Bogor | Lampung Selatan |
| Provinsi | Jawa Barat | Lampung |
| Alamat | Jl. Raya Puncak No. 24 | dari data desa |
| Telepon | (0251) 830 1122 | dari data desa |
| WhatsApp | 0812-3456-7890 | dari data desa |
| Email | pemdes@sukamaju.desa.id | dari data desa |
| Awalan referensi | `SBM-2026-0009` | `KRS-<tahun>-<urut>` |

Hal yang sama terjadi pada `backend/resources/css/style.css` baris 2, yang
masih berjudul *"Desa Sukamaju - Design System"*.

**Kabar baiknya:** aplikasi Laravel sudah mengambil nama desa, kecamatan,
kabupaten, provinsi, telepon, WhatsApp, email, dan alamat dari tabel
`profil_desas` (lihat `backend/database/migrations/2026_01_01_000002_create_profil_desas_table.php`).
Jadi **tidak perlu ada perubahan kode** — identitas sudah benar di aplikasi,
cukup diatur lewat menu *Profil & Kontak* di dashboard.

Identitas "Sukamaju" di mockup dibiarkan sebagai penanda bahwa berkas Figma ini
adalah desain awal. Bila suatu saat mockup dipakai lagi untuk presentasi,
ganti dulu nama desanya.

### 2. Beberapa elemen desain tidak punya kolom di basis data

Beberapa hal di mockup menggambarkan fitur yang belum tentu didukung skema
sekarang. Perlu dicek bila mau dipakai lagi sebagai acuan.

| Elemen desain | Terlihat di | Kondisi basis data |
| --- | --- | --- |
| **Kode Pos** terpisah | `23-profil-kontak.png` | **Sudah ada** — kolom `kode_pos` |
| **Email desa** | `23-profil-kontak.png`, footer | **Sudah ada** — kolom `email` |
| Ikon WhatsApp berwarna khusus | `10-kontak.png` (kartu kontak) | Tidak relevan (presentasi) |
| Logo desa & foto beranda | `23-profil-kontak.png` | **Sudah ada** — `logo`, `foto_beranda` |
| Jam kunjungan terpisah dari jam layanan | `23-profil-kontak.png` | **Belum jelas** — perlu dicek `jam_kunjungan` |
| Status "Siap Diambil" sebagai status tersendiri | `12-ringkasan.png`, `14-pengajuan.png` | Perlu dicocokkan dengan `App\Enums\StatusPengajuan` |

### 3. Alur "cek status" di desain berbeda dengan aplikasi

**Ini perbedaan yang paling perlu dipahami** sebelum siapa pun mengubah tampilan
berdasarkan desain ini.

Di `09-pengajuan-berhasil.png`, halaman konfirmasi hanya menampilkan **nomor
referensi** (`SBM-2026-0009`) dan tombol "Salin nomor referensi" — **tanpa kode
akses**. Lalu ada keterangan:

> "Website ini tidak menyediakan pelacakan status untuk publik. Admin akan
> menghubungi Anda melalui WhatsApp ketika ada perbaikan yang diperlukan atau
> ketika surat sudah siap diambil."

Setelah desain ini dibuat, aplikasi **berkembang** menjadi sistem **nomor
referensi + kode akses acak 8 karakter**. Alasannya dijelaskan di `README.md`
utama: nomor referensi berurutan dan mudah ditebak (`KRS-2026-0001`), sehingga
tidak boleh dipakai sebagai kunci pelacakan.

**Status: aplikasi yang benar, desain yang tertinggal.** Tidak ada yang perlu
diperbaiki di kode. Yang perlu dijaga: kalau nanti halaman ini didesain ulang,
**jangan kembali ke versi tanpa kode akses**. Lihat
`pages/cek-status.blade.php` dan bagian "Alur Pengajuan Surat" di `README.md`
utama sebagai acuan yang berlaku.

### 4. Jenis surat: desain 6, aplikasi 7

Desain (`06-pelayanan.png`, `16-jenis-surat.png`) menampilkan **6 layanan** —
Domisili (470), Usaha (471), Tidak Mampu (472), Pengantar KTP (473), Kematian
(474), Izin Keramaian (475). Aplikasi sekarang punya **7 jenis surat**
(lihat `backend/resources/surat/`).

Ini karena satu jenis surat ditambahkan setelah desain dibuat. Aplikasi yang
berlaku; desain hanya belum memuat yang ketujuh.

### 5. Data contoh di mockup memakai NIK berformat valid

`15-pengajuan-detail.png` menampilkan NIK `3201121508870003` dan nama pemohon
"Budi Santoso". Format NIK-nya valid (16 digit, awalan kode wilayah Jawa Barat),
meski datanya karangan.

Tidak ada masalah selama repo ini publik dan datanya fiktif. Catatan ini hanya
pengingat: untuk mockup baru yang di-commit ke repo publik, lebih aman memakai
`3201xxxxxxxxxxxx` atau `0000000000000000`.

---

## Inventaris 25 halaman

Nomor urut mengikuti nama berkas di `ekspor/`.

### Halaman publik (`ekspor/publik/`)

| # | Berkas | Halaman | View Blade | Catatan |
| --- | --- | --- | --- | --- |
| 01 | `01-beranda.png` | Beranda | `pages/beranda.blade.php` | 10 section vertikal, 12110px @2x |
| 02 | `02-profil.png` | Profil desa | `pages/profil.blade.php` | Tab: Sejarah, Visi & Misi, Struktur, Wilayah |
| 03 | `03-informasi.png` | Informasi | `pages/informasi.blade.php` | Berita + Pengumuman + Agenda |
| 04 | `04-informasi-detail.png` | Detail berita | `pages/informasi-detail.blade.php` | + sidebar "Berita lainnya" |
| 05 | `05-potensi.png` | Potensi desa | `pages/potensi.blade.php` | Filter: Semua/Pertanian/Wisata/UMKM |
| 06 | `06-pelayanan.png` | Katalog layanan | `pages/pelayanan/index.blade.php` | 6 kartu, ada kotak pencarian |
| 07 | `07-pelayanan-detail.png` | Detail layanan | `pages/pelayanan/show.blade.php` | Kode layanan 470 |
| 08 | `08-pengajuan.png` | Formulir pengajuan | `pages/pengajuan/create.blade.php` | 3 langkah bernomor + sidebar ringkasan |
| 09 | `09-pengajuan-berhasil.png` | Pengajuan berhasil | `pages/pengajuan/berhasil.blade.php` | **Tanpa kode akses** — lihat temuan #3 |
| 10 | `10-kontak.png` | Kontak | `pages/kontak.blade.php` | 3 kartu info + peta + FAQ akordeon |

### Dashboard admin (`ekspor/admin/`)

| # | Berkas | Halaman | View Blade | Catatan |
| --- | --- | --- | --- | --- |
| 11 | `11-login.png` | Login | `admin/login.blade.php` | Split-screen navy / putih |
| 12 | `12-ringkasan.png` | Ringkasan | `admin/ringkasan.blade.php` | 4 kartu statistik + tabel + sebaran status |
| 13 | `13-ringkasan-menu-akun.png` | Ringkasan (dropdown akun terbuka) | `admin/ringkasan.blade.php` | State tambahan, bukan halaman baru |
| 14 | `14-pengajuan.png` | Daftar pengajuan | `admin/pengajuan/index.blade.php` | 8 pil filter status |
| 15 | `15-pengajuan-detail.png` | Detail pengajuan | `admin/pengajuan/show.blade.php` | Data pemohon + dokumen + preview surat |
| 16 | `16-jenis-surat.png` | Jenis surat | `admin/jenis-surat/index.blade.php` | Tabel + toggle aktif/nonaktif |
| 17 | `17-jenis-surat-tab-formulir.png` | Modal — tab Field formulir | `admin/jenis-surat/form.blade.php` | |
| 18 | `18-jenis-surat-tab-persyaratan.png` | Modal — tab Persyaratan | `admin/jenis-surat/form.blade.php` | |
| 19 | `19-jenis-surat-baru.png` | Modal — jenis surat baru | `admin/jenis-surat/form.blade.php` | State kosong |
| 20 | `20-konten.png` | Konten website | `admin/konten/index.blade.php` | 19 terbit, 4 jenis konten |
| 21 | `21-konten-edit.png` | Modal edit konten | `admin/konten/edit.blade.php` | |
| 22 | `22-konten-baru.png` | Modal tulis konten | `admin/konten/edit.blade.php` | Status default `Draf` |
| 23 | `23-profil-kontak.png` | Profil & Kontak | `admin/profil/edit.blade.php` | 3 panel + sidebar pratinjau |
| 24 | `24-akun-admin.png` | Akun admin | `admin/akun/index.blade.php` | **Lihat catatan di bawah** |
| 25 | `25-log-aktivitas.png` | Log aktivitas | `admin/log/index.blade.php` | Tabel + panel "Aktivitas yang dicatat" |

### Halaman yang belum didesain

Dari 47 view Blade, desain mencakup 22 halaman. Yang **belum ada** mockup-nya:

| Halaman | View Blade | Sudah ada di aplikasi? |
| --- | --- | --- |
| Cek status | `pages/cek-status.blade.php` | Ya — fitur inti, tapi belum pernah didesain |
| Lupa kode akses | `pages/cek-status-lupa.blade.php` | Ya |
| Revisi pengajuan | `pages/pengajuan/revisi.blade.php` | Ya |
| Panduan warga | `pages/panduan.blade.php` | Ya |
| Panduan admin | `admin/panduan.blade.php` | Ya |
| Kebijakan privasi | `pages/privasi/kebijakan.blade.php` | Ya |
| Pemberitahuan data | `pages/privasi/pemberitahuan.blade.php` | Ya |
| Kelola berita (index + form) | `admin/berita/*` | Ya |
| Kelola potensi (index + form) | `admin/potensi/*` | Ya |
| Kelola statistik (index + form) | `admin/statistik/*` | Ya |
| Kelola perangkat (index + form) | `admin/perangkat/*` | Ya |
| Jenis surat — halaman edit penuh | `admin/jenis-surat/edit.blade.php` | Ya |
| Halaman galat 429 | `errors/429.blade.php` | Ya |

---

## Pola desain yang konsisten di semua halaman

Ini yang perlu dijaga saat implementasi, karena berlaku menyeluruh.

### Struktur halaman publik

```
1. Skip link "Lewati ke konten utama"     (biru, hanya tampil saat fokus keyboard)
2. Topbar navy tipis                      lokasi kiri, jam layanan + telepon kanan
3. Header putih                           logo + nama desa + nav + tombol "Ajukan Surat"
4. Hero                                   judul besar kiri, deskripsi kanan
5. Breadcrumb                             Beranda / <Halaman>
6. Konten
7. Footer navy 4 kolom                    identitas, Jelajahi, Pelayanan, Kantor Desa
8. Baris bawah footer                     hak cipta + tautan privasi + Masuk Admin
```

**Temuan aksesibilitas:** skip link di desain tampil sebagai bar biru penuh di
paling atas. Di aplikasi sekarang, elemen ini biasanya disembunyikan sampai
mendapat fokus keyboard. Perlu dipastikan desain tidak membuatnya selalu
terlihat.

### Tata letak dua kolom asimetris

Berulang di `02-profil`, `03-informasi`, `06-pelayanan`, `07-pelayanan-detail`:

```
[ Judul besar kiri  ~55% ]  [ Deskripsi penjelas kanan ~40% ]
```

Jarang dipakai di aplikasi saat ini yang cenderung satu kolom. Ini **perubahan
pola yang cukup besar**, perlu dicek apakah `style.css` sudah punya kelasnya.

### Label kecil huruf kapital (eyebrow)

Semua section diawali label kecil kapital dengan garis pendek di kiri, misal
`PELAYANAN ADMINISTRASI`, `ALUR PENGAJUAN`, `POTENSI DESA`. Ini sudah ada di
`style.css` sebagai `.eyebrow` (baris 96) — **cocok**, tidak perlu kelas baru.

### Tombol aksi tunggal yang tegas

Setiap halaman punya **satu** tombol utama navy: "Ajukan Surat" (header),
"Isi formulir pengajuan" (detail), "Simpan perubahan" (admin). Tombol sekunder
memakai outline. Konsisten.

---

## Perubahan yang berdampak pada CSS

Dipetakan ke kelas yang sudah ada di `backend/resources/css/style.css`:

| Yang didesain | Kelas yang ada | Status |
| --- | --- | --- |
| Eyebrow kapital + garis | `.eyebrow` (baris 96) | **Cocok** |
| Tombol navy / outline / hijau | `.btn-primary`, `.btn-outline`, `.btn-green` | **Cocok** |
| Badge status warna | `.badge-blue|green|amber|red|gray` | **Cocok** |
| Pil filter dengan jumlah | `.status-pill` (baris 1391) | Perlu cek ukuran |
| Chip kecil (kode layanan 470) | `.chip` (baris 1615) | **Cocok** |
| Kartu | `.card` (baris 653) | **Cocok** |
| Tabel bisa digulir | `.table-wrap` (baris 1129) | **Cocok** |
| Field bantuan form | `.field-help` (baris 1069) | **Cocok** |
| Dropdown kustom | `pilih-kustom.css` | **Cocok** |
| Sidebar admin navy | `.admin-sidebar` | **Cocok** |

**Kesimpulan: fondasi CSS sudah sangat dekat dengan desain.** Yang berubah
terutama susunan halaman (grid dua kolom asimetris), bukan sistem warnanya.
Ini kabar bagus — pekerjaan implementasi lebih kecil dari perkiraan.

---

## Rekomendasi pemakaian

Dokumen ini paling berguna sebagai **rujukan tampilan**, bukan spesifikasi wajib.
Kalau nanti ada penyesuaian UI, bagian yang masih bisa diikuti:

1. **Pola visual** — palet warna, radius, bayangan, tips huruf. Sudah cocok
   dengan `style.css` (lihat `komponen/README.md`).
2. **Susunan halaman publik** — hero dua kolom asimetris, eyebrow kapital,
   section berlatar bergantian. Belum sepenuhnya ada di aplikasi.
3. **Komponen yang belum dibuat** — stepper, toggle, akordeon FAQ, bar progres,
   kotak tanggal. Enam kelas CSS baru.

Bagian yang **jangan** diikuti apa adanya karena sudah tertinggal dari aplikasi:
identitas desa, halaman pengajuan berhasil (kode akses), jumlah jenis surat, dan
halaman akun admin (aplikasi sudah multi-akun berbasis peran).

Urutan pengerjaan bila ingin menyelaraskan tampilan:

1. Putuskan halaman mana yang mau diselaraskan lebih dulu
2. Implementasi CSS/Blade per halaman, satu PR per halaman atau kelompok halaman
3. Jalankan `cd backend && php artisan test` — baseline **418 tes lulus**

---

## Berkas terkait

- [`../figma/frame-figma.md`](../figma/frame-figma.md) — tabel pemetaan
  frame → view (kolom Node ID masih perlu diisi dari Figma)
- [`../tokens/tokens.json`](../tokens/tokens.json) — token warna dan tipografi
- [`../catatan/perubahan-desain.md`](../catatan/perubahan-desain.md) — log perubahan
- [`../catatan/checklist-handoff.md`](../catatan/checklist-handoff.md) — checklist
