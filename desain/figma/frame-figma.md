# Peta Frame Figma → View Blade

**Berkas Figma:**
<https://www.figma.com/design/Apvdrem9fQ0YdfjKzqOAMA/UI-UI-Web-Desa--editable-?node-id=0-1>

Kolom `Ekspor` sudah terisi dan terverifikasi — berkasnya benar-benar ada di
folder `ekspor/`. Kolom `Node ID` masih `TODO` karena butuh diambil manual dari
Figma: klik frame → *Copy link to selection* → ambil bagian `node-id=12-345` →
tulis `12:345`.

**Status:** `final` = desain tahap awal, siap dijadikan rujukan tampilan ·
`belum` = belum didesain

> Catatan: desain ini adalah **desain awal** sebelum aplikasi Laravel
> dikembangkan. Beberapa halaman sengaja berbeda dari aplikasi sekarang karena
> sudah melalui perbaikan lanjutan. Rincian di `../README.md` bagian
> "Perbedaan yang sudah diketahui".

---

## Halaman publik

| # | Halaman | Ekspor | Node ID | Route | View Blade | Status |
| --- | --- | --- | --- | --- | --- | --- |
| 01 | Beranda | `publik/01-beranda.png` | `TODO` | `/` | `pages/beranda.blade.php` | `final` |
| 02 | Profil desa | `publik/02-profil.png` | `TODO` | `/profil` | `pages/profil.blade.php` | `final` |
| 03 | Informasi | `publik/03-informasi.png` | `TODO` | `/informasi` | `pages/informasi.blade.php` | `final` |
| 04 | Detail informasi | `publik/04-informasi-detail.png` | `TODO` | `/informasi/{slug}` | `pages/informasi-detail.blade.php` | `final` |
| 05 | Potensi desa | `publik/05-potensi.png` | `TODO` | `/potensi` | `pages/potensi.blade.php` | `final` |
| 06 | Katalog layanan | `publik/06-pelayanan.png` | `TODO` | `/pelayanan` | `pages/pelayanan/index.blade.php` | `final` |
| 07 | Detail layanan | `publik/07-pelayanan-detail.png` | `TODO` | `/pelayanan/{slug}` | `pages/pelayanan/show.blade.php` | `final` |
| 08 | Formulir pengajuan | `publik/08-pengajuan.png` | `TODO` | `/pengajuan/{slug}` | `pages/pengajuan/create.blade.php` | `final` |
| 09 | Pengajuan berhasil | `publik/09-pengajuan-berhasil.png` | `TODO` | `/pengajuan/berhasil` | `pages/pengajuan/berhasil.blade.php` | `final` |
| 10 | Kontak | `publik/10-kontak.png` | `TODO` | `/kontak` | `pages/kontak.blade.php` | `final` |
| — | Cek status | **belum ada** | `—` | `/cek-status` | `pages/cek-status.blade.php` | `belum` |
| — | Lupa kode akses | **belum ada** | `—` | `/cek-status/lupa-kode` | `pages/cek-status-lupa.blade.php` | `belum` |
| — | Revisi pengajuan | **belum ada** | `—` | `/pengajuan/revisi/...` | `pages/pengajuan/revisi.blade.php` | `belum` |
| — | Panduan warga | **belum ada** | `—` | `/panduan` | `pages/panduan.blade.php` | `belum` |
| — | Kebijakan privasi | **belum ada** | `—` | `/kebijakan-privasi` | `pages/privasi/kebijakan.blade.php` | `belum` |
| — | Pemberitahuan data | **belum ada** | `—` | `/pemberitahuan-data` | `pages/privasi/pemberitahuan.blade.php` | `belum` |

## Dashboard admin

