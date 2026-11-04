<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengajuanRequest;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Services\PengajuanService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Formulir pengajuan surat warga (tanpa akun).
 */
class PengajuanController extends Controller
{
    public function __construct(protected PengajuanService $pengajuanService) {}

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
        ]);
    }

    public function store(StorePengajuanRequest $request, JenisSurat $jenisSurat): RedirectResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->safe()->except(['dokumen', 'persetujuan']);
        $data['persetujuan_data'] = true; // checkbox 'persetujuan' bukan kolom DB

        $persyaratan = $jenisSurat->persyaratan()->get();
        $berkas = $request->file('dokumen', []);

        // Tulis file dulu ke folder sementara; dihapus lagi bila DB gagal.
        $folder = 'dokumen/'.now()->year.'/'.Str::uuid()->toString();
        $dokumen = [];
        $tertulis = [];

        try {
            foreach ($persyaratan as $index => $syarat) {
                $file = $berkas[$index] ?? null;

                if ($file === null || ! $file->isValid()) {
                    continue;
                }

                $path = $file->store($folder, 'local');

                if ($path === false) {
                    throw new \RuntimeException('Dokumen gagal disimpan.');
                }

                $tertulis[] = $path;

                $dokumen[] = [
                    'persyaratan_surat_id' => $syarat->id,
                    'label' => $syarat->label,
                    'path' => $path,
                    'nama_asli' => $file->getClientOriginalName(),
                    'mime' => (string) ($file->getMimeType() ?: $file->getClientMimeType()),
                    'ukuran' => (int) $file->getSize(),
                ];
            }

            $pengajuan = $this->pengajuanService->buat($jenisSurat, $data, $dokumen, $request->ip());
        } catch (Throwable $e) {
            Storage::disk('local')->delete($tertulis);

            throw $e;
        }

        $request->session()->put('pengajuan_terakhir', $pengajuan->nomor_referensi);

        return redirect()->route('pengajuan.berhasil', $pengajuan);
    }

    public function berhasil(Request $request, Pengajuan $pengajuan): View
    {
        // Nomor referensi berfungsi sebagai token: halaman hanya terbuka bila
        // nomornya diketahui (dari redirect setelah kirim) atau cocok di URL.
        $diizinkan = $request->session()->get('pengajuan_terakhir') === $pengajuan->nomor_referensi
            || hash_equals($pengajuan->nomor_referensi, (string) $request->query('ref'));

        abort_unless($diizinkan, 404);

        $pengajuan->load(['jenisSurat', 'dokumen']);

        return view('pages.pengajuan.berhasil', [
            'activeNav' => 'pelayanan',
            'pengajuan' => $pengajuan,
        ]);
    }
}
