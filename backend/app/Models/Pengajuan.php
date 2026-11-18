<?php

namespace App\Models;

use App\Enums\StatusPengajuan;
use App\Support\KodeAkses;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'jenis_surat_id', 'nama', 'nik', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
    'alamat', 'keperluan', 'isian_surat', 'whatsapp', 'persetujuan_data', 'persetujuan_at', 'ip_pemohon',
])]
// Kode akses tidak pernah ikut diserialisasikan (toArray/toJson) agar tidak
// bocor lewat respons JSON mana pun.
#[Hidden(['kode_akses'])]
class Pengajuan extends Model
{
    use SoftDeletes;

    protected $table = 'pengajuans';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'persetujuan_at' => 'datetime',
            'diverifikasi_at' => 'datetime',
            'selesai_at' => 'datetime',
            'persetujuan_data' => 'boolean',
            'isian_surat' => 'array',
            'status' => StatusPengajuan::class,
        ];
    }

    /** Status diisi lewat service, bukan mass-assign langsung. */
    public function isiStatus(StatusPengajuan $status): void
    {
        $this->status = $status;
    }

    public function jenisSurat(): BelongsTo
    {
        return $this->belongsTo(JenisSurat::class);
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(DokumenPengajuan::class);
    }

    public function riwayat(): HasMany
    {
        // Urut berdasarkan waktu, lalu id sebagai penentu akhir: beberapa
        // baris riwayat sering dibuat dalam detik yang sama sehingga
        // "created_at" saja tidak cukup dan urutannya bisa berubah-ubah.
        return $this->hasMany(RiwayatPengajuan::class)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    /** NIK disimpan utuh, tetapi ditampilkan tersamar di daftar. */
    public function nikTersamar(): ?string
    {
        $nik = (string) $this->nik;

        if ($nik === '') {
            return null;
        }

        return strlen($nik) === 16
            ? substr($nik, 0, 6).'******'.substr($nik, -4)
            : $nik;
    }

    /** Apakah pengajuan sedang menunggu perbaikan dari warga. */
    public function perluPerbaikan(): bool
    {
        return $this->status === StatusPengajuan::PerluPerbaikan;
    }

    /**
     * Apakah surat jadi boleh diunduh warga.
     *
     * Surat baru sah setelah disetujui. Penting: tahap SESUDAH disetujui
     * (Sedang Diproses, Siap Diambil, Selesai) juga harus tetap boleh diunduh.
     * Sebelumnya hanya "Disetujui" dan "Selesai" yang diizinkan, sehingga
     * begitu petugas memajukan status ke "Sedang Diproses", tombol unduh
     * hilang dan warga kehilangan akses ke suratnya sendiri.
     */
    public function suratBolehDiunduh(): bool
    {
        return in_array($this->status, [
            StatusPengajuan::Disetujui,
            StatusPengajuan::SedangDiproses,
            StatusPengajuan::SiapDiambil,
            StatusPengajuan::Selesai,
        ], true);
    }

    /**
     * Apakah kode akses yang diberikan warga cocok dengan pengajuan ini.
     *
     * Nomor referensi TIDAK dipakai di sini: itu hanya penanda komunikasi,
     * bukan kunci. Perbandingan memakai hash_equals agar tahan timing attack.
     */
    public function kodeAksesCocok(?string $kode): bool
    {
        $kode = KodeAkses::normalisasi($kode);

        return $kode !== ''
            && filled($this->kode_akses)
            && hash_equals((string) $this->kode_akses, $kode);
    }

    /**
     * Surat jadi siap cetak / pratinjau.
     */
    public function suratTersusun(): string
    {
        return app(\App\Services\TemplateSuratService::class)
            ->susun($this->jenisSurat, $this, (array) ($this->isian_surat ?? []));
    }

    /**
     * Isi surat resmi desa sebagai teks, diambil dari dokumen Word yang sama
     * dengan yang diunduh petugas.
     *
     * Pratinjau di layar memakai sumber yang sama dengan berkas .docx supaya
     * yang dilihat petugas persis sama dengan yang akan tercetak — bukan tiruan
     * teks dari template formulir.
     */
    public function suratResmiTeks(): string
    {
        return app(\App\Services\SuratWordService::class)
            ->teks($this->jenisSurat, $this, (array) ($this->isian_surat ?? []));
    }

    /**
     * Nomor WhatsApp dalam format internasional tanpa tanda plus (62...),
     * yaitu format yang dipakai tautan wa.me. Nomor tersimpan sudah
     * dinormalkan menjadi 08xxxxxxxxxx oleh PengajuanService.
     */
    public function whatsappInternasional(): string
    {
        $nomor = preg_replace('/\D/', '', (string) $this->whatsapp) ?? '';

        if ($nomor === '') {
            return '';
        }

        if (str_starts_with($nomor, '62')) {
            return $nomor;
        }

        if (str_starts_with($nomor, '0')) {
            return '62'.substr($nomor, 1);
        }

        if (str_starts_with($nomor, '8')) {
            return '62'.$nomor;
        }

        return $nomor;
    }

    /**
     * Tautan chat WhatsApp ke warga, langsung membuka percakapan dengan pesan
     * awal berisi nomor referensi supaya petugas tidak perlu menyalin nomor
     * lalu mencari kontaknya secara manual.
     */
    public function tautanWhatsapp(?string $pesan = null): ?string
    {
        $nomor = $this->whatsappInternasional();

        if ($nomor === '') {
            return null;
        }

        $pesan ??= 'Halo '.$this->nama.', kami dari Pemerintah '.config('desa.nama_desa', 'Desa')
            .' mengenai pengajuan surat Anda. Nomor referensi: '.$this->nomor_referensi
            .($this->kode_akses ? '. Kode akses: '.$this->kode_akses : '')
            .'. Simpan nomor dan kode ini untuk memantau status surat di situs desa.';

        return 'https://wa.me/'.$nomor.'?text='.rawurlencode($pesan);
    }

    public function scopePerluTindakan(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(
            fn (StatusPengajuan $s) => $s->value,
            StatusPengajuan::perluTindakan()
        ));
    }

    public function scopeCari(Builder $query, ?string $kata): Builder
    {
        $kata = \App\Support\Pencarian::siapkan($kata);

        if ($kata === null) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($kata) {
            \App\Support\Pencarian::cocok($q, ['nomor_referensi', 'nama', 'whatsapp'], $kata);

            // NIK wajib diisi pada formulir, tetapi jangan menganggap semua
            // kata pencarian berupa angka. NIK dicocokkan dari awal agar
            // tidak perlu memindai seluruh kolom.
            if (ctype_digit($kata)) {
                \App\Support\Pencarian::awalan($q, 'nik', $kata, 'or');
            }

            $q->orWhereHas(
                'jenisSurat',
                fn (Builder $j) => \App\Support\Pencarian::cocok($j, 'nama', $kata)
            );
        });
    }
}
