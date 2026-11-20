# Spesifikasi Komponen

Spesifikasi ini disusun dari pembacaan ekspor PNG di `../ekspor/` dan
dibandingkan dengan implementasi yang sudah ada di
`backend/resources/css/style.css`.

Kolom **Status** artinya:

- `cocok` — desain sesuai kelas CSS yang sudah ada, tidak perlu pekerjaan
- `perlu-cek` — ada kelasnya, tapi ukuran/varian mungkin berbeda
- `perlu-baru` — belum ada kelasnya, harus dibuat

---

## Ringkasan

| Komponen | Kelas CSS sekarang | Status |
| --- | --- | --- |
| Eyebrow kapital + garis | `.eyebrow` (baris 96) | `cocok` |
| Tombol | `.btn` + 7 varian (baris 108) | `cocok` |
| Badge status | `.badge-*` (baris 132) | `cocok` |
| Chip kode layanan | `.chip` (baris 1615) | `cocok` |
| Kartu | `.card` (baris 653) | `cocok` |
| Tabel | `.table-wrap` (baris 1129) | `cocok` |
| Bantuan kolom form | `.field-help` (baris 1069) | `cocok` |
| Dropdown kustom | `pilih-kustom.css` | `cocok` |
| Pil filter berjumlah | `.status-pill` (baris 1391) | `perlu-cek` |
| Grid dua kolom asimetris | — | `perlu-baru` |
| Stepper bernomor | — | `perlu-baru` |
| Toggle switch | — | `perlu-baru` |
| Akordeon FAQ | — | `perlu-baru` |
| Bar progres status | — | `perlu-baru` |
| Kotak tanggal kalender | — | `perlu-baru` |

---

## Yang sudah cocok

### Eyebrow / label section

Terlihat sebagai label kapital kecil dengan **garis pendek di kiri**, di atas
setiap judul section. Contoh: `PELAYANAN ADMINISTRASI`, `ALUR PENGAJUAN`,
`POTENSI DESA`.

Implementasi di `style.css` baris 96–101 sudah memakai `::before` sebagai garis
24px. **Cocok, tidak perlu diubah.**

### Tombol

| Varian desain | Kelas | Pemakaian di desain |
| --- | --- | --- |
| Navy solid, ikon panah | `.btn-primary` | "Ajukan Surat", "Isi formulir pengajuan", "Simpan perubahan" |
| Putih dengan border | `.btn-outline` | "Lihat semua layanan", "Batal", "Semua pengajuan" |
| Hijau solid | `.btn-green` | Kartu WhatsApp di `10-kontak.png` |
| Outline merah | `.btn-danger-outline` | "Tolak" di `15-pengajuan-detail.png` |
| Outline kuning | `.btn-amber-outline` | "Minta perbaikan" di `15-pengajuan-detail.png` |
| Kecil untuk tabel | `.btn-sm` | "Periksa", "Edit", "Konfigurasi", "Unduh" |

Radius 8px, padding `12px 22px`, bobot 600 — **semua sudah sesuai**.

**Tetap perlu didesain/ditambahkan:** state hover, fokus keyboard, dan
nonaktif. Ekspor PNG tidak menunjukkan state interaktif.

### Badge status

| Status | Kelas | Terlihat di |
| --- | --- | --- |
| Menunggu Verifikasi | `.badge-amber` | `12`, `14`, `15` |
| Perlu Perbaikan | `.badge-red` | `12`, `14` |
| Disetujui | `.badge-green` | `12`, `14` |
| Sedang Diproses | `.badge-blue` | `12`, `14` |
| Siap Diambil | `.badge-green` | `12`, `14` — *perlu konfirmasi enum* |
| Ditolak | `.badge-red` | `16-jenis-surat` (status dokumen) |
| Selesei / Selesai | `.badge-gray` | `14-pengajuan.png` |

> **Perlu dicek:** desain menampilkan **6 status pengajuan** (Menunggu
> Verifikasi, Perlu Perbaikan, Ditolak, Disetujui, Sedang Diproses, Siap
> Diambil, Selesai — total 7 di panel sebaran status `12-ringkasan.png`).
> Cocokkan dengan `App\Enums\StatusPengajuan` di `backend/app/Enums/`.

### Chip kode layanan

Kotak navy kecil berisi angka `470`, `471`, … di samping nama jenis surat.
`.chip` saat ini berlatar `#E8F0F9` (biru muda), sedangkan desain memakai
**navy solid dengan teks putih**. Ukuran mendekati `.badge-navy`.