| # | Halaman | Ekspor | Node ID | Route | View Blade | Status |
| --- | --- | --- | --- | --- | --- | --- |
| 11 | Login | `admin/11-login.png` | `TODO` | `/admin/login` | `admin/login.blade.php` | `final` |
| 12 | Ringkasan | `admin/12-ringkasan.png` | `TODO` | `/admin` | `admin/ringkasan.blade.php` | `final` |
| 13 | Ringkasan — dropdown akun | `admin/13-ringkasan-menu-akun.png` | `TODO` | `/admin` | `admin/ringkasan.blade.php` | `final` |
| 14 | Daftar pengajuan | `admin/14-pengajuan.png` | `TODO` | `/admin/pengajuan` | `admin/pengajuan/index.blade.php` | `final` |
| 15 | Detail pengajuan | `admin/15-pengajuan-detail.png` | `TODO` | `/admin/pengajuan/{id}` | `admin/pengajuan/show.blade.php` | `final` |
| 16 | Jenis surat | `admin/16-jenis-surat.png` | `TODO` | `/admin/jenis-surat` | `admin/jenis-surat/index.blade.php` | `final` |
| 17 | Jenis surat — tab Formulir | `admin/17-jenis-surat-tab-formulir.png` | `TODO` | `/admin/jenis-surat/{id}` | `admin/jenis-surat/form.blade.php` | `final` |
| 18 | Jenis surat — tab Persyaratan | `admin/18-jenis-surat-tab-persyaratan.png` | `TODO` | `/admin/jenis-surat/{id}` | `admin/jenis-surat/form.blade.php` | `final` |
| 19 | Jenis surat — baru | `admin/19-jenis-surat-baru.png` | `TODO` | `/admin/jenis-surat/create` | `admin/jenis-surat/form.blade.php` | `final` |
| 20 | Konten website | `admin/20-konten.png` | `TODO` | `/admin/konten` | `admin/konten/index.blade.php` | `final` |
| 21 | Konten — edit | `admin/21-konten-edit.png` | `TODO` | `/admin/konten/{id}/edit` | `admin/konten/edit.blade.php` | `final` |
| 22 | Konten — baru | `admin/22-konten-baru.png` | `TODO` | `/admin/konten/create` | `admin/konten/edit.blade.php` | `final` |
| 23 | Profil & Kontak | `admin/23-profil-kontak.png` | `TODO` | `/admin/profil` | `admin/profil/edit.blade.php` | `final` |
| 24 | Akun admin | `admin/24-akun-admin.png` | `TODO` | `/admin/akun` | `admin/akun/index.blade.php` | `final` |
| 25 | Log aktivitas | `admin/25-log-aktivitas.png` | `TODO` | `/admin/log` | `admin/log/index.blade.php` | `final` |
| — | Kelola berita | **belum ada** | `—` | `/admin/berita` | `admin/berita/index.blade.php` | `belum` |
| — | Form berita | **belum ada** | `—` | `/admin/berita/...` | `admin/berita/form.blade.php` | `belum` |
| — | Kelola potensi | **belum ada** | `—` | `/admin/potensi` | `admin/potensi/index.blade.php` | `belum` |
| — | Kelola statistik | **belum ada** | `—` | `/admin/statistik` | `admin/statistik/index.blade.php` | `belum` |
| — | Kelola perangkat | **belum ada** | `—` | `/admin/perangkat` | `admin/perangkat/index.blade.php` | `belum` |
| — | Panduan admin | **belum ada** | `—` | `/admin/panduan` | `admin/panduan.blade.php` | `belum` |

## Layout & partial global

Frame ini memengaruhi banyak halaman. Perubahan di sini **wajib** dicek ulang di
seluruh halaman lain.

| Bagian | Terlihat di | Node ID | Berkas | Status |
| --- | --- | --- | --- | --- |
| Skip link "Lewati ke konten utama" | semua publik | `TODO` | `layouts/public.blade.php` | `final` |
| Topbar navy (lokasi, jam, telepon) | semua publik | `TODO` | `layouts/partials/topbar.blade.php` | `final` |
| Header putih + nav + tombol Ajukan Surat | semua publik | `TODO` | `layouts/partials/topbar.blade.php` | `final` |
| Breadcrumb | semua publik kecuali beranda | `TODO` | `layouts/public.blade.php` | `final` |
| Footer 4 kolom navy | semua publik | `TODO` | `layouts/partials/footer.blade.php` | `final` |
| Sidebar admin navy | semua admin | `TODO` | `layouts/partials/admin-sidebar.blade.php` | `final` |
| Topbar admin (cari + notifikasi + akun) | semua admin | `TODO` | `layouts/partials/admin-topbar.blade.php` | `final` |
| Footer admin + versi prototipe | semua admin | `TODO` | `layouts/partials/admin-footer.blade.php` | `final` |
| Panel "Butuh bantuan? Buka panduan" | semua admin | `TODO` | `layouts/partials/admin-sidebar.blade.php` | `final` |
| Layout login split-screen | `11-login.png` | `TODO` | `layouts/auth.blade.php` | `final` |

## Komponen berulang

| Komponen | Terlihat di | Kelas CSS sekarang | Status |
| --- | --- | --- | --- |
| Eyebrow kapital + garis | semua section | `.eyebrow` (baris 96) | `cocok` |
| Tombol primer navy | semua halaman | `.btn-primary` | `cocok` |
| Tombol outline | semua halaman | `.btn-outline` | `cocok` |
| Tombol hijau | kartu kontak WhatsApp | `.btn-green` | `cocok` |
| Badge status | admin | `.badge-blue/green/amber/red/gray` | `cocok` |
| Pil filter berjumlah | `14-pengajuan.png` | `.status-pill` (baris 1391) | `perlu-cek` |
| Chip kode layanan | `06-pelayanan.png`, `16-jenis-surat.png` | `.chip` (baris 1615) | `cocok` |
| Kartu | semua | `.card` (baris 653) | `cocok` |
| Tabel | admin | `.table-wrap` (baris 1129) | `cocok` |
| Bantuan kolom form | `08-pengajuan.png` | `.field-help` (baris 1069) | `cocok` |
| Dropdown kustom | admin & form | `pilih-kustom.css` | `cocok` |
| Toggle aktif/nonaktif | `16-jenis-surat.png` | belum ada kelasnya | `perlu-baru` |
| Akordeon FAQ | `10-kontak.png` | belum ada kelasnya | `perlu-baru` |
| Bar progres sebaran status | `12-ringkasan.png` | belum ada kelasnya | `perlu-baru` |
| Grid dua kolom asimetris | publik | belum ada kelasnya | `perlu-baru` |
| Langkah bernomor (stepper) | `08-pengajuan.png`, `07-pelayanan-detail.png` | belum ada kelasnya | `perlu-baru` |
| Kalender tanggal (kotak) | agenda di `03-informasi.png` | belum ada kelasnya | `perlu-baru` |

**Catatan:** enam komponen terakhir butuh kelas CSS baru. Ini bagian pekerjaan
yang sebenarnya, karena sisanya sudah tersedia di `style.css`.
