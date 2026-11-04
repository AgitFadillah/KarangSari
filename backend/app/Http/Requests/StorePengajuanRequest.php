<?php

namespace App\Http\Requests;

use App\Models\JenisSurat;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi formulir pengajuan surat warga (pengajuan.html).
 *
 * Batas ukuran file mengikuti teks di prototipe: "JPG, PNG, PDF · 2 MB".
 */
class StorePengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // warga tidak perlu akun
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $jenisSurat = $this->route('jenisSurat');

        // Jumlah dokumen wajib sama dengan jumlah persyaratan layanan; satu
        // dokumen per baris persyaratan (urutan input file mengikuti daftar).
        $jumlah = $jenisSurat instanceof JenisSurat
            ? $jenisSurat->persyaratan()->count()
            : 0;
        $jumlah = max($jumlah, 1);

        $rules = [
            'nama' => ['required', 'string', 'max:120'],
            'nik' => ['required', 'digits:16', function (string $attr, mixed $value, Closure $fail) {
                if (! $this->nikValid((string) $value)) {
                    $fail('NIK tidak valid. Periksa kembali 16 digit pada KTP Anda.');
                }
            }],
            'tempat_lahir' => ['required', 'string', 'max:80'],
            'tanggal_lahir' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'alamat' => ['required', 'string', 'max:500'],
            'keperluan' => ['required', 'string', 'max:500'],
            'whatsapp' => ['required', 'string', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'persetujuan' => ['accepted'],
            'dokumen' => ['required', 'array', 'size:'.$jumlah],
            'dokumen.*' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nik.digits' => 'NIK harus 16 digit angka.',
            'whatsapp.regex' => 'Nomor WhatsApp harus diawali 08, 62, atau +62.',
            'persetujuan.accepted' => 'Anda harus menyetujui pemberitahuan data untuk melanjutkan.',
            'dokumen.required' => 'Unggah seluruh dokumen persyaratan.',
            'dokumen.size' => 'Jumlah dokumen yang diunggah tidak sesuai persyaratan.',
            'dokumen.*.mimes' => 'Format dokumen harus JPG, PNG, atau PDF.',
            'dokumen.*.max' => 'Ukuran tiap dokumen maksimal 2 MB.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
        ];
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

    /**
     * Validasi struktur NIK: 6 digit wilayah + 6 digit tanggal (ddmmyy, +40 untuk perempuan)
     * + 4 digit urut. Longgar terhadap urutan agar tidak menolak NIK sah.
     */
    protected function nikValid(string $nik): bool
    {
        if (! preg_match('/^\d{16}$/', $nik)) {
            return false;
        }

        $wilayah = (int) substr($nik, 0, 2);
        if ($wilayah < 11 || $wilayah > 94) {
            return false;
        }

        $tanggal = (int) substr($nik, 6, 2);
        if ($tanggal > 40) {
            $tanggal -= 40;
        }

        $bulan = (int) substr($nik, 8, 2);

        return $tanggal >= 1 && $tanggal <= 31 && $bulan >= 1 && $bulan <= 12;
    }
}
