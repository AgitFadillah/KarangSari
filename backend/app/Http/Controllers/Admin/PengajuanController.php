<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPengajuan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStatusPengajuanRequest;
use App\Models\DokumenPengajuan;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Services\VerifikasiService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengajuanController extends Controller
{
    public function __construct(protected VerifikasiService $verifikasiService) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $status = StatusPengajuan::tryFrom((string) $status)?->value;

        $pengajuans = Pengajuan::query()
            ->with('jenisSurat')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->filled('jenis'), fn ($q) => $q->whereHas(
                'jenisSurat',
                fn ($j) => $j->where('slug', $request->query('jenis'))
            ))
            ->when($request->filled('tanggal'), fn ($q) => $q->whereDate('created_at', $request->query('tanggal')))
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
                'tanggal' => (string) $request->query('tanggal', ''),
            ],
        ]);
    }

    public function show(Pengajuan $pengajuan): View
    {
        $pengajuan->load(['jenisSurat.persyaratan', 'dokumen', 'riwayat.user', 'diprosesOleh']);

        return view('admin.pengajuan.show', [
            'activeAdmin' => 'admin.pengajuan.index',
            'pengajuan' => $pengajuan,
            'transisi' => $pengajuan->status->bolehBerubahKe(),
        ]);
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

    /** Dokumen warga hanya dibuka lewat route admin, tidak pernah dari URL publik. */
    public function unduhDokumen(Pengajuan $pengajuan, DokumenPengajuan $dokumen): StreamedResponse
    {
        abort_unless($dokumen->pengajuan_id === $pengajuan->id, 404);

        $disk = Storage::disk($dokumen->disk());

        abort_unless($disk->exists($dokumen->path), 404, 'Dokumen tidak ditemukan di penyimpanan.');

        $namaAman = str_replace(['/', '\\'], '-', $dokumen->label).'-'.$dokumen->nama_asli;

        return $disk->download($dokumen->path, $namaAman, [
            'Content-Type' => $dokumen->mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
