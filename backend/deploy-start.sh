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
#    2. storage:link -> agar foto perangkat desa tampil (symlink hilang setiap
#       container diganti, jadi harus dibuat ulang)
#    3. optimize -> cache config/route/view agar tidak dikompilasi tiap request
#    4. serve    -> jalankan aplikasi di port yang diberikan Railway
#
#  Semua langkah aman dijalankan berulang kali.
# =============================================================================

set -u

echo "[start] Menunggu basis data siap dan menjalankan migrasi..."

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
        echo "[start] Periksa variabel DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD,"
        echo "[start] dan pastikan layanan PostgreSQL sudah dibuat serta terhubung ke service ini."
        exit 1
    fi

    echo "[start] Basis data belum siap (percobaan $percobaan/$batas), menunggu 5 detik..."
    sleep 5
done

# Foto perangkat desa disimpan di disk "public". Symlink ini tidak tersimpan
# di repositori dan hilang setiap container diganti, jadi dibuat ulang di sini.
# Kegagalan symlink tidak boleh menghentikan aplikasi: dokumen warga tetap
# dapat diakses karena tidak memakai symlink.
php artisan storage:link --force || echo "[start] Peringatan: storage:link gagal (foto perangkat mungkin tidak tampil)."

# Cache konfigurasi, route, dan view.
php artisan optimize || echo "[start] Peringatan: optimize gagal, aplikasi tetap dijalankan tanpa cache."

echo "[start] Menjalankan aplikasi pada port ${PORT:-8000}..."

# "exec" menggantikan proses shell dengan PHP, sehingga sinyal berhenti dari
# Railway diteruskan langsung ke aplikasi (deploy/restart tidak menggantung).
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
