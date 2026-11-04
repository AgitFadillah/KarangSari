<?php

namespace Database\Seeders;

use App\Models\JenisSurat;
use Illuminate\Database\Seeder;

/**
 * Enam jenis surat, disamakan dengan arsip desain pelayanan.html
 * dan objek JS LETTERS pada pengajuan.html.
 */
class JenisSuratSeeder extends Seeder
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $data = [
        [
            'slug' => 'domisili',
            'kode' => 'SKD',
            'kode_layanan' => '470',
            'nama' => 'Surat Keterangan Domisili',
            'deskripsi' => 'Keterangan resmi tempat tinggal warga yang digunakan untuk keperluan sekolah, pekerjaan, perbankan, atau administrasi kependudukan.',
            'estimasi_hari' => '1 hari kerja',
            'urutan' => 1,
            'persyaratan' => [
                ['KTP pemohon', 'KTP asli difoto atau dipindai.'],
                ['Kartu Keluarga', 'Halaman yang memuat nama pemohon.'],
            ],
        ],
        [
            'slug' => 'usaha',
            'kode' => 'SKU',
            'kode_layanan' => '471',
            'nama' => 'Surat Keterangan Usaha',
            'deskripsi' => 'Keterangan kepemilikan dan lokasi usaha untuk pengajuan kredit, izin usaha mikro, atau kemitraan dengan pihak ketiga.',
            'estimasi_hari' => '2 hari kerja',
            'urutan' => 2,
            'persyaratan' => [
                ['KTP pemohon', 'KTP asli difoto atau dipindai.'],
                ['Kartu Keluarga', 'Halaman yang memuat nama pemohon.'],
                ['Foto tempat usaha', 'Tampak depan lokasi usaha yang dijalankan.'],
            ],
        ],
        [
            'slug' => 'tidak-mampu',
            'kode' => 'SKTM',
            'kode_layanan' => '472',
            'nama' => 'Surat Keterangan Tidak Mampu',
            'deskripsi' => 'Keterangan kondisi ekonomi untuk keperluan beasiswa, bantuan sosial, atau keringanan biaya layanan kesehatan.',
            'estimasi_hari' => '2 hari kerja',
            'urutan' => 3,
            'persyaratan' => [
                ['KTP pemohon', 'KTP asli difoto atau dipindai.'],
                ['Kartu Keluarga', 'Halaman yang memuat nama pemohon.'],
                ['Surat pengantar RT/RW', 'Pengantar permohonan dari lingkungan warga.'],
            ],
        ],
        [
            'slug' => 'ktp',
            'kode' => 'SPKTP',
            'kode_layanan' => '473',
            'nama' => 'Surat Pengantar KTP',
            'deskripsi' => 'Pengantar dari desa untuk pembuatan KTP baru, perpanjangan, atau penggantian akibat perubahan data.',
            'estimasi_hari' => '1 hari kerja',
            'urutan' => 4,
            'persyaratan' => [
                ['Kartu Keluarga', 'Halaman yang memuat data pemohon.'],
                ['KTP lama (bila ada)', 'Untuk pencocokan data sebelumnya.'],
            ],
        ],
        [
            'slug' => 'kematian',
            'kode' => 'SKK',
            'kode_layanan' => '474',
            'nama' => 'Surat Keterangan Kematian',
            'deskripsi' => 'Keterangan resmi peristiwa kematian warga untuk pengurusan akta kematian dan administrasi warisan.',
            'estimasi_hari' => '1 hari kerja',
            'urutan' => 5,
            'persyaratan' => [
                ['KTP pelapor', 'Identitas pelapor peristiwa.'],
                ['Kartu Keluarga', 'Halaman yang memuat data almarhum atau almarhumah.'],
                ['Surat keterangan fasilitas kesehatan', 'Keterangan kematian dari bidan, dokter, atau rumah sakit.'],
            ],
        ],
        [
            'slug' => 'keramaian',
            'kode' => 'SIK',
            'kode_layanan' => '475',
            'nama' => 'Surat Izin Keramaian',
            'deskripsi' => 'Izin penyelenggaraan kegiatan yang menghadirkan banyak orang, seperti hajatan, pentas seni, atau kegiatan keagamaan.',
            'estimasi_hari' => '3 hari kerja',
            'urutan' => 6,
            'persyaratan' => [
                ['KTP pemohon', 'KTP asli difoto atau dipindai.'],
                ['Surat pernyataan penyelenggara', 'Pernyataan kesanggupan menaati aturan desa.'],
            ],
        ],
    ];

    public function run(): void
    {
        foreach ($this->data as $item) {
            $persyaratan = $item['persyaratan'];
            unset($item['persyaratan']);

            $jenisSurat = JenisSurat::query()->updateOrCreate(
                ['slug' => $item['slug']],
                $item + [
                    'is_aktif' => true,
                    'template_surat' => $this->template($item['nama']),
                ]
            );

            $jenisSurat->persyaratan()->delete();

            foreach ($persyaratan as $urutan => [$label, $petunjuk]) {
                $jenisSurat->persyaratan()->create([
                    'label' => $label,
                    'petunjuk' => $petunjuk,
                    'urutan' => $urutan + 1,
                ]);
            }
        }
    }

    protected function template(string $nama): string
    {
        return "Yang bertanda tangan di bawah ini, Kepala Desa Sukamaju, menerangkan bahwa:\n\n"
            ."Nama lengkap   : {nama}\n"
            ."NIK            : {nik}\n"
            ."Tempat lahir   : {tempat_lahir}\n"
            ."Tanggal lahir  : {tanggal_lahir}\n"
            ."Jenis kelamin  : {jenis_kelamin}\n"
            ."Alamat         : {alamat}\n\n"
            ."Surat keterangan ini dibuat untuk keperluan {keperluan}.\n\n"
            ."Demikian {$nama} ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.";
    }
}
