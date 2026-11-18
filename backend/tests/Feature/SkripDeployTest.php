<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Skrip start (`deploy-start.sh`) harus bisa dijalankan pada basis data kosong.
 *
 * Kenapa ini diuji?
 *
 * Deployment pertama di hosting container selalu berjalan dengan basis data
 * yang benar-benar kosong: belum ada satu tabel pun. Skrip start justru
 * dijalankan SEBELUM `migrate`, jadi perintah apa pun yang menyentuh tabel akan
 * gagal di sana.
 *
 * Itu pernah terjadi. Skrip memakai `php artisan optimize:clear` untuk membuang
 * cache config hasil build. Perintah itu ikut menjalankan `cache:clear`, yang
 * menghapus isi tabel `cache`:
 *
 *     SQLSTATE[42P01]: Undefined table: relation "cache" does not exist
 *
 * Akibatnya bukan sekadar peringatan. `optimize:clear` gagal seluruhnya, cache
 * config hasil build TIDAK terhapus, dan `migrate` di bawahnya tetap memakai
 * `DB_URL`/`APP_KEY` yang terkunci saat build. Gejalanya membingungkan: build
 * dilaporkan sukses, tetapi aplikasi tidak pernah mau hidup.
 *
 * Uji ini menjaga agar skrip start tidak kembali memakai perintah yang butuh
 * tabel basis data.
 */
class SkripDeployTest extends TestCase
{
    private function skrip(string $nama): string
    {
        $berkas = dirname(__DIR__, 2) . '/' . $nama;

        $this->assertFileExists($berkas, "Berkas {$nama} hilang dari repositori.");

        return (string) file_get_contents($berkas);
    }

    /**
     * Baris perintah yang benar-benar dieksekusi (bukan komentar).
     *
     * @return array<int, string>
     */
    private function barisPerintah(string $isi): array
    {
        $hasil = [];

        foreach (preg_split('/\R/', $isi) ?: [] as $baris) {
            $bersih = trim($baris);

            if ($bersih === '' || str_starts_with($bersih, '#')) {
                continue;
            }

            $hasil[] = $bersih;
        }

        return $hasil;
    }

    public function test_skrip_start_tidak_memakai_optimize_clear(): void
    {
        $perintah = implode("\n", $this->barisPerintah($this->skrip('deploy-start.sh')));

        $this->assertStringNotContainsString(
            'optimize:clear',
            $perintah,
            'deploy-start.sh tidak boleh memakai "optimize:clear": perintah itu menjalankan '
            . '"cache:clear" yang menghapus tabel "cache", padahal tabel itu belum ada pada '
            . 'deploy pertama. Pakai config:clear/route:clear/view:clear/event:clear.'
        );
    }

    public function test_skrip_start_tidak_menghapus_cache_basis_data(): void
    {
        $perintah = implode("\n", $this->barisPerintah($this->skrip('deploy-start.sh')));

        foreach (['cache:clear', 'cache:forget', 'queue:clear'] as $terlarang) {
            $this->assertStringNotContainsString(
                $terlarang,
                $perintah,
                "deploy-start.sh tidak boleh memakai \"{$terlarang}\": perintah itu membutuhkan "
                . 'tabel basis data yang baru dibuat oleh "migrate" di bawahnya.'
            );
        }
    }

    /**
     * Perintah pembersih cache yang aman harus benar-benar dipanggil, bukan
     * hanya disebut di komentar.
     */
    public function test_skrip_start_membersihkan_cache_config(): void
    {
        $perintah = implode("\n", $this->barisPerintah($this->skrip('deploy-start.sh')));

        $this->assertStringContainsString(
            'config:clear',
            $perintah,
            'deploy-start.sh harus menjalankan "config:clear". Tanpa itu, nilai DB_URL dan '
            . 'APP_KEY hasil build tetap terkunci dan variabel di dashboard diabaikan.'
        );

        // Cache config harus dibuang SEBELUM migrasi, bukan sesudah.
        $posisiClear = strpos($perintah, 'config:clear');
        $posisiMigrate = strpos($perintah, 'migrate --force');

        $this->assertNotFalse($posisiMigrate, 'deploy-start.sh harus menjalankan "migrate --force".');
        $this->assertLessThan(
            $posisiMigrate,
            $posisiClear,
            'config:clear harus dijalankan SEBELUM migrate; kalau tidak, migrate memakai '
            . 'konfigurasi hasil build dan bisa menyambung ke basis data yang salah.'
        );
    }

