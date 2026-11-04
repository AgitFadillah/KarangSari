<?php

namespace Database\Seeders;

use App\Models\ProfilDesa;
use Illuminate\Database\Seeder;

/**
 * Data identitas desa. Sumber: arsip desain admin/profil-kontak.html.
 */
class ProfilDesaSeeder extends Seeder
{
    public function run(): void
    {
        ProfilDesa::query()->updateOrCreate(
            ['id' => 1],
            [
                'nama_desa' => 'Desa Sukamaju',
                'kecamatan' => 'Cisarua',
                'kabupaten' => 'Kabupaten Bogor',
                'provinsi' => 'Jawa Barat',
                'kode_pos' => '16750',
                'alamat_kantor' => 'Jl. Raya Puncak No. 24, Desa Sukamaju, Kec. Cisarua, Kab. Bogor, Jawa Barat 16750',
                'telepon' => '(0251) 830 1122',
                'whatsapp' => '081234567890',
                'email' => 'kantorpemdesa@sukamaju.desa.id',
                'jam_layanan' => 'Senin–Jumat, 08.00–15.00 WIB',
                'jam_kunjungan' => 'Senin–Kamis 08.00–15.00, Jumat 08.00–11.30, Sabtu–Minggu tutup',
                'kueri_peta' => 'Desa Sukamaju Cisarua Bogor',
                'sejarah' => 'Desa Sukamaju berdiri pada awal abad ke-20 sebagai permukiman petani di lereng selatan Gunung Gede. Nama Sukamaju dipilih dari musyawarah warga yang berharap desa ini tumbuh makmur.',
                'visi' => 'Mewujudkan Desa Sukamaju yang mandiri, sejahtera, dan berbudaya melalui pelayanan publik yang terbuka serta pengelolaan potensi lokal yang berkelanjutan.',
                'misi' => implode("\n", [
                    'Meningkatkan kualitas pelayanan administrasi desa yang cepat, terbuka, dan tanpa biaya tambahan.',
                    'Mengembangkan potensi pertanian, perkebunan, dan wisata desa secara berkelanjutan.',
                    'Memperkuat partisipasi warga dalam perencanaan dan pengawasan pembangunan desa.',
                    'Meningkatkan kualitas pendidikan, kesehatan, dan pelestarian budaya lokal.',
                ]),
            ]
        );
    }
}
