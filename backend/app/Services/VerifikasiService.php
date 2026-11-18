<?php

namespace App\Services;

use App\Enums\StatusPengajuan;
use App\Models\DokumenPengajuan;
use App\Models\LogAktivitas;
use App\Models\Pengajuan;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Perpindahan status pengajuan oleh admin, lengkap dengan riwayat + log.
 */
class VerifikasiService
{
    /**
     * Pindahkan status pengajuan dan catat audit trail.
     *
     * @throws DomainException bila transisi tidak diizinkan
     */
    public function ubahStatus(
        Pengajuan $pengajuan,
        StatusPengajuan $tujuan,
        User $admin,
        ?string $catatan = null,
    ): Pengajuan {
        return DB::transaction(function () use ($pengajuan, $tujuan, $admin, $catatan) {
            // Status dibaca ULANG di dalam transaksi dengan baris dikunci.
            //
            // Tanpa ini, pemeriksaan transisi memakai status yang dipegang
            // instance model — dan instance itu bisa sudah basi: dua petugas
            // membuka halaman yang sama, petugas pertama menyetujui, lalu
            // petugas kedua (masih memegang status "Menunggu Verifikasi")
            // menekan "Perlu Perbaikan". Hasilnya pengajuan yang sudah
            // disetujui mundur menjadi perlu perbaikan, padahal transisi itu
            // tidak sah dari "Disetujui" — dan warga menerima notifikasi yang
            // membingungkan. Hal yang sama terjadi bila satu petugas menekan
            // tombol dua kali.
            $terkunci = Pengajuan::query()
                ->whereKey($pengajuan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $asal = $terkunci->status;

            if (! $asal->bolehPindahKe($tujuan)) {
                throw new DomainException(
                    "Status tidak dapat berubah dari {$asal->label()} ke {$tujuan->label()}."
                );
            }

            // Instance milik pemanggil disegarkan agar pemakaian berikutnya
            // (mis. pesan "status diperbarui menjadi ...") tidak memakai
            // nilai lama.
            $pengajuan->setRawAttributes($terkunci->getAttributes(), true);

            $pengajuan->isiStatus($tujuan);
            $pengajuan->catatan_admin = $catatan;
            $pengajuan->diproses_oleh = $admin->id;

            if (in_array($tujuan, [StatusPengajuan::Disetujui, StatusPengajuan::Ditolak, StatusPengajuan::PerluPerbaikan], true)) {
                $pengajuan->diverifikasi_at = now();
            }

            if ($tujuan === StatusPengajuan::Selesai) {
                $pengajuan->selesai_at = now();
            }

            $pengajuan->save();

            $pengajuan->riwayat()->create([
                'status' => $tujuan->value,
                'catatan' => $catatan,
                'user_id' => $admin->id,
                'aktor' => 'admin',
            ]);

            LogAktivitas::catat(
                tindakan: 'Mengubah status pengajuan menjadi '.$tujuan->label(),
                subjek: $pengajuan->nomor_referensi,
                tingkat: $tujuan === StatusPengajuan::Ditolak ? LogAktivitas::PERINGATAN : LogAktivitas::SUKSES,
                keterangan: $catatan,
                user: $admin,
            );

            return $pengajuan->refresh();
        });
    }

    /** Tandai satu dokumen terverifikasi / ditolak. */
    public function verifikasiDokumen(DokumenPengajuan $dokumen, string $status, User $admin): DokumenPengajuan
    {
        if (! in_array($status, [DokumenPengajuan::TERVERIFIKASI, DokumenPengajuan::DITOLAK], true)) {
            throw new DomainException('Status dokumen tidak dikenal.');
        }

        $dokumen->status_verifikasi = $status;
        $dokumen->save();

        LogAktivitas::catat(
            tindakan: 'Verifikasi dokumen '.$dokumen->label,
            subjek: $dokumen->pengajuan?->nomor_referensi,
            tingkat: $status === DokumenPengajuan::TERVERIFIKASI ? LogAktivitas::SUKSES : LogAktivitas::PERINGATAN,
            user: $admin,
        );

        return $dokumen;
    }
}
