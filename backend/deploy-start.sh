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

# ---------------------------------------------------------------------------
# Pastikan folder storage yang dibutuhkan Laravel ada.
# ---------------------------------------------------------------------------
# Langkah ini HARUS paling awal, sebelum perintah artisan apa pun.
#
# Bila volume persisten dipasang pada "storage/" (cara yang disarankan agar
# dokumen warga tidak hilang setiap deploy), isi folder asli dari image
# TERTUTUPI oleh volume kosong. Akibatnya Laravel kehilangan
# storage/framework/{cache,sessions,views} dan storage/logs, lalu berhenti
# dengan galat "Please provide a valid cache path" atau gagal menulis log.
#
# Dulu folder ini dibuat SETELAH "migrate". Itu tidak cukup: bila migrasi
# menyentuh galat apa pun, Laravel mencoba menulis log ke storage/logs yang
# belum ada, sehingga container mati sebelum foldernya sempat dibuat.
mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/app/temp \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

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
#
# KENAPA TIDAK MEMAKAI "optimize:clear"?
# Perintah itu menjalankan "cache:clear" juga, dan cache:clear menghapus isi
# tabel "cache" di basis data. Pada deployment pertama tabel itu belum ada
# (baru dibuat oleh migrate di bawah), sehingga hasilnya:
#
#     SQLSTATE[42P01]: Undefined table: relation "cache" does not exist
#
# Perintah itu gagal, cache config tidak pernah terhapus, dan seluruh proses
# start memakai nilai hasil build - persis masalah yang ingin dicegah. Karena
# itu dipakai perintah yang hanya menyentuh berkas di disk.
php artisan config:clear  || echo "[start] Peringatan: config:clear gagal."
php artisan route:clear   || echo "[start] Peringatan: route:clear gagal."
php artisan view:clear    || echo "[start] Peringatan: view:clear gagal."
php artisan event:clear   || echo "[start] Peringatan: event:clear gagal."
php artisan clear-compiled || echo "[start] Peringatan: clear-compiled gagal."

# ---------------------------------------------------------------------------
# Laporkan konfigurasi yang benar-benar diterima aplikasi.
# ---------------------------------------------------------------------------
# Tanpa ini, bila container mati saat start, satu-satunya cara mengetahui
# penyebabnya adalah menebak dari dashboard. Log ini menjawab pertanyaan yang
# paling sering muncul sekaligus: apakah DB_URL terpasang, apakah basis data
# bisa dihubungi, dan apakah ada nilai yang membawa kutip atau baris baru.
#
# Rahasia tidak pernah dicetak: kata sandi hanya ditampilkan sebagai
# "terisi"/"kosong", dan APP_KEY hanya sebagai awalan "base64:".
echo "[start] Memeriksa konfigurasi..."
php artisan tinker --execute='
    $url = (string) env("APP_URL");
    echo "  APP_ENV        = ".env("APP_ENV").PHP_EOL;
    echo "  APP_DEBUG      = ".(env("APP_DEBUG") ? "true" : "false").PHP_EOL;
    echo "  APP_URL        = ".$url.PHP_EOL;
    echo "  APP_URL bersih = ".(($url === trim($url) && strpos($url, chr(34)) === false) ? "ya" : "TIDAK (ada kutip/baris baru)").PHP_EOL;
    echo "  APP_KEY        = ".(str_starts_with((string) env("APP_KEY"), "base64:") ? "base64 (dikenali)" : "TIDAK DIKENALI").PHP_EOL;
    echo "  DB_CONNECTION  = ".env("DB_CONNECTION").PHP_EOL;
    echo "  DB_URL terisi  = ".(env("DB_URL") ? "ya" : "TIDAK").PHP_EOL;
    echo "  DB host        = ".config("database.connections.".config("database.default").".host").PHP_EOL;
    echo "  DB nama        = ".config("database.connections.".config("database.default").".database").PHP_EOL;
    echo "  SESSION_DRIVER = ".env("SESSION_DRIVER").PHP_EOL;
    echo "  DOKUMEN_DISK   = ".config("desa.disk_dokumen").PHP_EOL;
    echo "  Volume storage = ".(is_dir(storage_path("app")) && is_writable(storage_path("app")) ? "siap & bisa ditulis" : "TIDAK BISA DITULIS").PHP_EOL;
' 2>&1 || echo "[start] Peringatan: pemeriksaan konfigurasi gagal dijalankan."

