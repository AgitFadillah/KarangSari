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
}