    /**
     * Folder storage harus dibuat SEBELUM perintah artisan apa pun.
     *
     * Bila volume persisten dipasang pada "storage/", isi folder asli dari
     * image tertutupi oleh volume kosong. Laravel lalu kehilangan
     * storage/logs dan storage/framework/{cache,sessions,views}.
     *
     * Kalau folder itu baru dibuat setelah "migrate", satu galat saja saat
     * migrasi sudah cukup untuk mematikan container: Laravel mencoba menulis
     * log ke folder yang belum ada. Itu gejalanya sama membingungkan dengan
     * bug "cache" sebelumnya — build sukses, container mati.
     */
    public function test_folder_storage_dibuat_sebelum_perintah_artisan(): void
    {
        $perintah = implode("\n", $this->barisPerintah($this->skrip('deploy-start.sh')));

        $posisiMkdir = strpos($perintah, 'storage/framework/sessions');
        $posisiMigrate = strpos($perintah, 'migrate --force');
        $posisiClear = strpos($perintah, 'config:clear');

        $this->assertNotFalse(
            $posisiMkdir,
            'deploy-start.sh harus membuat folder storage (storage/framework/sessions dan '
            . 'sebagainya). Tanpa itu, volume kosong di /app/storage membuat aplikasi mati '
            . 'dengan "Please provide a valid cache path".'
        );

        $this->assertNotFalse($posisiMigrate, 'deploy-start.sh harus menjalankan "migrate --force".');
        $this->assertNotFalse($posisiClear, 'deploy-start.sh harus menjalankan "config:clear".');

        $this->assertLessThan(
            $posisiMigrate,
            $posisiMkdir,
            'Folder storage harus dibuat SEBELUM migrate. Kalau dibuat sesudahnya, galat apa pun '
            . 'saat migrasi tidak bisa dicatat ke storage/logs dan container mati lebih awal.'
        );

        $this->assertLessThan(
            $posisiClear,
            $posisiMkdir,
            'Folder storage harus dibuat SEBELUM config:clear, karena perintah artisan itu pun '
            . 'sudah membutuhkan storage/logs untuk mencatat galat.'
        );
    }

    /**
     * `railway.json` harus ada di ROOT repositori, bukan hanya di backend/.
     *
     * Dokumentasi Railway: "The Railway Config File does not follow the Root
     * Directory path. You have to specify the absolute path for the
     * railway.json or railway.toml file."
     *
     * Artinya dengan Root Directory = /backend, Railway tetap mencari
     * railway.json di ROOT repo. Berkas yang hanya ada di backend/ tidak
     * pernah dibaca. Akibatnya startCommand, healthcheckPath, dan
     * restartPolicy di dalamnya diabaikan seluruhnya.
     *
     * Ini pernah terjadi dan gejalanya menyesatkan: build sukses, deploy
     * dilaporkan "successful" hanya dalam 12 detik, tetapi container tidak
     * pernah menjalankan skrip start sehingga tidak ada proses yang
     * mendengarkan port - Railway membalas 502 tanpa petunjuk apa pun.
     */
    public function test_railway_json_ada_di_root_repositori(): void
    {
        $root = dirname(__DIR__, 3);
        $berkas = $root . '/railway.json';

        $this->assertFileExists(
            $berkas,
            'railway.json harus ada di ROOT repositori. Railway tidak membaca berkas ini '
            . 'dari dalam Root Directory (/backend), sehingga tanpa berkas di root, '
            . 'startCommand "sh deploy-start.sh" tidak pernah dipakai dan container tidak '
            . 'pernah menjalankan aplikasi.'
        );

        $isi = (string) file_get_contents($berkas);
        $konfig = json_decode($isi, true);

        $this->assertIsArray($konfig, 'railway.json di root harus berupa JSON yang valid.');

        $start = (string) ($konfig['deploy']['startCommand'] ?? '');
        $build = (string) ($konfig['build']['buildCommand'] ?? '');

        $this->assertStringContainsString(
            'deploy-start.sh',
            $start,
            'startCommand di railway.json (root) harus menjalankan deploy-start.sh.'
        );

        $this->assertStringContainsString(
            'build.sh',
            $build,
            'buildCommand di railway.json (root) harus menjalankan build.sh.'
        );

        // Perintah harus tetap benar baik dijalankan dari root repo maupun dari
        // dalam /backend, karena keduanya mungkin terjadi tergantung bagaimana
        // Railway menerapkan Root Directory.
        foreach (['startCommand' => $start, 'buildCommand' => $build] as $nama => $perintah) {
            $this->assertStringContainsString(
                'cd backend',
                $perintah,
                "{$nama} di railway.json (root) harus menangani kemungkinan dijalankan dari "
                . 'root repo, dengan masuk ke folder backend lebih dulu.'
            );
        }

        $this->assertSame(
            '/up',
            $konfig['deploy']['healthcheckPath'] ?? null,
            'healthcheckPath di railway.json (root) harus "/up" agar Railway menunggu '
            . 'aplikasi benar-benar siap sebelum menganggap deploy berhasil.'
        );
    }

