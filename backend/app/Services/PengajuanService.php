<?php

namespace App\Services;

use App\Enums\StatusPengajuan;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Support\KodeAkses;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Membuat pengajuan baru + nomor referensi berurutan.
 *
 * Format referensi diambil dari konfigurasi desa (config/desa.php) agar
 * bisa diganti tanpa menyentuh kode, mis. "KRS-2026-0001".
 */
class PengajuanService
{
    /** Jumlah percobaan ulang bila nomor referensi bentrok. */
    private const MAKS_PERCOBAAN = 5;

    /**
     * @param  array<string, mixed>  $data  data pemohon yang sudah tervalidasi
     * @param  array<int, array{persyaratan_surat_id:int|null, label:string, path:string, nama_asli:string, mime:string, ukuran:int}>  $dokumen
     */
    public function buat(JenisSurat $jenisSurat, array $data, array $dokumen, ?string $ip = null): Pengajuan
    {
        $percobaan = 0;

        while (true) {
            $percobaan++;

            try {
                return DB::transaction(function () use ($jenisSurat, $data, $dokumen, $ip) {
                    $pengajuan = new Pengajuan;
                    $pengajuan->fill($data);
                    $pengajuan->jenis_surat_id = $jenisSurat->id;
                    $pengajuan->nomor_referensi = $this->nomorBerikutnya();
                    // Kunci akses warga yang sebenarnya (nomor referensi terlalu
                    // mudah ditebak untuk dijadikan token).
                    $pengajuan->kode_akses = $this->kodeAksesBaru();
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
            } catch (UniqueConstraintViolationException $e) {
                // Dua warga mengirim bersamaan dan mendapat nomor yang sama.
                // Nomor sudah dijamin unique oleh DB, jadi ulangi dengan nomor baru.
                if ($percobaan >= self::MAKS_PERCOBAAN) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Simpan revisi warga: nomor referensi TETAP, status kembali menunggu
     * verifikasi, dan riwayat mencatat bahwa ini kiriman ulang.
     *
     * Dokumen lama yang statusnya "ditolak" dihapus agar tidak menumpuk;
     * dokumen baru ditambahkan sebagai pengganti.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $dokumen
     */
    public function revisi(Pengajuan $pengajuan, array $data, array $dokumen): Pengajuan
    {
        // Berkas lama yang diganti dikumpulkan dulu, baru dihapus dari disk
        // SETELAH transaksi sukses. Bila penghapusan dilakukan di dalam
        // transaksi lalu transaksi batal, baris dokumen kembali ada tetapi
        // berkasnya sudah lenyap — dokumen warga rusak permanen.
        $berkasLama = [];

        $pengajuan = DB::transaction(function () use ($pengajuan, $data, $dokumen, &$berkasLama) {
            $pengajuan->fill($data);

            if (filled($pengajuan->whatsapp)) {
                $pengajuan->whatsapp = $this->normalisasiWhatsapp((string) $pengajuan->whatsapp);
            }

            $pengajuan->revisi_ke = ((int) $pengajuan->revisi_ke) + 1;
            $pengajuan->isiStatus(StatusPengajuan::MenungguVerifikasi);

            // Catatan perbaikan sebelumnya tidak relevan lagi setelah direvisi.
            $pengajuan->catatan_admin = null;
            $pengajuan->diverifikasi_at = null;

            $pengajuan->save();

            // Satu persyaratan hanya boleh punya SATU dokumen aktif. Tanpa
            // pembersihan ini, mengunggah ulang berkas yang sama menumpuk
            // baris dokumen: petugas melihat dua baris untuk satu syarat
            // (yang lama bisa berstatus "ditolak"), sementara halaman revisi
            // membaca yang pertama sehingga warga terus diberi tahu "sudah
            // ada" padahal berkasnya baru saja diganti. Berkas lama juga
            // tertinggal di disk selamanya.
            $diganti = collect($dokumen)
                ->pluck('persyaratan_surat_id')
                ->filter()
                ->all();

            if ($diganti !== []) {
                $usang = $pengajuan->dokumen()
                    ->whereIn('persyaratan_surat_id', $diganti)
                    ->get();

                foreach ($usang as $lama) {
                    $berkasLama[] = ['disk' => $lama->disk ?: null, 'path' => $lama->path];
                    $lama->delete();
                }
            }

            foreach ($dokumen as $d) {
                $pengajuan->dokumen()->create($d);
            }

            $pengajuan->riwayat()->create([
                'status' => StatusPengajuan::MenungguVerifikasi->value,
                'catatan' => 'Warga mengirim perbaikan (revisi ke-'.$pengajuan->revisi_ke.').',
                'aktor' => 'warga',
            ]);

            return $pengajuan->load(['jenisSurat', 'dokumen']);
        });

        foreach ($berkasLama as $lama) {
            if (filled($lama['path'])) {
                Storage::disk($lama['disk'] ?? config('desa.disk_dokumen', 'local'))
                    ->delete($lama['path']);
            }
        }

        return $pengajuan;
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
     * Nomor urut per tahun. Nomor lama bisa lebih dari 4 digit bila melewati
     * 9999 pengajuan setahun, jadi panjang urutan dihitung dari data terakhir
     * (minimal 4 digit) alih-alih selalu memotong 4 karakter dari belakang.
     */
    public function nomorBerikutnya(?int $tahun = null): string
    {
        $tahun ??= (int) Carbon::now()->year;
        $prefix = $this->prefix().'-'.$tahun.'-';

        $terakhir = Pengajuan::withTrashed()
            ->where('nomor_referensi', 'like', $prefix.'%')
            ->orderByRaw('LENGTH(nomor_referensi) DESC')
            ->orderByDesc('nomor_referensi')
            ->value('nomor_referensi');

        $urut = 0;

        if ($terakhir !== null) {
            $urut = (int) substr($terakhir, strlen($prefix));
        }

        return $prefix.str_pad((string) ($urut + 1), 4, '0', STR_PAD_LEFT);
    }

    /** Awalan nomor referensi desa, mis. "KRS". */
    public function prefix(): string
    {
        $prefix = (string) config('desa.prefix_referensi', 'DS');

        return $prefix !== '' ? $prefix : 'DS';
    }

    /**
     * Kode akses acak yang belum dipakai pengajuan lain.
     *
     * Kolomnya diberi indeks unik, jadi bentrok tetap tertangkap DB; pengecekan
     * di sini hanya membuat tabrakan praktis tidak pernah terjadi.
     */
    public function kodeAksesBaru(): string
    {
        do {
            $kode = KodeAkses::acak();
        } while (Pengajuan::withTrashed()->where('kode_akses', $kode)->exists());

        return $kode;
    }
}