→ **Rekomendasi:** pakai `.badge-navy` yang sudah ada, bukan `.chip`.

---

## Yang perlu dibuat baru

### 1. Grid dua kolom asimetris

Pola paling sering muncul di halaman publik:

```
┌─────────────────────────────┬──────────────────────┐
│ Judul besar (h2)            │ Deskripsi penjelas   │
│ ±55% lebar                  │ ±40%, teks abu       │
└─────────────────────────────┴──────────────────────┘
```

Terlihat di `02-profil`, `03-informasi`, `05-potensi`, `06-pelayanan`,
`07-pelayanan-detail`, `10-kontak`.

- [ ] Tentukan nama kelas, mis. `.section-head` dengan
      `grid-template-columns: minmax(0,1.15fr) minmax(0,1fr)`
- [ ] Definisikan perilaku di bawah 768px: judul dan deskripsi menumpuk vertikal
- [ ] Jarak antar kolom (`gap`) — perkirakan 48–64px
- [ ] Padding vertikal section — perkirakan 80–96px desktop, 40–48px mobile

### 2. Stepper bernomor

Dipakai di `08-pengajuan.png` (3 langkah) dan `07-pelayanan-detail.png`
(4 langkah vertikal "Setelah pengajuan dikirim").

- [ ] Lingkaran navy berisi angka putih, 32–36px
- [ ] Judul langkah tebal + deskripsi abu di bawahnya
- [ ] Varian horizontal (pengajuan) dan vertikal (detail layanan)
- [ ] Garis penghubung antar lingkaran (varian horizontal)

### 3. Toggle switch

Di `16-jenis-surat.png`, kolom Status berisi toggle hijau + label "Aktif".

- [ ] Ukuran perkiraan 44×24px, pil penuh
- [ ] Warna aktif `#1F9D63`, nonaktif abu
- [ ] Harus tetap bisa dioperasikan keyboard (`role="switch"`, `:focus-visible`)
- [ ] Perhatikan: ini menggantikan tombol teks, jadi perlu label aksesibel
      (`aria-label="Aktifkan Surat Keterangan Domisili"`)

### 4. Akordeon FAQ

Di `10-kontak.png`, 4 baris pertanyaan dengan ikon chevron.

- [ ] Baris dengan garis pemisah, tanpa latar
- [ ] Chevron berputar saat terbuka
- [ ] Sebaiknya pakai `<details>/<summary>` agar bisa tanpa JavaScript
- [ ] Cek apakah `create.blade.php` sudah punya `details.privacy-details`
      yang bisa dipakai ulang polanya

### 5. Bar progres sebaran status

Di `12-ringkasan.png`, panel kanan berisi 7 baris status dengan bar tipis
berwarna sesuai status.

- [ ] Label kiri + angka kanan
- [ ] Bar tipis 6–8px, radius penuh
- [ ] Warna mengikuti status (kuning, merah, hijau, biru, abu)
- [ ] Total ditampilkan sebagai badge di kanan judul panel

### 6. Kotak tanggal kalender

Dipakai di daftar agenda (`03-informasi.png` baris agenda, `12-ringkasan.png`
panel agenda kanan bawah).

- [ ] Kotak radius 8px berisi angka besar + nama bulan kapital kecil
- [ ] Latar putih, border abu tipis
- [ ] Lebar tetap ±48px agar sejajar antar baris

---

## Catatan aksesibilitas

Beberapa hal dari desain yang perlu diperhatikan:

- **Skip link** di semua halaman publik tampil sebagai bar biru penuh di paling
  atas pada ekspor PNG. Kemungkinan ini hanya karena elemen difokuskan saat
  ekspor. Pastikan di implementasi ia **tersembunyi** sampai menerima fokus
  keyboard (`:focus`), bukan selalu terlihat.
- **Kontras:** teks deskripsi abu `#5B6B7F` di atas latar `#F5F7FA` perlu
  dihitung ulang. Ambang WCAG AA adalah 4.5:1.
- **Badge "Siap Diambil" dan "Disetujui" sama-sama hijau.** Karena status juga
  dibaca cepat oleh petugas, pertimbangkan membedakan dengan ikon, bukan hanya
  warna.
- **Toggle switch** tidak boleh jadi satu-satunya penanda status; sertakan teks
  "Aktif"/"Nonaktif" seperti yang sudah ada di desain.
- Area sentuh minimal **44×44px** untuk tampilan ponsel.
