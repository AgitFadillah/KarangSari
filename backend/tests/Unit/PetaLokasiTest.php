<?php

namespace Tests\Unit;

use App\Models\ProfilDesa;
use PHPUnit\Framework\TestCase;

/**
 * Peta lokasi di beranda dan halaman kontak memakai sematan Google Maps.
 * Kueri peta boleh dikosongkan perangkat desa; dalam kondisi itu alamat
 * kantor + wilayah desa dipakai supaya peta tetap menunjuk lokasi yang
 * benar. Tombol WhatsApp juga harus dinormalkan agar tidak mengarah ke
 * tautan kosong saat nomornya belum diisi.
 */
class PetaLokasiTest extends TestCase
{
    public function test_kueri_peta_dari_admin_dipakai_bila_diisi(): void
    {
        $profil = new ProfilDesa([
            'kueri_peta' => 'Balai Desa Karang Sari',
            'alamat_kantor' => 'Jl. RA. Basyid',
            'nama_desa' => 'Desa Karang Sari',
        ]);

        $this->assertSame('Balai Desa Karang Sari', $profil->kueriPeta());
    }

    /**
     * Nama "Balai Desa Karang Sari" cukup umum. Bila admin mengisinya, wilayah
     * kecamatan/kabupaten tetap ditambahkan agar Google Maps tidak memilih
     * desa bernama sama di kabupaten lain.
     */
    public function test_kueri_peta_yang_diisi_diperjelas_dengan_wilayah(): void
    {
        $profil = new ProfilDesa([
            'kueri_peta' => 'Balai Desa Karang Sari',
            'nama_desa' => 'Desa Karang Sari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => 'Lampung Selatan',
            'provinsi' => 'Lampung',
        ]);

        $this->assertSame(
            'Balai Desa Karang Sari, Kecamatan Jati Agung, Lampung Selatan',
            $profil->kueriPeta(),
        );
    }

    public function test_kueri_peta_kosong_jatuh_ke_alamat_dan_wilayah(): void
    {
        $profil = new ProfilDesa([
            'kueri_peta' => null,
            'alamat_kantor' => 'Jl. RA. Basyid',
            'nama_desa' => 'Desa Karang Sari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => 'Lampung Selatan',
            'provinsi' => 'Lampung',
        ]);

        $this->assertSame(
            'Jl. RA. Basyid, Desa Karang Sari, Kecamatan Jati Agung, Lampung Selatan',
            $profil->kueriPeta(),
        );
    }

    /** Bagian yang sudah ada di alamat kantor tidak diulang di kueri peta. */
    public function test_kueri_peta_tidak_mengulang_wilayah_yang_sudah_ada_di_alamat(): void
    {
        $profil = new ProfilDesa([
            'kueri_peta' => null,
            'alamat_kantor' => 'Jl. RA. Basyid Desa Karang Sari Kecamatan Jati Agung Kabupaten Lampung Selatan',
            'nama_desa' => 'Desa Karang Sari',
            'kecamatan' => 'Jati Agung',
            'kabupaten' => 'Lampung Selatan',
            'provinsi' => 'Lampung',
        ]);

        $this->assertSame(
            'Jl. RA. Basyid Desa Karang Sari Kecamatan Jati Agung Kabupaten Lampung Selatan',
            $profil->kueriPeta(),
        );
    }

    public function test_kueri_peta_tanpa_alamat_memakai_nama_desa(): void
    {
        $profil = new ProfilDesa(['nama_desa' => 'Desa Karang Sari']);

        $this->assertSame('Desa Karang Sari', $profil->kueriPeta());
    }

    public function test_embed_peta_memakai_mode_sematan_google_maps(): void
    {
        $profil = new ProfilDesa(['kueri_peta' => 'Kantor Desa Karang Sari']);

        $url = $profil->embedPeta();

        $this->assertStringStartsWith('https://www.google.com/maps?q=', $url);
        $this->assertStringContainsString('output=embed', $url);
        $this->assertStringContainsString(rawurlencode('Kantor Desa Karang Sari'), $url);
        // Tampilan satelit dengan zoom dekat seperti referensi pengelola desa.
        $this->assertStringContainsString('t=k', $url);
        $this->assertStringContainsString('z=17', $url);
    }

    public function test_tautan_peta_memakai_pencarian_maps(): void
    {
        $profil = new ProfilDesa(['kueri_peta' => 'Kantor Desa Karang Sari']);

        $this->assertSame(
            'https://www.google.com/maps/search/?api=1&query='.rawurlencode('Kantor Desa Karang Sari'),
            $profil->tautanPeta(),
        );
    }

    public function test_nomor_whatsapp_dinormalkan_ke_angka_saja(): void
    {
        $this->assertSame('081234567890', (new ProfilDesa(['whatsapp' => '0812-3456-7890']))->nomorWhatsapp());
        $this->assertSame('6281234567890', (new ProfilDesa(['whatsapp' => '+62 812 3456 7890']))->nomorWhatsapp());
    }

    public function test_nomor_whatsapp_kosong_menghasilkan_null(): void
    {
        $this->assertNull((new ProfilDesa(['whatsapp' => null]))->nomorWhatsapp());
        $this->assertNull((new ProfilDesa(['whatsapp' => '   ']))->nomorWhatsapp());
        $this->assertNull((new ProfilDesa(['whatsapp' => '—']))->nomorWhatsapp());
    }
}
