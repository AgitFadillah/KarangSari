<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nama_desa', 'kecamatan', 'kabupaten', 'provinsi', 'kode_pos',
    'alamat_kantor', 'telepon', 'whatsapp', 'email',
    'jam_layanan', 'jam_kunjungan', 'kueri_peta', 'sejarah', 'visi', 'misi',
    'logo_path', 'foto_beranda_path', 'batas_wilayah', 'penggunaan_lahan',
    'tahun_berdiri',
    // Bagian "Gambaran Umum Kondisi Desa" pada dokumen PROFIL desa.docx.
    'kondisi_umum', 'iklim', 'luas_wilayah', 'daftar_dusun',
    'jarak_provinsi', 'jarak_kabupaten', 'jarak_kecamatan',
    'penduduk_laki_laki', 'penduduk_perempuan', 'jumlah_kk',
    'keadaan_sosial',
])]
class ProfilDesa extends Model
{
    protected $table = 'profil_desas';

    /** Teks untuk atribut "Pemerintah {kabupaten} · Kecamatan {kecamatan}". */
    public function getWilayahAttribute(): string
    {
        return trim(sprintf('Pemerintah %s · Kecamatan %s', $this->kabupaten, $this->kecamatan));
    }

    public function getAlamatLengkapAttribute(): string
    {
        return (string) $this->alamat_kantor;
    }

    /**
     * Kata kunci pencarian peta.
     *
     * Kolom "Kueri lokasi peta" di dashboard boleh dikosongkan; dalam kondisi
     * itu alamat kantor + wilayah desa dipakai supaya peta tetap menunjuk ke
     * lokasi yang benar, bukan jatuh ke tengah samudra.
     */
    public function kueriPeta(): string
    {
        $kueri = trim((string) $this->kueri_peta);

        if ($kueri !== '') {
            return $kueri;
        }

        $kandidat = array_filter([
            trim((string) $this->alamat_kantor),
            trim((string) $this->nama_desa),
            filled($this->kecamatan) ? 'Kecamatan '.trim((string) $this->kecamatan) : '',
            trim((string) $this->kabupaten),
            trim((string) $this->provinsi),
        ], fn (string $baris): bool => $baris !== '');

        // Alamat kantor umumnya sudah memuat nama desa, kecamatan, dan
        // kabupaten. Bagian yang sudah terkandung di bagian sebelumnya
        // dilewati supaya kueri peta tidak berulang, misalnya
        // "… Karang Sari, Desa Karang Sari, Kecamatan Jati Agung …".
        $bagian = [];

        foreach ($kandidat as $baris) {
            foreach ($bagian as $sudah) {
                if (mb_stripos($sudah, $baris) !== false) {
                    continue 2;
                }
            }

            $bagian[] = $baris;
        }

        return implode(', ', $bagian) ?: 'Kantor '.$this->nama_desa;
    }

    /** Tautan "Buka di Google Maps" untuk lokasi kantor. */
    public function tautanPeta(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($this->kueriPeta());
    }

    /** URL sematan peta (tanpa API key, memakai mode "output=embed"). */
    public function embedPeta(): string
    {
        return 'https://www.google.com/maps?q='.rawurlencode($this->kueriPeta())
            .'&z=16&hl=id&output=embed';
    }

    /**
     * Nomor WhatsApp desa dalam bentuk angka saja (untuk tautan wa.me).
     * Null bila kolomnya kosong, supaya tombol tidak mengarah ke "wa.me/"
     * yang membuka WhatsApp tanpa tujuan.
     */
    public function nomorWhatsapp(): ?string
    {
        $angka = preg_replace('/\D/', '', (string) $this->whatsapp);

        return $angka !== '' ? $angka : null;
    }

    /**
     * URL logo desa (relatif terhadap origin halaman).
     * Null bila belum diunggah atau berkasnya hilang dari disk.
     */
    public function logoUrl(): ?string
    {
        return \App\Support\BerkasPublik::ada($this->logo_path)
            ? \App\Support\BerkasPublik::url($this->logo_path)
            : null;
    }

    /** URL foto utama beranda; null bila belum ada. */
    public function fotoBerandaUrl(): ?string
    {
        return \App\Support\BerkasPublik::ada($this->foto_beranda_path)
            ? \App\Support\BerkasPublik::url($this->foto_beranda_path)
            : null;
    }

