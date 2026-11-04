<?php

namespace Database\Seeders;

use App\Models\KontenWebsite;
use Illuminate\Database\Seeder;

/**
 * Lima bagian konten website, mengikuti daftar pada arsip desain admin/konten.html.
 */
class KontenWebsiteSeeder extends Seeder
{
    /**
     * @var array<int, array{bagian: string, judul: string, isi: string, status: string}>
     */
    protected array $data = [
        [
            'bagian' => 'beranda',
            'judul' => 'Selamat Datang di Desa Sukamaju',
            'isi' => 'Selamat datang di portal resmi Desa Sukamaju. Temukan informasi layanan, berita, dan kontak desa di sini.',
            'status' => KontenWebsite::TERBIT,
        ],
        [
            'bagian' => 'profil',
            'judul' => 'Profil Desa Sukamaju',
            'isi' => 'Desa Sukamaju berada di Kecamatan Cisarua, Kabupaten Bogor, dengan wilayah pertanian dan perkebunan yang menjadi tulang punggung ekonomi warga.',
            'status' => KontenWebsite::TERBIT,
        ],
        [
            'bagian' => 'pelayanan',
            'judul' => 'Layanan Administrasi Desa',
            'isi' => 'Enam jenis surat dapat diajukan secara daring tanpa membuat akun. Seluruh layanan tidak dipungut biaya tambahan.',
            'status' => KontenWebsite::TERBIT,
        ],
        [
            'bagian' => 'informasi',
            'judul' => 'Informasi Terbaru Desa',
            'isi' => 'Pengumuman penerimaan bantuan sosial tahap 2 dan jadwal posyandu bulan November.',
            'status' => KontenWebsite::DRAF,
        ],
        [
            'bagian' => 'kontak',
            'judul' => 'Hubungi Kami',
            'isi' => 'Kantor desa melayani Senin–Jumat pukul 08.00–15.00 WIB melalui telepon, WhatsApp, atau kunjungan langsung.',
            'status' => KontenWebsite::TERBIT,
        ],
    ];

    public function run(): void
    {
        foreach ($this->data as $item) {
            KontenWebsite::query()->updateOrCreate(
                ['bagian' => $item['bagian']],
                $item
            );
        }
    }
}
