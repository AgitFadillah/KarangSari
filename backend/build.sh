#!/bin/sh
# =============================================================================
#  Skrip build untuk Railway (Railpack)
# =============================================================================
#  Kenapa memakai skrip terpisah, bukan satu baris panjang di railway.json?
#
#  Railpack sudah punya integrasi Node.js untuk proyek PHP. Bila di folder
#  proyek ada package.json, Railpack SENDIRI yang:
#
#    1. memasang dependency Node (npm install),
#    2. menjalankan skrip "build" di package.json (npm run build), lalu
#    3. membuang devDependency dari node_modules.
#
#  Versi sebelumnya menambahkan "npm ci && npm run build" di buildCommand.
#  Itu membuat build gagal:
#
#    npm error code EBUSY
#    npm error syscall rmdir
#    npm error path /app/node_modules/.vite
#
#  "npm ci" menghapus seluruh node_modules lebih dulu, padahal Railpack
#  menyimpan cache build Vite di node_modules/.vite sebagai lapisan
#  (layer) yang tidak bisa dihapus. Karena itu "npm ci" dilarang di sini.
#
#  Skrip ini hanya menangani dua hal:
#    1. membuang paket khusus pengembangan dari vendor/ (composer --no-dev),
#    2. membangun aset frontend HANYA bila manifest Vite belum ada.
#       Bila Railpack sudah membangunnya, langkah ini dilewati sama sekali.
# =============================================================================

set -eu

# Composer menolak berjalan sebagai root dan mematikan plugin demi keamanan.
# Di dalam container build kita memang root, jadi izin ini perlu diberikan
# eksplisit. Tidak ada plugin pihak ketiga yang dipakai proyek ini, tetapi
# tanpa variabel ini Composer mencetak peringatan yang membingungkan di log.
export COMPOSER_ALLOW_SUPERUSER=1

echo "[build] Memasang dependency PHP tanpa paket pengembangan..."

# "--no-scripts" TIDAK dipakai: skrip post-autoload-dump menjalankan
# "artisan package:discover" yang menyusun ulang bootstrap/cache/services.php
# agar tidak lagi menyebut paket yang baru saja dibuang (Pail, Collision,
# dsb). Tanpa langkah itu, Laravel gagal start karena mencoba memuat kelas
# dari paket yang sudah tidak ada.
composer install --no-dev --optimize-autoloader --no-interaction

# ---------------------------------------------------------------------------
# Aset frontend (Vite)
# ---------------------------------------------------------------------------
# Layout Blade memakai @vite(), jadi public/build/manifest.json WAJIB ada.
# Bila hilang, setiap halaman gagal dengan "Vite manifest not found".
if [ -f public/build/manifest.json ]; then
    echo "[build] public/build/manifest.json sudah ada (dibuat Railpack), lewati npm."
    exit 0
fi

echo "[build] Manifest Vite belum ada, membangun aset frontend..."

# "npm install" (bukan "npm ci") sengaja dipakai: npm install memperbarui
# node_modules yang ada tanpa menghapusnya, sehingga tidak bertabrakan dengan
# cache node_modules/.vite milik Railpack. "--include=dev" perlu karena
# Railpack sudah membuang devDependency sebelum langkah ini berjalan,
# sedangkan Vite sendiri ada di devDependencies.
npm install --include=dev --no-audit --no-fund

npm run build

# Pastikan hasilnya benar-benar ada. Lebih baik build gagal dengan pesan jelas
# daripada container hidup tetapi setiap halaman error.
if [ ! -f public/build/manifest.json ]; then
    echo "[build] GAGAL: npm run build selesai tetapi public/build/manifest.json tidak terbentuk."
    exit 1
fi

echo "[build] Aset frontend selesai dibangun."