    /**
     * Batas wilayah sebagai pasangan arah => nama, diurai dari format
     * "Utara: Desa Cibeureum" per baris.
     *
     * @return array<string, string>
     */
    public function getBatasWilayahTersusunAttribute(): array
    {
        return $this->uraiBaris($this->batas_wilayah);
    }

    /**
     * Penggunaan lahan sebagai daftar label/persen, diurai dari format
     * "Kebun dan pertanian: 58" per baris.
     *
     * @return array<int, array{label: string, persen: int}>
     */
    public function getPenggunaanLahanTersusunAttribute(): array
    {
        $hasil = [];

        foreach ($this->uraiBaris($this->penggunaan_lahan) as $label => $nilai) {
            $persen = (int) preg_replace('/[^0-9]/', '', $nilai);
            $persen = max(0, min(100, $persen));

            if ($label !== '' && $persen > 0) {
                $hasil[] = ['label' => $label, 'persen' => $persen];
            }
        }

        return $hasil;
    }

    /**
     * @return array<string, string>
     */
    protected function uraiBaris(?string $teks): array
    {
        $hasil = [];

        foreach (preg_split('/\r\n|\r|\n/', (string) $teks) ?: [] as $baris) {
            $baris = trim($baris);

            if ($baris === '' || ! str_contains($baris, ':')) {
                continue;
            }

            [$kunci, $nilai] = explode(':', $baris, 2);
            $kunci = trim($kunci);
            $nilai = trim($nilai);

            if ($kunci !== '' && $nilai !== '') {
                $hasil[$kunci] = $nilai;
            }
        }

        return $hasil;
    }

    /**
     * Daftar dusun sebagai larik nama, diurai dari satu nama per baris.
     * Nomor urut di depan nama ("1. Dusun I B") dibuang agar tidak dobel saat
     * halaman publik menampilkannya bernomor.
     *
     * @return array<int, string>
     */
    public function getDusunTersusunAttribute(): array
    {
        $hasil = [];

        foreach (preg_split('/\r\n|\r|\n/', (string) $this->daftar_dusun) ?: [] as $baris) {
            $baris = trim(preg_replace('/^\s*\d+[.)]\s*/', '', trim($baris)) ?? '');

            if ($baris !== '') {
                $hasil[] = $baris;
            }
        }

        return $hasil;
    }

    /**
     * Jadwal yang dipakai kartu "Jam pelayanan" di halaman kontak.
     *
     * Bila perangkat desa mengisi "Jam kunjungan kantor" dengan rincian
     * berjam (mis. "Senin-Kamis 08.00-15.00, Jumat 08.00-11.30"), rincian
     * itulah yang dipakai. Bila tidak, dipakai "Jam pelayanan" — kolom yang
     * sama dengan yang tampil di topbar, footer, panduan, dan cek status —
     * agar keterangan jam di seluruh situs tidak saling bertentangan.
     *
     * @return array<int, array{hari: string, jam: string, tutup: bool}>
     */
    public function jadwalTampil(): array
    {
        $rincian = $this->uraiJadwal($this->jam_kunjungan);

        // Rincian hanya dipakai bila benar-benar memuat jam; kalau hanya
        // berisi nama hari ("Senin - Minggu"), jamnya diambil dari
        // "Jam pelayanan" supaya tidak tampil sebagai baris tanpa jam.
        foreach ($rincian as $baris) {
            if (! $baris['tutup'] && $baris['jam'] !== '') {
                return $rincian;
            }
        }

        $layanan = $this->uraiJadwal($this->jam_layanan);

        // Gabungkan: nama hari dari "Jam kunjungan" + jam dari "Jam
        // pelayanan". Kasus nyata di lapangan: kunjungan diisi
        // "Senin - Minggu" dan pelayanan diisi "08.00 - 17.00" — dulu
        // hasilnya hanya "Setiap hari", padahal hari bukanya sudah diketahui.
        $hariSaja = array_values(array_filter($rincian, fn (array $b): bool => ! $b['tutup'] && $b['jam'] === ''));
        $jamSaja = array_values(array_filter($layanan, fn (array $b): bool => ! $b['tutup'] && $b['jam'] !== ''));

        if ($hariSaja !== [] && $jamSaja !== []) {
            $gabung = [];

            foreach ($hariSaja as $i => $hari) {
                $jam = $jamSaja[$i] ?? $jamSaja[0];
                $gabung[] = ['hari' => $hari['hari'], 'jam' => $jam['jam'], 'tutup' => false];
            }

            // Hari libur pada rincian kunjungan tetap ditampilkan.
            foreach ($rincian as $baris) {
                if ($baris['tutup']) {
                    $gabung[] = $baris;
                }
            }

            return $gabung;
        }

        return $layanan;
    }

