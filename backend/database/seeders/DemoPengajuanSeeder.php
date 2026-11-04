<?php

namespace Database\Seeders;

use App\Enums\StatusPengajuan;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Delapan pengajuan contoh (SBM-2026-0001..0008) dengan status bervariasi
 * agar dashboard dan daftar pengajuan punya isi saat demo.
 */
class DemoPengajuanSeeder extends Seeder
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $data = [
        ['slug' => 'domisili', 'nama' => 'Agus Setiawan', 'nik' => '3201120000000001', 'jk' => 'Laki-laki', 'lahir' => '1987-01-09', 'keperluan' => 'Persyaratan pembukaan rekening bank', 'status' => StatusPengajuan::Selesai, 'hari' => 18],
        ['slug' => 'tidak-mampu', 'nama' => 'Nurhayati', 'nik' => '3201120000000002', 'jk' => 'Perempuan', 'lahir' => '1983-04-08', 'keperluan' => 'Keringanan biaya pengobatan di puskesmas', 'status' => StatusPengajuan::Selesai, 'hari' => 20],
        ['slug' => 'ktp', 'nama' => 'Samsul Arifin', 'nik' => '3201120000000003', 'jk' => 'Laki-laki', 'lahir' => '1980-07-03', 'keperluan' => 'Pengantar penerbitan KTP baru', 'status' => StatusPengajuan::Ditolak, 'hari' => 22, 'catatan' => 'NIK tidak cocok dengan Kartu Keluarga.'],
        ['slug' => 'keramaian', 'nama' => 'Dedi Kurniawan', 'nik' => '3201120000000004', 'jk' => 'Laki-laki', 'lahir' => '1988-06-14', 'keperluan' => 'Izin penyelenggaraan hajatan pernikahan', 'status' => StatusPengajuan::SiapDiambil, 'hari' => 24],
        ['slug' => 'domisili', 'nama' => 'Rina Marlina', 'nik' => '3201120000000005', 'jk' => 'Perempuan', 'lahir' => '1992-09-05', 'keperluan' => 'Persyaratan administrasi kepesertaan BPJS', 'status' => StatusPengajuan::SedangDiproses, 'hari' => 27],
        ['slug' => 'tidak-mampu', 'nama' => 'Ahmad Fauzi', 'nik' => '3201120000000006', 'jk' => 'Laki-laki', 'lahir' => '1985-07-10', 'keperluan' => 'Keringanan biaya sekolah anak', 'status' => StatusPengajuan::Disetujui, 'hari' => 28],
        ['slug' => 'usaha', 'nama' => 'Siti Aminah', 'nik' => '3201120000000007', 'jk' => 'Perempuan', 'lahir' => '1990-09-12', 'keperluan' => 'Pengajuan izin usaha warung kelontong', 'status' => StatusPengajuan::PerluPerbaikan, 'hari' => 29, 'catatan' => 'Foto tempat usaha buram, mohon unggah ulang.'],
        ['slug' => 'domisili', 'nama' => 'Budi Santoso', 'nik' => '3201120000000008', 'jk' => 'Laki-laki', 'lahir' => '1987-08-15', 'keperluan' => 'Keterangan domisili untuk administrasi sekolah', 'status' => StatusPengajuan::MenungguVerifikasi, 'hari' => 30],
    ];

    public function run(): void
    {
        $jenis = JenisSurat::query()->get()->keyBy('slug');
        $verifikator = User::query()->where('username', 'rangga')->first();

        foreach ($this->data as $index => $item) {
            $tanggal = Carbon::create(now()->year, 10, $item['hari'], 9, 14);

            $pengajuan = Pengajuan::query()->updateOrCreate(
                ['nomor_referensi' => sprintf('SBM-%d-%04d', now()->year, $index + 1)],
                [
                    'jenis_surat_id' => $jenis[$item['slug']]->id,
                    'nama' => $item['nama'],
                    'nik' => $item['nik'],
                    'tempat_lahir' => 'Bogor',
                    'tanggal_lahir' => $item['lahir'],
                    'jenis_kelamin' => $item['jk'],
                    'alamat' => 'Kp. Pasir Angin RT 02/RW 05',
                    'keperluan' => $item['keperluan'],
                    'whatsapp' => '08123456'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'persetujuan_data' => true,
                    'persetujuan_at' => $tanggal,
                    'ip_pemohon' => '127.0.0.1',
                    'status' => $item['status']->value,
                    'catatan_admin' => $item['catatan'] ?? null,
                    'diproses_oleh' => $item['status'] === StatusPengajuan::MenungguVerifikasi ? null : $verifikator?->id,
                    'diverifikasi_at' => $item['status'] === StatusPengajuan::MenungguVerifikasi ? null : $tanggal->copy()->addDay(),
                    'selesai_at' => $item['status'] === StatusPengajuan::Selesai ? $tanggal->copy()->addDays(3) : null,
                    'created_at' => $tanggal,
                    'updated_at' => $tanggal,
                ]
            );

            $pengajuan->riwayat()->delete();

            $pengajuan->riwayat()->create([
                'status' => StatusPengajuan::MenungguVerifikasi->value,
                'catatan' => 'Pengajuan diterima dan menunggu verifikasi admin.',
                'aktor' => 'warga',
                'created_at' => $tanggal,
                'updated_at' => $tanggal,
            ]);

            if ($item['status'] !== StatusPengajuan::MenungguVerifikasi) {
                $pengajuan->riwayat()->create([
                    'status' => $item['status']->value,
                    'catatan' => $item['catatan'] ?? 'Diperbarui oleh admin desa.',
                    'user_id' => $verifikator?->id,
                    'aktor' => 'admin',
                    'created_at' => $tanggal->copy()->addDay(),
                    'updated_at' => $tanggal->copy()->addDay(),
                ]);
            }
        }
    }
}
