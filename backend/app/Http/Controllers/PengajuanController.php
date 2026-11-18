<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengajuanRequest;
use App\Models\JenisSurat;
use App\Models\LogAktivitas;
use App\Models\Pengajuan;
use App\Services\PengajuanService;
use App\Services\SuratWordService;
use App\Services\TemplateSuratService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Formulir pengajuan surat warga (tanpa akun) + revisi setelah permintaan
 * perbaikan dari admin.
 *
 * Revisi memakai nomor referensi yang sama: warga tidak membuat pengajuan baru,
 * hanya memperbaiki isian surat / mengunggah ulang berkas yang ditolak.
 */
class PengajuanController extends Controller
{
    public function __construct(
        protected PengajuanService $pengajuanService,
        protected TemplateSuratService $templateService,
    ) {}

    /**
     * ?jenis=domisili memilih layanan; tanpa parameter jatuh ke layanan pertama.
     */
    public function create(Request $request): View
    {
        $jenisSurats = JenisSurat::query()->aktif()->terurut()->with('persyaratan')->get();

        $slug = Str::lower(trim((string) $request->query('jenis', '')));

        $jenisSurat = $slug === ''
            ? $jenisSurats->first()
            : $jenisSurats->firstWhere('slug', $slug);

        abort_if($jenisSurat === null, 404);

        return view('pages.pengajuan.create', [
            'activeNav' => 'pelayanan',
            'jenisSurat' => $jenisSurat,
            'jenisSurats' => $jenisSurats,
            'penanda' => $this->templateService->penandaKustom($jenisSurat),
            // Pratinjau memakai surat resmi desa (dokumen Word yang sama dengan
            // berkas unduhan), bukan teks template, supaya tampilannya sama
            // persis dengan surat yang akan dicetak.
            'pratinjau' => app(SuratWordService::class)->teks($jenisSurat),
        ]);
    }

    public function store(StorePengajuanRequest $request, JenisSurat $jenisSurat): RedirectResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->safe()->except(['dokumen', 'persetujuan', 'isian_surat']);
        $data['persetujuan_data'] = true; // checkbox 'persetujuan' bukan kolom DB
        $data['isian_surat'] = $this->bersihkanIsian($request);

        $berkas = $request->file('dokumen', []);

        // Tulis file dulu ke folder sementara; dihapus lagi bila DB gagal.
        $folder = 'dokumen/'.now()->year.'/'.Str::uuid()->toString();
        $dokumen = [];
        $tertulis = [];

        try {
            $dokumen = $this->simpanBerkas($jenisSurat, $berkas, $folder, $tertulis);

            $pengajuan = $this->pengajuanService->buat($jenisSurat, $data, $dokumen, $request->ip());
        } catch (Throwable $e) {
            Storage::disk($this->diskDokumen())->delete($tertulis);

            throw $e;
        }

        $request->session()->put('pengajuan_terakhir', $pengajuan->nomor_referensi);

        // Dipakai halaman cek status untuk mengisi kolom nomor referensi
        // secara otomatis, sehingga warga hanya perlu menempel kode akses.
        $request->session()->put('nomor_referensi_terakhir', $pengajuan->nomor_referensi);

        $this->tandaiAkses($request, $pengajuan);

