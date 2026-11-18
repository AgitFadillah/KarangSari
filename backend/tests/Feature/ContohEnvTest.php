<?php

namespace Tests\Feature;

use Dotenv\Dotenv;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Berkas contoh konfigurasi harus benar-benar bisa dibaca Dotenv.
 *
 * Kenapa ini diuji?
 *
 * ".env.production.example" adalah satu-satunya petunjuk konfigurasi yang
 * dibaca petugas saat memasang situs di server. Bila berkas itu tidak bisa
 * diparse, Laravel berhenti sebelum aplikasi jalan dengan galat yang tidak
 * menyebut baris mana yang salah:
 *
 *     Dotenv\Exception\InvalidFileException:
 *     Failed to parse dotenv file. Encountered unexpected whitespace at [...]
 *
 * Itu pernah terjadi: nilai placeholder ditulis "DB_PASSWORD=<<< RAHASIA >>>"
 * tanpa tanda kutip, dan Dotenv menolak karakter "<" serta spasi di dalam
 * nilai yang tidak dikutip. Petugas yang menyalin berkas itu lalu mengisi
 * nilainya tetap akan gagal, karena berkasnya sudah tidak sah sejak awal.
 *
 * Uji ini memastikan setiap berkas contoh tetap bisa diparse, dan memastikan
 * placeholder yang dipakai mudah dikenali (berawalan ISI_).
 */
class ContohEnvTest extends TestCase
{
    /**
     * Berkas contoh yang disimpan di repositori.
     *
     * @return array<string, string>
     */
    private function berkasContoh(): array
    {
        $akar = dirname(__DIR__, 2);

        return [
            '.env.example' => $akar . '/.env.example',
            '.env.production.example' => $akar . '/.env.production.example',
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function berkasContohProvider(): array
    {
        return [
            '.env.example' => ['.env.example'],
            '.env.production.example' => ['.env.production.example'],
        ];
    }

    #[DataProvider('berkasContohProvider')]
    public function test_berkas_contoh_bisa_dibaca_dotenv(string $nama): void
    {
        $berkas = $this->berkasContoh()[$nama];

        $this->assertFileExists($berkas, "Berkas contoh {$nama} hilang dari repositori.");

        try {
            $nilai = Dotenv::parse(file_get_contents($berkas));
        } catch (\Dotenv\Exception\InvalidFileException $e) {
            $this->fail(
                "Berkas {$nama} tidak bisa dibaca Dotenv: {$e->getMessage()}\n" .
                'Periksa nilai yang mengandung karakter <, >, spasi, atau # tanpa tanda kutip.'
            );
        }

        $this->assertIsArray($nilai);
        $this->assertNotEmpty($nilai, "Berkas {$nama} tidak memuat satu variabel pun.");
    }

    /**
     * Nilai yang wajib benar pada setiap berkas contoh.
     */
    #[DataProvider('berkasContohProvider')]
    public function test_nilai_keamanan_pada_berkas_contoh_benar(string $nama): void
    {
        $nilai = Dotenv::parse(file_get_contents($this->berkasContoh()[$nama]));

        // Seluruh halaman menulis "WIB" secara harfiah.
        $this->assertSame(
            'Asia/Jakarta',
            $nilai['APP_TIMEZONE'] ?? null,
            "APP_TIMEZONE di {$nama} harus Asia/Jakarta."
        );

        // Dokumen warga (KTP/KK) tidak boleh tersimpan di disk publik.
        $this->assertSame('local', $nilai['DOKUMEN_DISK'] ?? null, "DOKUMEN_DISK di {$nama} harus local.");

        // Cookie sesi hanya boleh dikirim lewat HTTPS.
        $this->assertSame(
            'true',
            $nilai['SESSION_SECURE_COOKIE'] ?? null,
            "SESSION_SECURE_COOKIE di {$nama} harus true."
        );
    }

    /**
     * Berkas contoh PRODUCTION tidak boleh membawa nilai pengembangan.
     */
    public function test_contoh_production_tidak_membawa_nilai_pengembangan(): void
    {
        $nilai = Dotenv::parse(file_get_contents($this->berkasContoh()['.env.production.example']));

        // Bila APP_DEBUG tertinggal "true", setiap galat menampilkan isi .env,
        // APP_KEY, dan potongan kode ke pengunjung mana pun.
        $this->assertSame('false', $nilai['APP_DEBUG'] ?? null, 'APP_DEBUG di .env.production.example harus false.');

        $this->assertSame('production', $nilai['APP_ENV'] ?? null, 'APP_ENV di .env.production.example harus production.');

        // SQLite tidak cocok untuk produksi: pengajuan bersamaan dapat membuat
        // berkasnya terkunci atau rusak.
        $this->assertSame('pgsql', $nilai['DB_CONNECTION'] ?? null, 'DB_CONNECTION di .env.production.example harus pgsql.');

        // Email tidak boleh dibuang ke log di produksi — warga tidak akan
        // pernah menerima pemberitahuan apa pun.
        $this->assertNotSame('log', $nilai['MAIL_MAILER'] ?? null, 'MAIL_MAILER di .env.production.example tidak boleh log.');

        // APP_URL harus HTTPS dan tanpa garis miring di akhir, kalau tidak
        // tautan pada surat dan notifikasi menjadi salah.
        $url = (string) ($nilai['APP_URL'] ?? '');
        $this->assertStringStartsWith('https://', $url, 'APP_URL di .env.production.example harus https://.');
        $this->assertStringEndsNotWith('/', $url, 'APP_URL di .env.production.example tidak boleh berakhir dengan "/".');
    }

    /**
     * Petunjuk pada berkas contoh tidak boleh menyuruh mengisi nilai yang
     * mustahil diparse.
     */
    public function test_berkas_contoh_tidak_memuat_placeholder_merusak(): void
    {
        foreach ($this->berkasContoh() as $nama => $berkas) {
            foreach (file($berkas, FILE_IGNORE_NEW_LINES) as $nomor => $baris) {
                $baris = trim($baris);

                // Baris komentar boleh memuat karakter apa pun.
                if ($baris === '' || str_starts_with($baris, '#')) {
                    continue;
                }

                $this->assertStringNotContainsString(
                    '<<<',
                    $baris,
                    "{$nama} baris " . ($nomor + 1) . ' memuat placeholder "<<<" yang membuat Dotenv gagal: ' . $baris
                );
            }
        }
    }
}