    /**
     * Skrip dijalankan oleh "sh" di Linux (Railway). Berkas dengan akhir baris
     * CRLF membuat sh gagal dengan pesan seperti:
     *
     *     sh: ./deploy-start.sh: /bin/sh^M: bad interpreter
     */
    public function test_skrip_deploy_memakai_akhir_baris_lf(): void
    {
        foreach (['deploy-start.sh', 'build.sh'] as $nama) {
            $isi = $this->skrip($nama);

            $this->assertStringNotContainsString(
                "\r",
                $isi,
                "{$nama} mengandung karakter CR (akhir baris Windows). Simpan sebagai LF murni, "
                . 'kalau tidak skrip gagal dijalankan di container Linux.'
            );

            $this->assertStringStartsWith(
                '#!/bin/sh',
                $isi,
                "{$nama} harus diawali baris shebang \"#!/bin/sh\"."
            );
        }
    }

    /**
     * Skrip build tidak boleh memakai "npm ci" (lihat docs/SIAP-PRODUCTION.md).
     */
    public function test_skrip_build_tidak_memakai_npm_ci(): void
    {
        $perintah = implode("\n", $this->barisPerintah($this->skrip('build.sh')));

        $this->assertStringNotContainsString(
            'npm ci',
            $perintah,
            'build.sh tidak boleh memakai "npm ci": perintah itu menghapus node_modules, '
            . 'termasuk lapisan cache node_modules/.vite milik Railpack, dan build gagal '
            . 'dengan EBUSY. Pakai "npm install".'
        );

        $this->assertStringContainsString(
            'manifest.json',
            $perintah,
            'build.sh harus memeriksa public/build/manifest.json; tanpa manifest, @vite() '
            . 'membuat setiap halaman gagal dengan "Vite manifest not found".'
        );
    }

    /**
     * Skrip start harus BERHENTI CEPAT bila alamat basis data jelas salah.
     *
     * Di hosting container, aplikasi dan PostgreSQL adalah dua mesin terpisah.
     * Alamat 127.0.0.1 di dalam container aplikasi menunjuk ke container itu
     * sendiri, dan di sana tidak ada PostgreSQL. Jadi bila DB_URL kosong
     * sementara DB_HOST tidak diisi, koneksi PASTI ditolak - bukan "belum siap".
     *
     * Tanpa pemeriksaan ini, skrip mengulang 10 kali x 5 detik dan membanjiri
     * log dengan "Connection refused" yang tidak menjelaskan apa pun. Itu
     * pernah terjadi: log penuh galat koneksi, tetapi penyebab sebenarnya satu
     * hal sederhana - teks referensi ${{NamaService.DATABASE_URL}} tidak
     * ter-resolve karena nama service basis data di dashboard tidak sama persis.
     *
     * Pemeriksaan harus berada SEBELUM perulangan migrate, supaya tidak ada
     * satu pun percobaan koneksi yang sia-sia.
     */
    public function test_skrip_start_berhenti_cepat_bila_alamat_basis_data_salah(): void
    {
        $perintah = implode("\n", $this->barisPerintah($this->skrip('deploy-start.sh')));

        $posisiJaga = strpos($perintah, 'DB_URL kosong dan DB_HOST tidak diisi');

        $this->assertNotFalse(
            $posisiJaga,
            'deploy-start.sh harus menjelaskan dengan gamblang saat DB_URL kosong dan DB_HOST '
            . 'tidak diisi: aplikasi mencoba menghubungi basis data di container itu sendiri. '
            . 'Tanpa pesan itu, log hanya berisi "Connection refused" yang menyesatkan.'
        );

        $posisiMigrate = strpos($perintah, 'migrate --force');

        $this->assertNotFalse($posisiMigrate, 'deploy-start.sh harus menjalankan "migrate --force".');

        $this->assertLessThan(
            $posisiMigrate,
            $posisiJaga,
            'Pemeriksaan alamat basis data harus dijalankan SEBELUM migrate. Kalau diletakkan '
            . 'sesudahnya, skrip tetap mengulang 10 kali dan log tetap dipenuhi galat koneksi.'
        );

        $this->assertStringContainsString(
            'Add Reference',
            $perintah,
            'Pesan galat harus menyebut cara memperbaikinya di dashboard Railway (ikon Reference '
            . 'atau "Add Reference" pada variabel DB_URL).'
        );
    }