        return redirect()->route('pengajuan.berhasil', $pengajuan);
    }

    public function berhasil(Request $request, Pengajuan $pengajuan): View
    {
        // Nomor referensi saja TIDAK cukup: ia berurutan dan mudah ditebak.
        // Akses diberikan bila warga baru saja mengirim (sesi) atau membawa
        // kode akses yang benar.
        $diizinkan = $request->session()->get('pengajuan_terakhir') === $pengajuan->nomor_referensi
            || $this->berhakAkses($request, $pengajuan);

        abort_unless($diizinkan, 404);

        $pengajuan->load(['jenisSurat', 'dokumen']);

        return view('pages.pengajuan.berhasil', [
            'activeNav' => 'pelayanan',
            'pengajuan' => $pengajuan,
            'kodeAkses' => $pengajuan->kode_akses,
        ]);
    }

    /**
     * Unduh surat jadi (.docx) oleh warga.
     *
     * Wajib membawa kode akses (atau sesi yang sudah ditandai), sebab nomor
     * referensi dapat ditebak. Surat hanya tersedia setelah pengajuan
     * disetujui atau selesai, supaya warga tidak mengunduh surat yang belum sah.
     */
    public function unduhSurat(Request $request, string $nomorReferensi, SuratWordService $surat): StreamedResponse
    {
        // Nomor referensi dinormalkan seperti pada halaman cek status dan
        // revisi. Tanpa ini, tautan yang diketik ulang dengan huruf kecil
        // ("krs-2026-0042") berakhir 404 padahal kodenya benar — dan warga
        // mengira suratnya hilang.
        $pengajuan = Pengajuan::query()
            ->with('jenisSurat')
            ->where('nomor_referensi', Str::upper(trim($nomorReferensi)))
            ->firstOrFail();

        abort_unless($this->berhakAkses($request, $pengajuan), 404);

        $bolehUnduh = $pengajuan->suratBolehDiunduh();

        abort_unless($bolehUnduh, 404);

        LogAktivitas::catat(
            tindakan: 'Warga mengunduh surat jadi (Word)',
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

    /**
     * Formulir revisi: hanya untuk pengajuan berstatus "Perlu Perbaikan".
     *
     * Wajib membawa kode akses (?kode=) atau memakai perangkat yang sesinya
     * sudah ditandai — nomor referensi saja tidak cukup.
     */
    public function revisi(Request $request, string $nomorReferensi): View
    {
        $pengajuan = $this->cariUntukRevisi($nomorReferensi);

        abort_unless($this->berhakAkses($request, $pengajuan), 404);

        $jenisSurat = $pengajuan->jenisSurat;

        return view('pages.pengajuan.revisi', [
            'activeNav' => 'cek-status',
            'pengajuan' => $pengajuan,
            'jenisSurat' => $jenisSurat,
            'kodeAkses' => $pengajuan->kode_akses,
            'penanda' => $this->templateService->penandaKustom($jenisSurat),
            // Pratinjau memakai surat resmi desa yang sudah terisi isian warga.
            'pratinjau' => $pengajuan->suratResmiTeks(),
        ]);
    }

    /** Simpan revisi: nomor referensi tetap, status kembali menunggu verifikasi. */
    public function simpanRevisi(StorePengajuanRequest $request, string $nomorReferensi): RedirectResponse
    {
        $pengajuan = $this->cariUntukRevisi($nomorReferensi);

        abort_unless($this->berhakAkses($request, $pengajuan), 404);

        $jenisSurat = $pengajuan->jenisSurat;

        $data = $request->safe()->except(['dokumen', 'persetujuan', 'isian_surat']);
        $data['isian_surat'] = $this->bersihkanIsian($request);

        $berkas = $request->file('dokumen', []);
        $folder = 'dokumen/'.now()->year.'/'.Str::uuid()->toString();
        $tertulis = [];

        try {
            $dokumen = $this->simpanBerkas($jenisSurat, $berkas, $folder, $tertulis);

            $this->pengajuanService->revisi($pengajuan, $data, $dokumen);
        } catch (Throwable $e) {
            Storage::disk($this->diskDokumen())->delete($tertulis);

            throw $e;
        }

        return redirect()
            ->route('cek-status.form')
            ->with('status', 'Revisi pengajuan '.$pengajuan->nomor_referensi.' terkirim. Admin akan memeriksa ulang.')
            ->with('nomor_referensi_terakhir', $pengajuan->nomor_referensi);
    }

    /**
     * Pratinjau surat resmi desa untuk formulir warga.
     *
     * Menerima isian sementara (belum disimpan) lalu mengembalikan teks surat
     * yang sudah terisi. Teksnya dibangun dari dokumen Word desa yang sama
     * dengan berkas .docx, jadi pratinjau di layar sama dengan hasil cetak.
     */
    public function pratinjauResmi(Request $request, JenisSurat $jenisSurat, SuratWordService $surat): JsonResponse
    {
        $isian = $request->input('isian', []);

        abort_unless(is_array($isian), 422);

        // Hanya isian teks pendek yang diterima; cegah payload besar.
        $isian = array_filter(
            array_map(fn ($v) => is_scalar($v) ? mb_substr((string) $v, 0, 200) : '', $isian),
            fn ($v) => $v !== ''
        );

        // Pengajuan sementara di memori: hanya untuk mengisi nama/NIK/alamat
        // pada pratinjau, tidak pernah disimpan.
        $sementara = new Pengajuan([
            'nama' => mb_substr((string) $request->input('nama', ''), 0, 120),
            'nik' => mb_substr((string) $request->input('nik', ''), 0, 32),
            'tempat_lahir' => mb_substr((string) $request->input('tempat_lahir', ''), 0, 80),
            'alamat' => mb_substr((string) $request->input('alamat', ''), 0, 255),
        ]);

        // tanggal_lahir memakai cast "date"; isi null bila belum diisi agar
        // pratinjau awal tidak gagal sebelum warga memilih tanggal.
        //
        // Nilai mentah dari formulir tidak boleh langsung diumpankan ke
        // Carbon::parse(): teks yang tidak dikenali (mis. "bukan-tanggal")
        // melempar InvalidFormatException dan berubah menjadi HTTP 500.
        // Pratinjau hanya alat bantu, jadi tanggal yang tidak terbaca cukup
        // dianggap kosong, bukan menggagalkan permintaan.
        $sementara->tanggal_lahir = null;

        if ($request->filled('tanggal_lahir')) {
            try {
                $sementara->tanggal_lahir = \Illuminate\Support\Carbon::parse((string) $request->input('tanggal_lahir'));
            } catch (\Throwable) {
                $sementara->tanggal_lahir = null;
            }
        }

        return response()->json([
            'teks' => $surat->teks($jenisSurat, $sementara, $isian),
        ]);
    }

    /**
     * Tandai perangkat ini berhak membuka pengajuan tersebut.
     *
     * Dipakai setelah warga mengirim pengajuan atau memasukkan kode akses yang
     * benar, supaya tautan berikutnya (unduh surat, revisi) tidak perlu
     * menempelkan ?kode= terus-menerus. Yang disimpan adalah HASH kode, bukan
     * kodenya, sehingga isi sesi tidak langsung membocorkan kunci.
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
     * Apakah permintaan ini berhak melihat/mengubah pengajuan.
     *
     * Dua jalur yang sah:
     *  1. membawa kode akses yang benar (?kode= / field kode), atau
     *  2. perangkat ini sesinya sudah ditandai untuk pengajuan tersebut.
     *
     * Nomor referensi SENDIRI tidak pernah dihitung sebagai bukti akses.
     */
    protected function berhakAkses(Request $request, Pengajuan $pengajuan): bool
    {
        $kode = $request->input('kode');

        if (is_string($kode) && $pengajuan->kodeAksesCocok($kode)) {
            $this->tandaiAkses($request, $pengajuan);

            return true;
        }

        $tersimpan = $request->session()->get('akses_pengajuan.'.$pengajuan->id);

        return is_string($tersimpan)
            && filled($pengajuan->kode_akses)
            && hash_equals(hash('sha256', (string) $pengajuan->kode_akses), $tersimpan);
    }

    /**
     * Ambil pengajuan yang boleh direvisi. 404 bila nomor tidak ada atau
     * statusnya bukan "Perlu Perbaikan" - agar nomor referensi orang lain
     * tidak bisa dipakai menebak-nebak.
     */
    protected function cariUntukRevisi(string $nomorReferensi): Pengajuan
    {
        $pengajuan = Pengajuan::query()
            ->with(['jenisSurat.persyaratan', 'dokumen'])
            ->where('nomor_referensi', Str::upper(trim($nomorReferensi)))
            ->first();

        abort_if($pengajuan === null, 404);
        abort_unless($pengajuan->perluPerbaikan(), 404);

        return $pengajuan;
    }

    /**
     * Simpan berkas unggahan warga menjadi baris dokumen pengajuan.
     *
     * Kunci array $berkas adalah ID persyaratan (name="dokumen[ID]"), sehingga
     * aturan validasi per persyaratan (format & ukuran) dapat ditelusuri balik
     * ke dokumen yang tepat.
     *
     * @param  array<int|string, mixed>  $berkas
     * @param  array<int, string>  $tertulis
     * @return array<int, array<string, mixed>>
     */
    protected function simpanBerkas(JenisSurat $jenisSurat, array $berkas, string $folder, array &$tertulis): array
    {
        $persyaratan = $jenisSurat->persyaratan()->get();
        $dokumen = [];
        $disk = $this->diskDokumen();

        foreach ($persyaratan as $syarat) {
            $file = $berkas[$syarat->id] ?? null;

            if (! $file instanceof \Illuminate\Http\UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store($folder, $disk);

            if ($path === false) {
                throw new \RuntimeException('Dokumen gagal disimpan.');
            }

            $tertulis[] = $path;

            $dokumen[] = [
                'persyaratan_surat_id' => $syarat->id,
                'label' => $syarat->label,
                'path' => $path,
                'disk' => $disk,
                // Nama asli hanya untuk ditampilkan/dipakai sebagai nama
                // unduhan. Disimpan dalam bentuk yang sudah bersih agar nilai
                // berbahaya (CR/LF, garis miring) tidak pernah masuk database.
                'nama_asli' => \App\Support\NamaBerkas::aman($file->getClientOriginalName(), 'dokumen'),
                'mime' => \App\Support\NamaBerkas::mimeAman(
                    (string) ($file->getMimeType() ?: $file->getClientMimeType())
                ),
                'ukuran' => (int) $file->getSize(),
            ];
        }

        return $dokumen;
    }

    /** Disk privat untuk dokumen warga ("local" atau "s3"). */
    protected function diskDokumen(): string
    {
        return (string) config('desa.disk_dokumen', 'local');
    }

    /**
     * Ambil hanya isian yang memang diminta template, buang sisanya.
     *
     * @return array<string, string>
     */
    protected function bersihkanIsian(Request $request): array
    {
        $jenisSurat = $request->route('jenisSurat');

        if (! $jenisSurat instanceof JenisSurat) {
            // Rute revisi: jenis surat diambil dari pengajuannya.
            $nomor = (string) $request->route('nomorReferensi');
            $jenisSurat = Pengajuan::query()
                ->with('jenisSurat')
                ->where('nomor_referensi', Str::upper(trim($nomor)))
                ->first()?->jenisSurat;
        }

        if (! $jenisSurat instanceof JenisSurat) {
            return [];
        }

        $masukan = (array) $request->input('isian_surat', []);
        $hasil = [];

        foreach ($this->templateService->penandaKustom($jenisSurat) as $p) {
            $nilai = $masukan[$p['kunci']] ?? null;

            if (is_scalar($nilai) && trim((string) $nilai) !== '') {
                $hasil[$p['kunci']] = trim((string) $nilai);
            }
        }

        return $hasil;
    }
}
