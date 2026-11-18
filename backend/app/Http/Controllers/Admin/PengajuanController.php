<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPengajuan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStatusDokumenRequest;
use App\Http\Requests\Admin\UpdateStatusPengajuanRequest;
use App\Models\DokumenPengajuan;
use App\Models\JenisSurat;
use App\Models\LogAktivitas;
use App\Models\Pengajuan;
use App\Services\SuratWordService;
use App\Services\VerifikasiService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengajuanController extends Controller
{
    public function __construct(protected VerifikasiService $verifikasiService)
    {
        // Seluruh halaman pengajuan berisi data pribadi warga (NIK, alamat,
        // KTP/KK). Hanya Administrator dan Verifikator yang boleh membukanya.
        // Penjagaan ditaruh di konstruktor supaya metode baru otomatis ikut
        // terlindungi dan tidak ada yang lupa menambahkan cek peran.
        abort_unless(
            request()->user()?->role->bolehVerifikasiPengajuan() ?? false,
            403
        );
    }

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $status = StatusPengajuan::tryFrom((string) $status)?->value;

        // Tanggal filter harus berupa YYYY-MM-DD yang benar. Nilai mentah dari
        // query string tidak boleh langsung masuk whereDate(): PostgreSQL
        // menolak "abc" atau "2026-13-45" dengan galat sintaks dan berubah
        // menjadi HTTP 500, padahal itu hanya URL yang salah ketik. Filter
        // yang tidak sah diabaikan (sama seperti filter status asing).
        $tanggal = $this->tanggalSah($request->query('tanggal'));

        $pengajuans = Pengajuan::query()
            ->with('jenisSurat')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->filled('jenis'), fn ($q) => $q->whereHas(
                'jenisSurat',
                fn ($j) => $j->where('slug', $request->query('jenis'))
            ))
            ->when($tanggal, fn ($q) => $q->whereDate('created_at', $tanggal))
            ->cari($request->query('q'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.pengajuan.index', [
            'activeAdmin' => 'admin.pengajuan.index',
            'pengajuans' => $pengajuans,
            'jenisSurats' => JenisSurat::query()->terurut()->get(),
            'statusList' => StatusPengajuan::cases(),
            'filter' => [
                'status' => $status,
                'q' => (string) $request->query('q', ''),
                'jenis' => (string) $request->query('jenis', ''),
                'tanggal' => $tanggal ?? '',
            ],
        ]);
    }

    /**
     * Kembalikan tanggal dalam bentuk Y-m-d bila nilainya tanggal yang benar,
     * atau null bila kosong/tidak sah.
     *
     * Dipakai untuk memfilter query, jadi formatnya harus dipastikan lebih
     * dulu — bukan sekadar "kelihatan seperti tanggal".
     */
    protected function tanggalSah(mixed $nilai): ?string
    {
        if (! is_string($nilai) || trim($nilai) === '') {
            return null;
        }

        $nilai = trim($nilai);

        try {
            $tanggal = \Illuminate\Support\Carbon::createFromFormat('!Y-m-d', $nilai);
        } catch (\Throwable) {
            return null;
        }

        // createFromFormat menerima "2026-13-45" dengan menggeser bulannya.
        // Format ulang lalu bandingkan agar tanggal mustahil ikut tertolak.
        if ($tanggal === null || $tanggal->format('Y-m-d') !== $nilai) {
            return null;
        }

        return $nilai;
    }

    public function show(Pengajuan $pengajuan): View
    {
        $pengajuan->load(['jenisSurat.persyaratan', 'dokumen', 'riwayat.user', 'diprosesOleh']);

        $transisi = $pengajuan->status->bolehBerubahKe();

        // Aksi cepat di kepala halaman hanya muncul bila peran admin memang
        // boleh mengubah status dan transisinya sah menurut alur.
        $bolehVerifikasi = (bool) request()->user()?->role->bolehVerifikasiPengajuan();

        $aksi = [
            'setujui' => $bolehVerifikasi && in_array(StatusPengajuan::Disetujui, $transisi, true),
            'perbaikan' => $bolehVerifikasi && in_array(StatusPengajuan::PerluPerbaikan, $transisi, true),
            'tolak' => $bolehVerifikasi && in_array(StatusPengajuan::Ditolak, $transisi, true),
        ];

        return view('admin.pengajuan.show', [
            'activeAdmin' => 'admin.pengajuan.index',
            'pengajuan' => $pengajuan,
            'transisi' => $transisi,
            'aksi' => $aksi,
            // Tautan chat WhatsApp warga, dihitung sekali per halaman.
            'tautanWa' => $pengajuan->tautanWhatsapp(),
            'versiPrivasi' => config('desa.versi_pemberitahuan_data'),
            // Nomor surat: pakai yang tersimpan, atau susun usulan dari kode
            // layanan agar petugas tidak mengetik dari nol.
            'nomorSuratUsulan' => $pengajuan->nomor_surat
                ?: ($pengajuan->jenisSurat->kode_layanan ?: config('desa.kode_surat'))
                    .'/____/'.config('desa.kode_surat').'/'.$pengajuan->created_at->year,
        ]);
    }

    /**
     * Simpan nomor surat saja, tanpa mengubah status.
     *
     * Terpisah dari updateStatus karena nomor surat sering baru diketahui
     * setelah surat disetujui dan diberi nomor oleh petugas tata usaha.
     */
    public function simpanNomorSurat(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        abort_unless($request->user()?->role->bolehVerifikasiPengajuan() ?? false, 403);

        $data = $request->validate([
            'nomor_surat' => ['nullable', 'string', 'max:80'],
        ], [], ['nomor_surat' => 'nomor surat']);

        $pengajuan->nomor_surat = filled($data['nomor_surat'] ?? null)
            ? trim($data['nomor_surat'])
            : null;
        $pengajuan->save();

        LogAktivitas::catat(
            tindakan: 'Menyimpan nomor surat pengajuan',
            subjek: $pengajuan->nomor_referensi,
            tingkat: LogAktivitas::SUKSES,
            keterangan: $pengajuan->nomor_surat,
            user: $request->user(),
        );

        return redirect()
            ->route('admin.pengajuan.show', $pengajuan)
            ->with('status', $pengajuan->nomor_surat
                ? 'Nomor surat disimpan: '.$pengajuan->nomor_surat.'.'
                : 'Nomor surat dikosongkan.');
    }

    public function updateStatus(UpdateStatusPengajuanRequest $request, Pengajuan $pengajuan): RedirectResponse
    {
        try {
            $this->verifikasiService->ubahStatus(
                $pengajuan,
                $request->statusTujuan(),
                $request->user(),
                $request->validated('catatan'),
            );
        } catch (DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.pengajuan.show', $pengajuan)
            ->with('status', 'Status pengajuan diperbarui menjadi '.$pengajuan->status->label().'.');
    }

    /**
     * Surat jadi dalam format Word asli desa (.docx).
     *
     * Tata letaknya identik dengan dokumen perangkat desa karena memakai
     * kerangka XML dokumen asli, bukan menggambar ulang.
     */
    public function unduhSuratWord(Pengajuan $pengajuan, SuratWordService $surat): StreamedResponse
    {
        $pengajuan->loadMissing('jenisSurat');

        LogAktivitas::catat(
            tindakan: 'Mengunduh surat jadi (Word)',
            subjek: $pengajuan->nomor_referensi,
            tingkat: LogAktivitas::SUKSES,
            keterangan: $pengajuan->jenisSurat?->nama,
        );

        $isi = $surat->isi($pengajuan->jenisSurat, $pengajuan, (array) ($pengajuan->isian_surat ?? []));
        $nama = $surat->namaUnduhan($pengajuan->jenisSurat, $pengajuan);

        return response()->streamDownload(
            fn () => print $isi,
            $nama,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /** Dokumen warga hanya dibuka lewat route admin, tidak pernah dari URL publik. */
    public function unduhDokumen(Pengajuan $pengajuan, DokumenPengajuan $dokumen): StreamedResponse
    {
        abort_unless($dokumen->pengajuan_id === $pengajuan->id, 404);

        $disk = Storage::disk($dokumen->disk());

        // Akses dokumen PII dicatat lebih dulu agar percobaan yang gagal pun
        // meninggalkan jejak audit (mis. berkas sudah dipindahkan).
        LogAktivitas::catat(
            tindakan: 'Membuka dokumen pengajuan',
            subjek: $pengajuan->nomor_referensi,
            tingkat: LogAktivitas::PERINGATAN,
            keterangan: $dokumen->label,
        );

        abort_unless($disk->exists($dokumen->path), 404, 'Dokumen tidak ditemukan di penyimpanan.');

        // Nama berkas dan MIME berasal dari WARGA (nama asli unggahan dan tipe
        // yang dilaporkan browser mereka), lalu dipakai pada header respons.
        // Nilai mentahnya berbahaya:
        //   - garis miring pada nama membuat Symfony melempar exception ->
        //     petugas melihat HTTP 500 dan dokumennya tidak bisa dibuka;
        //   - CR/LF menyuntik header HTTP baru;
        //   - "text/html" membuat berkas warga dirender sebagai halaman di
        //     origin situs desa (stored XSS terhadap sesi admin).
        $namaAman = \App\Support\NamaBerkas::aman(
            str_replace(['/', '\\'], '-', (string) $dokumen->label).'-'.(string) $dokumen->nama_asli,
            'dokumen'
        );

        return $disk->download($dokumen->path, $namaAman, [
            'Content-Type' => \App\Support\NamaBerkas::mimeAman($dokumen->mime),
            'X-Content-Type-Options' => 'nosniff',
            // Berkas unggahan warga tidak boleh dirender inline di origin
            // situs desa; selalu sebagai lampiran.
            'Content-Disposition' => 'attachment; filename="'.$namaAman.'"',
        ]);
    }

    /** Tandai satu dokumen sebagai terverifikasi atau ditolak. */
    public function verifikasiDokumen(
        UpdateStatusDokumenRequest $request,
        Pengajuan $pengajuan,
        DokumenPengajuan $dokumen,
    ): RedirectResponse {
        abort_unless($dokumen->pengajuan_id === $pengajuan->id, 404);

        try {
            $this->verifikasiService->verifikasiDokumen(
                $dokumen,
                (string) $request->validated('status_verifikasi'),
                $request->user(),
            );
        } catch (DomainException $e) {
            return back()->withErrors(['status_verifikasi' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.pengajuan.show', $pengajuan)
            ->with('status', 'Status dokumen "'.$dokumen->label.'" diperbarui.');
    }
}
