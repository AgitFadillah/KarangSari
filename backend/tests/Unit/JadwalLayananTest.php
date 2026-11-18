<?php

namespace Tests\Unit;

use App\Models\ProfilDesa;
use PHPUnit\Framework\TestCase;

/**
 * Kartu "Jam pelayanan" di halaman kontak dulu ditulis mati di blade sehingga
 * selalu menampilkan "Sabtu–Minggu: Tutup" walau kantor buka setiap hari.
 * Sekarang jadwalnya diurai dari kolom yang diisi perangkat desa lewat menu
 * Profil & Kontak, jadi penguraiannya perlu dijaga test.
 */
class JadwalLayananTest extends TestCase
{
    /**
     * @param  array<int, array{hari: string, jam: string, tutup: bool}>  $jadwal
     * @return array<int, string>
     */
    protected function ringkas(array $jadwal): array
    {
        return array_map(
            fn (array $baris): string => $baris['hari'].' = '.($baris['jam'] ?: '—'),
            $jadwal,
        );
    }

    public function test_nama_hari_digabung_dengan_jam_pelayanan(): void
    {
        // Data nyata di lapangan: kunjungan hanya berisi nama hari.
        $profil = new ProfilDesa([
            'jam_layanan' => '08.00 - 17.00',
            'jam_kunjungan' => 'Senin - Minggu',
        ]);

        $this->assertSame(['Senin - Minggu = 08.00 - 17.00'], $this->ringkas($profil->jadwalTampil()));
    }

    public function test_rincian_harian_lengkap_dipakai_apa_adanya(): void
    {
        $profil = new ProfilDesa([
            'jam_layanan' => '08.00 - 17.00',
            'jam_kunjungan' => 'Senin-Kamis 08.00-15.00, Jumat 08.00-11.30, Sabtu 08.00-12.00',
        ]);

        $this->assertSame([
            'Senin-Kamis = 08.00-15.00',
            'Jumat = 08.00-11.30',
            'Sabtu = 08.00-12.00',
        ], $this->ringkas($profil->jadwalTampil()));
    }

    public function test_hari_libur_tetap_ditampilkan_sebagai_tutup(): void
    {
        $profil = new ProfilDesa([
            'jam_layanan' => '08.00 - 17.00',
            'jam_kunjungan' => "Senin - Kamis 08.00 - 15.00\nJumat 08.00 - 11.30\nSabtu - Minggu Tutup",
        ]);

        $jadwal = $profil->jadwalTampil();

        $this->assertSame([
            'Senin - Kamis = 08.00 - 15.00',
            'Jumat = 08.00 - 11.30',
            'Sabtu - Minggu = Tutup',
        ], $this->ringkas($jadwal));
        $this->assertTrue($jadwal[2]['tutup']);
    }

    public function test_jam_tanpa_nama_hari_berlaku_setiap_hari(): void
    {
        $profil = new ProfilDesa([
            'jam_layanan' => '08.00 - 17.00',
            'jam_kunjungan' => null,
        ]);

        $this->assertSame(['Setiap hari = 08.00 - 17.00'], $this->ringkas($profil->jadwalTampil()));
    }

    public function test_awalan_pelayanan_diabaikan(): void
    {
        $profil = new ProfilDesa([
            'jam_layanan' => 'Pelayanan Senin - Minggu 08.00 - 17.00',
        ]);

        $this->assertSame(['Senin - Minggu = 08.00 - 17.00'], $this->ringkas($profil->jadwalTampil()));
    }

    public function test_jam_kunjungan_tanpa_jam_tidak_menghapus_jam_pelayanan(): void
    {
        // "Senin - Minggu" saja tidak boleh menjadi baris tanpa jam; jamnya
        // harus tetap diambil dari kolom Jam pelayanan.
        $profil = new ProfilDesa([
            'jam_layanan' => '08.00 - 16.00',
            'jam_kunjungan' => 'Senin - Minggu',
        ]);

        foreach ($profil->jadwalTampil() as $baris) {
            $this->assertNotSame('', $baris['jam']);
        }
    }

    public function test_tanpa_data_jadwal_hasilnya_kosong(): void
    {
        $profil = new ProfilDesa(['jam_layanan' => null, 'jam_kunjungan' => null]);

        $this->assertSame([], $profil->jadwalTampil());
    }

    public function test_aksesor_jadwal_layanan_hanya_membaca_jam_pelayanan(): void
    {
        // Dipakai topbar/footer: harus mengikuti kolom Jam pelayanan saja,
        // bukan rincian kunjungan.
        $profil = new ProfilDesa([
            'jam_layanan' => 'Senin - Jumat 08.00 - 15.00',
            'jam_kunjungan' => 'Sabtu - Minggu Tutup',
        ]);

        $this->assertSame(['Senin - Jumat = 08.00 - 15.00'], $this->ringkas($profil->jadwal_layanan));
    }
}
