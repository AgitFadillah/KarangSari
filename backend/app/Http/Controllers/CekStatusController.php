<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Cek status pengajuan oleh warga.
 *
 * Butuh DUA hal: nomor referensi + kode akses. Nomor referensi berurutan dan
 * mudah ditebak, jadi ia bukan kunci. Kode akses acak (kolom kode_akses) yang
 * menentukan apakah warga berhak melihat status pengajuan tersebut.
 *
 * Halaman ini juga menampilkan kembali nomor referensi dan kode akses milik
 * warga, supaya tidak ada jalan buntu ketika kode tercatat di perangkat lain
 * atau kertasnya hilang. Bila kode benar-benar lupa, tersedia jalur pemulihan
 * dengan tanggal lahir - bukan dengan menebak nomor referensi.
 *
 * Hanya status, riwayat, dan catatan admin yang ditampilkan - bukan NIK,
 * alamat, atau dokumen. Route POST-nya dibatasi per menit per IP.
 */
class CekStatusController extends Controller
{
    public function form(): View
    {
        return view('pages.cek-status', [
            'activeNav' => 'cek-status',
            'pengajuan' => null,
            'kodeAkses' => null,
        ]);
    }

    public function cari(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'nomor_referensi' => ['required', 'string', 'max:30'],
            'kode_akses' => ['nullable', 'string', 'max:32'],
        ], [
            'nomor_referensi.required' => 'Masukkan nomor referensi pengajuan Anda.',
        ]);

        $nomor = strtoupper(trim((string) $data['nomor_referensi']));
        $kode = (string) ($data['kode_akses'] ?? '');

        $pengajuan = Pengajuan::query()
            ->with(['jenisSurat', 'riwayat.user'])
            ->where('nomor_referensi', $nomor)
            ->first();

        // Pesan sengaja disamakan untuk "nomor tidak ada" dan "kode salah",
        // supaya halaman ini tidak bisa dipakai menebak nomor mana yang ada.
        $pesan = 'Nomor referensi dan kode akses tidak cocok. Periksa kembali keduanya.';

        if ($pengajuan === null) {
            return back()->withInput()->withErrors(['kode_akses' => $pesan]);
        }

        if ($pengajuan->kodeAksesCocok($kode)) {
            $this->tandaiAkses($request, $pengajuan);
        } elseif (! $this->sesiPunyaAkses($request, $pengajuan)) {
            return back()->withInput()->withErrors(['kode_akses' => $pesan]);
        }

        return $this->tampilkanHasil($pengajuan);
    }

    /**
     * Tampilkan status langsung untuk perangkat yang sudah terbukti berhak.
     *
     * Dipakai tautan "Pantau status surat" tepat setelah warga mengirim
     * pengajuan: sesi perangkat itu sudah ditandai, jadi tidak perlu menempel
     * kode akses lagi hanya untuk melihat status yang barusan ia buat.
     * Perangkat lain tetap mendapat 404.
     */
    /**
     * Tampilkan status langsung untuk perangkat yang sudah terbukti berhak.
     *
     * Dipakai tautan "Pantau status surat" tepat setelah warga mengirim
     * pengajuan: sesi perangkat itu sudah ditandai, jadi tidak perlu menempel
     * kode akses lagi hanya untuk melihat status yang barusan ia buat.
     * Perangkat lain tetap mendapat 404.
     *
     * Kode akses (?kode=) juga diterima, sama seperti halaman surat dan
     * revisi. Tanpa itu, tautan yang tersalin dari bilah alamat — mis. saat
     * warga membukanya di ponsel lain atau setelah cookie dibersihkan —
     * berakhir 404 padahal warga memegang kunci yang benar, dan ia terpaksa
     * kembali ke formulir cek status untuk mengetik ulang nomor + kode.
     */
    public function status(Request $request, string $nomorReferensi): View
    {
        $pengajuan = Pengajuan::query()
            ->with(['jenisSurat', 'riwayat.user'])
            ->where('nomor_referensi', strtoupper(trim($nomorReferensi)))
            ->first();

        abort_if($pengajuan === null, 404);

        $kode = $request->query('kode');

        if (is_string($kode) && $pengajuan->kodeAksesCocok($kode)) {
            $this->tandaiAkses($request, $pengajuan);
        } else {
            abort_unless($this->sesiPunyaAkses($request, $pengajuan), 404);
        }

        return $this->tampilkanHasil($pengajuan);
    }

    // --------------------------------------------------------------- pemulihan

    /** Formulir "lupa kode akses". */
    public function lupaForm(): View
    {
        return view('pages.cek-status-lupa', [
            'activeNav' => 'cek-status',
        ]);
    }

    /**
     * Pulihkan akses dengan tanggal lahir pemohon.
     *
     * Tanggal lahir dipilih karena warga pasti mengingatnya, tidak perlu
     * disalin dari mana pun, dan tidak bisa ditebak orang lain untuk nomor
     * referensi berurutan. NIK sengaja tidak dipakai: 16 digit terlalu berat
     * untuk dihafal saat warga panik karena kode hilang.
     */
    public function lupa(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'nomor_referensi' => ['required', 'string', 'max:30'],
            'tanggal_lahir' => ['required', 'date'],
        ], [
            'nomor_referensi.required' => 'Masukkan nomor referensi pengajuan Anda.',
            'tanggal_lahir.required' => 'Masukkan tanggal lahir pemohon.',
            'tanggal_lahir.date' => 'Tanggal lahir tidak dikenali. Gunakan format tanggal pada kolom.',
        ]);

        $nomor = strtoupper(trim((string) $data['nomor_referensi']));

        $pengajuan = Pengajuan::query()
            ->with(['jenisSurat', 'riwayat.user'])
            ->where('nomor_referensi', $nomor)
            ->first();

        $pesan = 'Nomor referensi dan tanggal lahir tidak cocok. Periksa kembali keduanya.';

        if ($pengajuan === null || $pengajuan->tanggal_lahir === null) {
            return back()->withInput($request->only('nomor_referensi'))
                ->withErrors(['tanggal_lahir' => $pesan]);
        }

        $dicoba = Carbon::parse((string) $data['tanggal_lahir'])->toDateString();

        if ($pengajuan->tanggal_lahir->toDateString() !== $dicoba) {
            return back()->withInput($request->only('nomor_referensi'))
                ->withErrors(['tanggal_lahir' => $pesan]);
        }

        // Bukti sudah cukup: tandai perangkat ini agar tombol unduh/revisi
        // langsung bekerja, lalu tampilkan kembali nomor + kode akses.
        $this->tandaiAkses($request, $pengajuan);

        return $this->tampilkanHasil($pengajuan, dipulihkan: true);
    }

    // ------------------------------------------------------------------ bantuan

    protected function tampilkanHasil(Pengajuan $pengajuan, bool $dipulihkan = false): View
    {
        return view('pages.cek-status', [
            'activeNav' => 'cek-status',
            'pengajuan' => $pengajuan,
            // Ditampilkan kembali supaya warga bisa mencatat/menyalin ulang
            // kapan pun ia sudah terbukti berhak atas pengajuan ini.
            'kodeAkses' => $pengajuan->kode_akses,
            'dipulihkan' => $dipulihkan,
        ]);
    }

    /**
     * Tandai perangkat ini berhak membuka pengajuan tersebut.
     *
     * Yang disimpan adalah HASH kode, bukan kodenya, sehingga isi sesi tidak
     * langsung membocorkan kunci bila sesi bocor.
     */
    protected function tandaiAkses(Request $request, Pengajuan $pengajuan): void
    {
        if (! filled($pengajuan->kode_akses)) {
            return;
        }

        $request->session()->put(
            'akses_pengajuan.'.$pengajuan->id,
            hash('sha256', (string) $pengajuan->kode_akses)
        );
    }

    /**
     * Apakah sesi perangkat ini sudah pernah ditandai untuk pengajuan itu.
     * Hanya hash kode yang disimpan, bukan kode aslinya.
     */
    protected function sesiPunyaAkses(Request $request, Pengajuan $pengajuan): bool
    {
        $tersimpan = $request->session()->get('akses_pengajuan.'.$pengajuan->id);

        return is_string($tersimpan)
            && filled($pengajuan->kode_akses)
            && hash_equals(hash('sha256', (string) $pengajuan->kode_akses), $tersimpan);
    }
}