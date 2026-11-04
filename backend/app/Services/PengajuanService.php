<?php

namespace App\Services;

use App\Enums\StatusPengajuan;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Membuat pengajuan baru + nomor referensi berurutan.
 *
 * Format referensi mengikuti prototipe: SBM-YYYY-NNNN
 * (SBM = Sukamaju, lihat admin/ringkasan.html: SBM-2026-0008).
 */
class PengajuanService
{
    /**
     * @param  array<string, mixed>  $data  data pemohon yang sudah tervalidasi
     * @param  array<int, array{persyaratan_surat_id:int|null, label:string, path:string, nama_asli:string, mime:string, ukuran:int}>  $dokumen
     */
    public function buat(JenisSurat $jenisSurat, array $data, array $dokumen, ?string $ip = null): Pengajuan
    {
        return DB::transaction(function () use ($jenisSurat, $data, $dokumen, $ip) {
            $pengajuan = new Pengajuan;
            $pengajuan->fill($data);
            $pengajuan->jenis_surat_id = $jenisSurat->id;
            $pengajuan->nomor_referensi = $this->nomorBerikutnya();
            $pengajuan->persetujuan_at = now();
            $pengajuan->ip_pemohon = $ip;

            if (filled($pengajuan->whatsapp)) {
                $pengajuan->whatsapp = $this->normalisasiWhatsapp((string) $pengajuan->whatsapp);
            }

            $pengajuan->isiStatus(StatusPengajuan::MenungguVerifikasi);
            $pengajuan->save();

            foreach ($dokumen as $d) {
                $pengajuan->dokumen()->create($d);
            }

            $pengajuan->riwayat()->create([
                'status' => StatusPengajuan::MenungguVerifikasi->value,
                'catatan' => 'Pengajuan diterima dan menunggu verifikasi admin.',
                'aktor' => 'warga',
            ]);

            return $pengajuan->load(['jenisSurat', 'dokumen']);
        });
    }

    /**
     * Simpan WhatsApp dalam satu format: 08xxxxxxxxxx.
     */
    public function normalisasiWhatsapp(string $nomor): string
    {
        $nomor = preg_replace('/[^0-9]/', '', $nomor) ?? '';

        if (str_starts_with($nomor, '62')) {
            $nomor = '0'.substr($nomor, 2);
        } elseif (str_starts_with($nomor, '8')) {
            $nomor = '0'.$nomor;
        }

        return $nomor;
    }

    /**
     * Nomor urut per tahun, aman dari bentrok karena dibungkus transaksi
     * dan kolomnya unique (retry ditangani pemanggil lewat DB).
     */
    public function nomorBerikutnya(?int $tahun = null): string
    {
        $tahun ??= (int) Carbon::now()->year;
        $prefix = sprintf('SBM-%d-', $tahun);

        $terakhir = Pengajuan::withTrashed()
            ->where('nomor_referensi', 'like', $prefix.'%')
            ->orderByDesc('nomor_referensi')
            ->value('nomor_referensi');

        $urut = $terakhir ? ((int) substr($terakhir, -4)) + 1 : 1;

        return $prefix.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }
}