    /**
     * Uji perilaku sungguhan: skrip dijalankan dengan "php" tiruan.
     *
     * Pemeriksaan teks di atas bisa lolos walaupun logikanya salah. Uji ini
     * membuktikan skrip benar-benar berhenti sebelum menyentuh basis data, dan
     * tetap berjalan normal ketika alamat basis data memang diisi.
     *
     * @return array{0:int,1:string}
     */
    private function jalankanSkripStart(array $variabel): array
    {
        $sh = $this->cariShell();

        if ($sh === null) {
            $this->markTestSkipped('Shell "sh" tidak tersedia di sistem ini.');
        }

        $akar = dirname(__DIR__, 2);
        $palsu = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'stub-php-' . bin2hex(random_bytes(4));

        if (! is_dir($palsu)) {
            mkdir($palsu, 0777, true);
        }

        // "php" tiruan: mencatat setiap perintah artisan yang dipanggil, tanpa
        // benar-benar menjalankan Laravel. Dengan begitu uji ini tidak butuh
        // basis data dan tetap membuktikan urutan pemanggilannya.
        $stub = $palsu . DIRECTORY_SEPARATOR . 'php';
        file_put_contents($stub, "#!/bin/sh\necho \"STUB-PHP \$*\"\nexit 0\n");
        @chmod($stub, 0755);

        $env = [];

        foreach (getenv() as $kunci => $nilai) {
            $env[$kunci] = $nilai;
        }

        // Windows menyimpan variabel PATH dengan nama "Path". Bila nama itu
        // diteruskan apa adanya, shell tidak menemukan perintah dasar seperti
        // "mkdir" dan skrip gagal karena alasan yang tidak ada hubungannya
        // dengan yang sedang diuji. Kedua ejaan diisi agar sama di semua sistem.
        $pathAsli = $env['PATH'] ?? ($env['Path'] ?? '');

        unset($env['Path']);
        $env['PATH'] = $palsu . PATH_SEPARATOR . $pathAsli;

        foreach (['DB_URL', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $kunci) {
            unset($env[$kunci]);
        }

        $env['APP_KEY'] = 'base64:' . base64_encode(random_bytes(32));

        foreach ($variabel as $kunci => $nilai) {
            $env[$kunci] = $nilai;
        }

        $proses = proc_open(
            '"' . $sh . '" deploy-start.sh',
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipa,
            $akar,
            $env
        );

        $this->assertIsResource($proses, 'Skrip start tidak bisa dijalankan untuk diuji.');

        $keluaran = (string) stream_get_contents($pipa[1]) . (string) stream_get_contents($pipa[2]);

        fclose($pipa[1]);
        fclose($pipa[2]);

        $kode = proc_close($proses);

        @unlink($stub);
        @rmdir($palsu);

        return [$kode, $keluaran];
    }

    private function cariShell(): ?string
    {
        foreach (['C:\\Users\\Git\\bin\\sh.exe', '/bin/sh', '/usr/bin/sh'] as $calon) {
            if (is_file($calon)) {
                return $calon;
            }
        }

        return null;
    }

    public function test_skrip_start_berhenti_sebelum_migrate_bila_db_url_kosong(): void
    {
        [$kode, $keluaran] = $this->jalankanSkripStart([
            'DB_CONNECTION' => 'pgsql',
        ]);

        $this->assertSame(
            1,
            $kode,
            'Skrip harus keluar dengan kode 1 (gagal) ketika DB_URL kosong dan DB_HOST tidak '
            . 'diisi. Keluaran: ' . $keluaran
        );

        $this->assertStringContainsString(
            'DB_URL kosong',
            $keluaran,
            'Pesan galat harus menyebut DB_URL kosong sebagai penyebabnya.'
        );

        $this->assertStringNotContainsString(
            'artisan migrate',
            $keluaran,
            'Skrip tidak boleh mencoba migrate sama sekali ketika alamat basis data sudah pasti '
            . 'salah. Setiap percobaan hanya menambah galat "Connection refused" di log.'
        );
    }

    public function test_skrip_start_tetap_mencoba_migrate_bila_db_url_terisi(): void
    {
        [$kode, $keluaran] = $this->jalankanSkripStart([
            'DB_CONNECTION' => 'pgsql',
            'DB_URL' => 'postgresql://pengguna:rahasia@postgres.railway.internal:5432/railway',
        ]);

        $this->assertStringContainsString(
            'artisan migrate',
            $keluaran,
            'Pemeriksaan alamat basis data tidak boleh terlalu ketat: bila DB_URL terisi, skrip '
            . 'harus tetap menjalankan migrate. Keluaran: ' . $keluaran
        );

        $this->assertStringNotContainsString(
            'DB_URL kosong',
            $keluaran,
            'Skrip tidak boleh menuduh DB_URL kosong padahal variabelnya terisi.'
        );

        $this->assertNotSame(
            1,
            $kode,
            'Dengan DB_URL terisi, skrip tidak boleh berhenti dengan kode gagal.'
        );
    }
}
