<?php

namespace App\Enums;

/**
 * Status alur pengajuan surat.
 *
 * Alur: MenungguVerifikasi -> (PerluPerbaikan <-> MenungguVerifikasi)
 *       -> Disetujui -> SedangDiproses -> SiapDiambil -> Selesai
 *       MenungguVerifikasi -> Ditolak
 *
 * Catatan: pengajuan yang sedang "Perlu Perbaikan" tetap boleh langsung
 * diterima admin. Warga memang memperbaiki lewat halaman revisi (statusnya
 * kembali ke MenungguVerifikasi), tetapi petugas juga perlu bisa menerima
 * pengajuan yang ternyata sudah benar — mis. warga membetulkan berkasnya
 * langsung di kantor desa, atau petugas salah menekan "Minta perbaikan".
 */
enum StatusPengajuan: string
{
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case PerluPerbaikan = 'perlu_perbaikan';
    case Ditolak = 'ditolak';
    case Disetujui = 'disetujui';
    case SedangDiproses = 'sedang_diproses';
    case SiapDiambil = 'siap_diambil';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::MenungguVerifikasi => 'Menunggu Verifikasi',
            self::PerluPerbaikan => 'Perlu Perbaikan',
            self::Ditolak => 'Ditolak',
            self::Disetujui => 'Disetujui',
            self::SedangDiproses => 'Sedang Diproses',
            self::SiapDiambil => 'Siap Diambil',
            self::Selesai => 'Selesai',
        };
    }

    /** Kelas badge CSS yang dipakai desain (lihat admin/detail-pengajuan.html). */
    public function badge(): string
    {
        return match ($this) {
            self::MenungguVerifikasi, self::PerluPerbaikan => 'badge-amber',
            self::Ditolak => 'badge-red',
            self::Disetujui, self::SiapDiambil => 'badge-green',
            self::SedangDiproses => 'badge-blue',
            self::Selesai => 'badge-gray',
        };
    }

    /** Status yang dihitung sebagai "menunggu tindakan admin". */
    public static function perluTindakan(): array
    {
        return [self::MenungguVerifikasi, self::PerluPerbaikan];
    }

    /** Transisi status yang diizinkan. */
    public function bolehBerubahKe(): array
    {
        return match ($this) {
            self::MenungguVerifikasi => [self::PerluPerbaikan, self::Ditolak, self::Disetujui],
            // "Perlu Perbaikan" TIDAK menutup kemungkinan diterima: petugas
            // tetap harus bisa menerima pengajuan yang sudah dibetulkan warga
            // (atau salah diminta perbaikan). Tanpa Disetujui di sini, panel
            // "Ubah status" hanya menampilkan Menunggu Verifikasi & Ditolak,
            // sehingga tidak ada jalan untuk menerima pengajuan.
            self::PerluPerbaikan => [self::Disetujui, self::MenungguVerifikasi, self::Ditolak],
            self::Disetujui => [self::SedangDiproses, self::Ditolak],
            self::SedangDiproses => [self::SiapDiambil, self::Ditolak],
            self::SiapDiambil => [self::Selesai],
            self::Ditolak, self::Selesai => [],
        };
    }

    public function bolehPindahKe(self $tujuan): bool
    {
        return in_array($tujuan, $this->bolehBerubahKe(), true);
    }

    public function isSelesai(): bool
    {
        return in_array($this, [self::Ditolak, self::Selesai], true);
    }
}
