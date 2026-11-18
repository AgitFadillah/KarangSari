<?php

namespace Database\Seeders;

use App\Models\KontenWebsite;
use Illuminate\Database\Seeder;

/**
 * Lima bagian konten website. Teks di sini hanya contoh awal; perangkat desa
 * mengubahnya lewat menu Konten Website di dashboard admin.
 *
 * Halaman publik membaca isi ini lewat KontenWebsite::ambil($bagian).
 */
class KontenWebsiteSeeder extends Seeder
{
    /**
     * @var array<int, array{bagian: string, judul: string, isi: string, status: string}>
     */
    protected array $data = [
        [
            'bagian' => 'beranda',
            'judul' => 'Selamat datang di situs resmi desa',
            'isi' => 'Temukan informasi layanan, berita, dan kontak kantor desa di sini. Ubah teks ini melalui menu Konten Website.',
            'status' => KontenWebsite::TERBIT,
        ],
        [
            'bagian' => 'profil',
            'judul' => 'Profil desa',
            'isi' => 'Isi sejarah, visi, dan misi desa melalui menu Profil & Kontak di dashboard admin.',
            'status' => KontenWebsite::TERBIT,
        ],
        [
            'bagian' => 'pelayanan',
            'judul' => 'Layanan administrasi desa',
            'isi' => 'Seluruh layanan surat dapat diajukan secara daring tanpa membuat akun dan tidak dipungut biaya.',
            'status' => KontenWebsite::TERBIT,
        ],
        [
            'bagian' => 'informasi',
            'judul' => 'Informasi terbaru desa',
            'isi' => 'Belum ada pengumuman. Tambahkan melalui menu Pengumuman di dashboard admin.',
            'status' => KontenWebsite::DRAF,
        ],
        [
            'bagian' => 'kontak',
            'judul' => 'Hubungi kami',
            'isi' => 'Lengkapi nomor telepon, WhatsApp, email, dan jam layanan melalui menu Profil & Kontak.',
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
