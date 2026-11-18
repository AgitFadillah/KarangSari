<?php

namespace App\Http\Requests;

use App\Models\JenisSurat;
use App\Services\TemplateSuratService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi formulir pengajuan surat warga.
 *
 * Semua dokumen resmi desa mencetak NIK serta tempat/tanggal lahir, sehingga
 * ketiganya wajib diisi. Isian template (agama, pekerjaan, data usaha, data
 * anak asuh, dan seterusnya) mengikuti konfigurasi jenis surat.
 *
 * Unggah dokumen bersifat OPSIONAL: jenis surat tertentu mungkin tidak punya
 * persyaratan dokumen sama sekali.
 */
class StorePengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // warga tidak perlu akun
    }

    /**
     * Jenis surat yang sedang diisi.
     *
     * Rute pengajuan baru punya parameter {jenisSurat}, sedangkan rute revisi
     * hanya punya {nomorReferensi}. Tanpa penyelesaian ini, seluruh aturan
     * isian surat dan dokumen persyaratan akan dilewati saat warga mengirim
     * perbaikan — akibatnya revisi bisa lolos dengan isian kosong dan surat
     * tercetak titik-titik.
     */
    protected function jenisSurat(): ?JenisSurat
    {
        $jenisSurat = $this->route('jenisSurat');

        if ($jenisSurat instanceof JenisSurat) {
            return $jenisSurat;
        }

        return $this->pengajuanRevisi()?->jenisSurat;
    }

    /**
     * Pengajuan yang sedang direvisi, bila permintaan ini datang dari rute
     * revisi. Null pada formulir pengajuan baru.
     */
    protected function pengajuanRevisi(): ?\App\Models\Pengajuan
    {
        if ($this->route('jenisSurat') instanceof JenisSurat) {
            return null;
        }

        $nomor = $this->route('nomorReferensi');

        if (! is_string($nomor) || trim($nomor) === '') {
            return null;
        }

        return \App\Models\Pengajuan::query()
            ->with(['jenisSurat.persyaratan', 'dokumen'])
            ->where('nomor_referensi', strtoupper(trim($nomor)))
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $jenisSurat = $this->jenisSurat();

        $rules = [
            'nama' => ['required', 'string', 'max:120'],
            'alamat' => ['required', 'string', 'max:500'],
            'keperluan' => ['required', 'string', 'max:500'],
            'whatsapp' => ['required', 'string', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'persetujuan' => ['accepted'],

            // Identitas wajib: setiap dokumen resmi desa mencetak NIK dan
            // tempat/tanggal lahir pada blok identitas pemohon.
            'nik' => ['required', 'digits:16'],
            'tempat_lahir' => ['required', 'string', 'max:80'],
            'tanggal_lahir' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'jenis_kelamin' => ['nullable', 'in:Laki-laki,Perempuan'],

            // Unggah dokumen tidak wajib; jumlah tidak lagi dikunci.
            //
            // Batas format & ukuran TIDAK ditaruh di sini. Aturan generik
            // "dokumen.*" berlaku untuk semua kunci sekaligus, sehingga
            // batas 2 MB di sini akan menimpa batas per persyaratan yang
            // diatur admin (hingga 10 MB) — unggahan 5 MB yang sah akan
            // ditolak dengan pesan "maksimal 2 MB". Format & ukuran diatur
            // per persyaratan di bawah; di sini hanya penjaga bentuk data.
            'dokumen' => ['nullable', 'array'],
            'dokumen.*' => ['nullable', 'file', 'max:10240'],
        ];

        // Isian template surat: setiap penanda kustom wajib diisi warga.
        if ($jenisSurat instanceof JenisSurat) {
            // Dokumen yang sudah pernah diunggah pada pengajuan yang sedang
            // direvisi. Halaman revisi menyatakan "Kosongkan bila tidak ingin
            // mengganti", jadi dokumen lama yang sudah ada TIDAK boleh
            // diwajibkan unggah ulang — kalau tidak, warga yang hanya
            // memperbaiki salah ketik tidak akan pernah bisa menyimpan
            // perbaikannya.
            $dokumenLama = $this->pengajuanRevisi()?->dokumen
                ->pluck('persyaratan_surat_id')
                ->filter()
                ->all() ?? [];

            // Aturan unggah mengikuti konfigurasi admin per persyaratan:
            // format (mis. pdf,jpg) dan ukuran maksimum dalam KB.
            // Bila admin tidak mengatur, dipakai batas aman bawaan (2 MB).
            foreach ($jenisSurat->persyaratan as $syarat) {
                $kunciDokumen = 'dokumen.'.$syarat->id;

                $aturan = ['nullable', 'file'];

                if (filled($syarat->format)) {
                    $aturan[] = 'mimes:'.$syarat->format;
                } else {
                    $aturan[] = 'mimes:jpg,jpeg,png,pdf';
                }

                $aturan[] = 'max:'.($syarat->maks_kb ?: 2048);

                $rules[$kunciDokumen] = $aturan;

                if ($syarat->wajib && ! in_array($syarat->id, $dokumenLama, true)) {
                    $rules[$kunciDokumen][] = 'required';
                }
            }

            $penanda = app(TemplateSuratService::class)->penandaKustom($jenisSurat);

            if ($penanda !== []) {
                $adaWajib = false;

                foreach ($penanda as $p) {
                    if (($p['wajib'] ?? true) === false) {
                        continue;
                    }

                    $adaWajib = true;
                    break;
                }

                $rules['isian_surat'] = $adaWajib ? ['required', 'array'] : ['nullable', 'array'];

                foreach ($penanda as $p) {
                    $wajib = ($p['wajib'] ?? true) !== false;
                    $awalan = $wajib ? 'required' : 'nullable';

                    // Tipe input menentukan aturan validasi, sehingga tanggal
                    // tidak bisa diisi teks bebas dan angka tetap angka.
                    $aturan = match ($p['tipe'] ?? 'text') {
                        'date' => [$awalan, 'date', 'after:1900-01-01'],
                        'number' => [$awalan, 'numeric', 'max:999999999999'],
                        'textarea' => [$awalan, 'string', 'max:2000'],
                        default => [$awalan, 'string', 'max:200'],
                    };

                    $rules['isian_surat.'.$p['kunci']] = $aturan;
                }
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $pesan = [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus 16 digit angka.',
            'tempat_lahir.required' => 'Tempat lahir wajib diisi.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'whatsapp.regex' => 'Nomor WhatsApp harus diawali 08, 62, atau +62.',
            'persetujuan.accepted' => 'Anda harus menyetujui pemberitahuan data untuk melanjutkan.',
            'dokumen.*.mimes' => 'Format dokumen tidak sesuai ketentuan.',
            'dokumen.*.max' => 'Ukuran dokumen melebihi batas yang diizinkan.',
            'dokumen.*.uploaded' => 'Dokumen gagal diunggah. Pastikan ukurannya tidak melebihi batas server, lalu coba lagi.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'isian_surat.required' => 'Lengkapi seluruh isian surat.',
        ];

        // Pesan ramah untuk isian template, memakai label hasil olahan kunci.
        $jenisSurat = $this->jenisSurat();

        if ($jenisSurat instanceof JenisSurat) {
            $layanan = app(TemplateSuratService::class);

            foreach ($layanan->penandaKustom($jenisSurat) as $p) {
                $pesan['isian_surat.'.$p['kunci'].'.required'] = 'Isian "'.$p['label'].'" wajib diisi.';
            }
        }

        return $pesan;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => 'nama lengkap',
            'nik' => 'NIK',
            'tempat_lahir' => 'tempat lahir',
            'tanggal_lahir' => 'tanggal lahir',
            'jenis_kelamin' => 'jenis kelamin',
            'alamat' => 'alamat lengkap',
            'keperluan' => 'keperluan surat',
            'whatsapp' => 'nomor WhatsApp',
            'dokumen.*' => 'dokumen persyaratan',
        ];
    }
}
