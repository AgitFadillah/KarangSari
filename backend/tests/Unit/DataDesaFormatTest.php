<?php

namespace Tests\Unit;

use App\Models\DataDesa;
use PHPUnit\Framework\TestCase;

/**
 * Format angka tabel data desa. Nol harus tetap tampil sebagai "0" dan angka
 * ribuan diberi pemisah, supaya tabel pada halaman profil tidak menampilkan
 * tanda pisah untuk data yang sebenarnya berisi nol.
 */
class DataDesaFormatTest extends TestCase
{
    public function test_nilai_nol_tetap_ditampilkan_sebagai_nol(): void
    {
        $baris = new DataDesa(['nilai' => '0']);

        $this->assertSame('0', $baris->nilaiTampil());
    }

    public function test_nilai_ribuan_diberi_pemisah(): void
    {
        $baris = new DataDesa(['nilai' => '10085']);

        $this->assertSame('10.085', $baris->nilaiTampil());
    }

    public function test_nilai_kosong_menjadi_tanda_pisah(): void
    {
        $baris = new DataDesa(['nilai' => null]);

        $this->assertSame('—', $baris->nilaiTampil());
    }

    public function test_nilai_bukan_angka_dibiarkan_apa_adanya(): void
    {
        $baris = new DataDesa(['nilai' => 'Belum ada data']);

        $this->assertSame('Belum ada data', $baris->nilaiTampil());
    }

    public function test_jumlah_golongan_umur_dijumlahkan_dan_diformat(): void
    {
        $baris = new DataDesa(['nilai_l' => '654', 'nilai_p' => '580']);

        $this->assertSame('1.234', $baris->jumlahTampil());
    }

    public function test_kolom_nol_tetap_tampil_sebagai_nol(): void
    {
        $baris = new DataDesa(['nilai_l' => '0', 'nilai_p' => '42']);

        $this->assertSame('0', $baris->kolomTampil($baris->nilai_l));
        $this->assertSame('42', $baris->kolomTampil($baris->nilai_p));
    }

    public function test_jumlah_kosong_menjadi_tanda_pisah(): void
    {
        $baris = new DataDesa(['nilai_l' => null, 'nilai_p' => null]);

        $this->assertSame('—', $baris->jumlahTampil());
    }
}