    /**
     * Jadwal pelayanan per hari untuk kartu "Jam pelayanan" di halaman kontak.
     *
     * Sumbernya kolom `jam_layanan` yang diisi bebas oleh perangkat desa
     * (mis. "Senin - Minggu 08.00 - 17.00" atau "Senin-Kamis 08.00-15.00,
     * Jumat 08.00-11.30, Sabtu 08.00-12.00"). Sebelumnya kartu ini ditulis
     * mati di blade sehingga selalu menampilkan "Sabtu–Minggu: Tutup" walau
     * kantor buka setiap hari.
     *
     * @return array<int, array{hari: string, jam: string, tutup: bool}>
     */
    public function getJadwalLayananAttribute(): array
    {
        return $this->uraiJadwal($this->jam_layanan);
    }

    /**
     * @return array<int, array{hari: string, jam: string, tutup: bool}>
     */
    protected function uraiJadwal(?string $teks): array
    {
        $teks = trim((string) $teks);

        if ($teks === '') {
            return [];
        }

        // Buang awalan "Pelayanan" bila perangkat desa menuliskannya.
        $teks = trim(preg_replace('/^pelayanan\s+/i', '', $teks) ?? $teks);

        // Pisahkan per butir: koma, titik koma, atau baris baru.
        $butir = preg_split('/\s*[;,\n]\s*/', $teks) ?: [];

        $hasil = [];

        foreach ($butir as $satu) {
            $satu = trim($satu);

            if ($satu === '') {
                continue;
            }

            // Pola: "{hari} {jam}". Jam dikenali dari pola angka berpemisah
            // titik/titik dua, mis. "08.00 - 15.00" atau "08:00-11:30".
            if (preg_match('/^(?<hari>.*?)\s+(?<jam>\d{1,2}[.:]\d{2}\s*[-\x{2013}\x{2014}]\s*\d{1,2}[.:]\d{2})$/u', $satu, $m)) {
                $hasil[] = [
                    'hari' => trim($m['hari']),
                    'jam' => trim($m['jam']),
                    'tutup' => false,
                ];

                continue;
            }

            // Butir berisi jam saja (tanpa nama hari) berarti berlaku setiap
            // hari, mis. "08.00 - 17.00". Tanpa cabang ini, jamnya sempat
            // terbaca sebagai nama hari.
            if (preg_match('/^\d{1,2}[.:]\d{2}\s*[-\x{2013}\x{2014}]\s*\d{1,2}[.:]\d{2}$/u', $satu)) {
                $hasil[] = [
                    'hari' => 'Setiap hari',
                    'jam' => $satu,
                    'tutup' => false,
                ];

                continue;
            }

            // Butir tanpa jam (mis. "Sabtu - Minggu") dianggap tutup, tetapi
            // hanya bila memang menyebut "tutup"/"libur".
            if (preg_match('/\b(tutup|libur)\b/i', $satu)) {
                $hasil[] = [
                    'hari' => trim(preg_replace('/\b(tutup|libur)\b/i', '', $satu) ?? $satu),
                    'jam' => 'Tutup',
                    'tutup' => true,
                ];

                continue;
            }

            // Sisanya ditampilkan apa adanya sebagai satu baris.
            $hasil[] = ['hari' => $satu, 'jam' => '', 'tutup' => false];
        }

        return $hasil;
    }

    /**
     * Jumlah penduduk total. Bila kolom laki-laki/perempuan terisi, keduanya
     * dijumlahkan agar angka selalu konsisten dengan rincian yang tampil.
     */
    public function getJumlahPendudukAttribute(): ?string
    {
        $l = (int) preg_replace('/[^0-9]/', '', (string) $this->penduduk_laki_laki);
        $p = (int) preg_replace('/[^0-9]/', '', (string) $this->penduduk_perempuan);

        if ($l + $p === 0) {
            return null;
        }

        return number_format($l + $p, 0, ',', '.');
    }
}
