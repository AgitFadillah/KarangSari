#!/bin/sh
# =============================================================================
#  Skrip start untuk Railway (dan hosting container lain)
# =============================================================================
#  Kenapa memakai skrip terpisah, bukan satu baris panjang di railway.json?
#
#  Pada hosting container, container aplikasi dan container basis data start
#  bersamaan. Seringkali PHP sudah hidup tetapi PostgreSQL belum menerima
#  koneksi, sehingga "migrate" gagal dan aplikasi mati padahal tidak ada yang
#  salah pada kode. Skrip ini menunggu basis data siap (maksimal ~50 detik)
#  sebelum menyerah, lalu menyiapkan hal-hal yang hanya boleh dijalankan saat
#  start:
#
#    1. migrate  -> tabel dibuat/diperbarui (aman dijalankan berulang)
#    2. mkdir    -> folder storage dibuat ulang bila volume baru saja dipasang
#    3. storage:link -> agar foto perangkat desa tampil (symlink hilang setiap
#       container diganti, jadi harus dibuat ulang)
#    4. optimize -> cache config/route/view agar tidak dikompilasi tiap request
#    5. serve    -> jalankan aplikasi di port yang diberikan Railway
#
#  Semua langkah aman dijalankan berulang kali.
# =============================================================================

set -u

# ---------------------------------------------------------------------------
# Pemeriksaan awal: APP_KEY wajib ada.
# ---------------------------------------------------------------------------
# Tanpa APP_KEY, Laravel langsung berhenti dengan galat "No application
# encryption key has been specified" dan container berulang kali restart tanpa
# penjelasan di log yang mudah dibaca. Pesan di bawah jauh lebih jelas.
#
# PENTING: jangan membuat APP_KEY baru setiap start. Nilainya dipakai untuk
# mengenkripsi cookie sesi dan data terenkripsi; bila berubah, semua sesi
# admin dan warga menjadi tidak sah. Simpan sebagai variabel lingkungan
# permanen di dashboard hosting (Railway > Variables).
if [ -z "${APP_KEY:-}" ]; then
    echo "[start] GAGAL: variabel APP_KEY belum diisi."
    echo "[start] Buat sekali dengan perintah:  php artisan key:generate --show"
    echo "[start] lalu simpan hasilnya sebagai variabel APP_KEY di dashboard hosting."
    exit 1
fi

echo "[start] Menunggu basis data siap dan menjalankan migrasi..."

# ---------------------------------------------------------------------------
# Buang cache config hasil build SEBELUM menyentuh basis data.
# ---------------------------------------------------------------------------
# Railpack menjalankan "php artisan config:cache" pada tahap build. Nilai
# seperti DB_URL, APP_URL, dan APP_KEY saat itu ikut TERBAKED di dalam
# bootstrap/cache/config.php, dan variabel runtime diabaikan selama berkas
# cache itu ada.
#
# Akibatnya, bila variabel diubah di dashboard lalu container dijalankan ulang
# tanpa build ulang, Laravel tetap memakai nilai lama. Yang lebih berbahaya:
# "migrate" di bawah bisa menyambung ke basis data yang salah. Karena itu
# cache dibuang dulu, sehingga seluruh proses start memakai variabel runtime.
# Cache dibangun ulang oleh "optimize" di akhir skrip ini.
php artisan optimize:clear >/dev/null 2>&1 || echo "[start] Peringatan: optimize:clear gagal (lanjut dengan cache lama)."

percobaan=0
batas=10

while :; do
    if php artisan migrate --force --no-interaction; then
        echo "[start] Migrasi selesai."
        break
    fi

    percobaan=$((percobaan + 1))

    if [ "$percobaan" -ge "$batas" ]; then
        echo "[start] GAGAL: migrasi tidak berhasil setelah $batas percobaan."
        echo "[start] Periksa variabel DB_URL (disarankan) atau DB_HOST, DB_PORT,"
        echo "[start] DB_DATABASE, DB_USERNAME, DB_PASSWORD, dan pastikan layanan"
        echo "[start] PostgreSQL sudah dibuat serta terhubung ke service ini."
        exit 1
    fi

    echo "[start] Basis data belum siap (percobaan $percobaan/$batas), menunggu 5 detik..."
    sleep 5
done

# ---------------------------------------------------------------------------
# Pastikan folder storage yang dibutuhkan Laravel ada.
# ---------------------------------------------------------------------------
# Bila volume persisten dipasang pada "storage/" (cara yang disarankan agar
# dokumen warga tidak hilang setiap deploy), isi folder asli dari image
# TERTUTUPI oleh volume kosong. Akibatnya Laravel kehilangan
# storage/framework/{cache,sessions,views} dan berhenti dengan galat
# "Please provide a valid cache path" — container crash berulang tanpa
# petunjuk yang jelas. Membuat ulang foldernya di sini membuat volume
# kosong tetap bisa dipakai.
mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/app/temp \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Foto perangkat desa disimpan di disk "public". Symlink ini tidak tersimpan
# di repositori dan hilang setiap container diganti, jadi dibuat ulang di sini.
# Kegagalan symlink tidak boleh menghentikan aplikasi: dokumen warga tetap
# dapat diakses karena tidak memakai symlink.
php artisan storage:link --force || echo "[start] Peringatan: storage:link gagal (foto perangkat mungkin tidak tampil)."

# Cache konfigurasi, route, dan view.
php artisan optimize || echo "[start] Peringatan: optimize gagal, aplikasi tetap dijalankan tanpa cache."

echo "[start] Menjalankan aplikasi pada port ${PORT:-8000}..."

# Jumlah proses yang melayani permintaan.
#
# "php artisan serve" bawaan hanya memakai SATU proses, sehingga permintaan
# dilayani bergiliran: selama satu warga membuat surat Word (menyusun ulang
# dokumen .docx, bisa 1-2 detik), warga lain yang membuka halaman harus
# menunggu. Dengan beberapa proses, halaman tetap terbuka meski ada yang
# sedang mengunduh surat.
#
# Tanpa "PHP_CLI_SERVER_WORKERS", Laravel mengabaikan nilainya dan tetap
# memakai satu proses. Tanpa "--no-reload", Laravel juga menolak memakai
# lebih dari satu proses karena mode reload memantau perubahan .env (tidak
# diperlukan di produksi).
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"

# "exec" menggantikan proses shell dengan PHP, sehingga sinyal berhenti dari
# Railway diteruskan langsung ke aplikasi (deploy/restart tidak menggantung).
exec php artisan serve --no-reload --host=0.0.0.0 --port="${PORT:-8000}"