echo "[start] Menguji koneksi basis data..."
php artisan tinker --execute='
    try {
        Illuminate\Support\Facades\DB::connection()->getPdo();
        echo "  Koneksi basis data: BERHASIL".PHP_EOL;
    } catch (Throwable $e) {
        echo "  Koneksi basis data: GAGAL -> ".$e->getMessage().PHP_EOL;
    }
' 2>&1 || echo "[start] Peringatan: uji koneksi basis data gagal dijalankan."

# ---------------------------------------------------------------------------
# Berhenti cepat bila alamat basis data jelas salah.
# ---------------------------------------------------------------------------
# Di hosting container, container aplikasi dan container basis data adalah dua
# mesin terpisah. Alamat "127.0.0.1" di dalam container aplikasi menunjuk ke
# container itu sendiri, dan di sana tidak ada PostgreSQL. Jadi bila DB_URL
# kosong sementara DB_HOST juga tidak diisi (nilai bawaannya 127.0.0.1),
# koneksi PASTI ditolak - bukan "belum siap".
#
# Tanpa pemeriksaan ini, skrip mengulang 10 kali x 5 detik dan membanjiri log
# dengan pesan "Connection refused" yang tidak menjelaskan apa pun. Yang
# sebenarnya terjadi hampir selalu satu hal: teks referensi
# ${{NamaService.DATABASE_URL}} tidak ter-resolve karena nama service basis
# data di dashboard tidak sama persis dengan yang ditulis.
#
# Perhatikan syaratnya: hanya berhenti bila DB_HOST memang kosong atau menunjuk
# ke loopback. Bila pengguna sengaja mengisi DB_HOST, nilainya dihormati dan
# skrip tetap mencoba seperti biasa.
if [ "${DB_CONNECTION:-sqlite}" != "sqlite" ] && [ -z "${DB_URL:-}" ]; then
    case "${DB_HOST:-}" in
        ""|127.0.0.1|localhost|::1)
            echo "[start] GAGAL: DB_URL kosong dan DB_HOST tidak diisi, sehingga"
            echo "[start] aplikasi mencoba menghubungi basis data di alamat"
            echo "[start] container itu sendiri (127.0.0.1:5432). Di sana tidak ada"
            echo "[start] PostgreSQL, jadi koneksi pasti ditolak - bukan soal timing."
            echo "[start]"
            echo "[start] Penyebab paling umum: DB_URL berisi teks referensi"
            echo "[start] seperti \${{NamaService.DATABASE_URL}} yang tidak ter-resolve"
            echo "[start] karena nama service basis data di dashboard tidak sama"
            echo "[start] persis dengan nama yang ditulis."
            echo "[start]"
            echo "[start] Cara memperbaiki (pilih salah satu):"
            echo "[start]   1. Service aplikasi > Variables > DB_URL > klik ikon"
            echo "[start]      Reference (panah) > Add Reference > pilih service"
            echo "[start]      PostgreSQL > pilih DATABASE_URL. Pastikan nilainya"
            echo "[start]      tampil sebagai chip referensi, bukan teks \${{...}} biasa."
            echo "[start]   2. Atau isi manual dari tab Variables service PostgreSQL:"
            echo "[start]      DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD."
            echo "[start]"
            echo "[start] Service ini tidak akan hidup sampai alamat basis data benar."
            exit 1
            ;;
    esac
fi

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
# Folder storage sudah dibuat di awal skrip (lihat bagian atas).
# ---------------------------------------------------------------------------

# Foto perangkat desa disimpan di disk "public". Symlink ini tidak tersimpan
# di repositori dan hilang setiap container diganti, jadi dibuat ulang di sini.
# Kegagalan symlink tidak boleh menghentikan aplikasi: dokumen warga tetap
# dapat diakses karena tidak memakai symlink.
#
# Pemeriksaan "-L" (symlink) dan "-e" (ada) dipakai agar log tidak penuh galat
# palsu. Di Windows, Laravel membuat "junction", dan PHP menganggapnya bukan
# symlink, sehingga "storage:link --force" mencetak "link already exists"
# padahal tidak ada yang salah. Di Linux (Railway) pemeriksaan ini tetap
# membuat ulang symlink setiap kali container start.
if [ -L public/storage ] || [ ! -e public/storage ]; then
    php artisan storage:link --force || echo "[start] Peringatan: storage:link gagal (foto perangkat mungkin tidak tampil)."
else
    echo "[start] public/storage sudah ada dan bukan symlink, lewati storage:link."
fi

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
