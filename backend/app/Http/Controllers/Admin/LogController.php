<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Log aktivitas admin.
 *
 * Halaman ini dipakai untuk menelusuri kembali "siapa melakukan apa".
 * Karena log bisa berisi ribuan baris, daftar dapat disaring per hari:
 *
 *   /admin/log                        -> semua hari (dikelompokkan per tanggal)
 *   /admin/log?tanggal=2026-11-19     -> hanya tanggal itu
 *   /admin/log?q=verifikasi           -> pencarian teks (boleh digabung)
 */
class LogController extends Controller
{
    /** Jumlah hari pada ringkasan "aktivitas per hari". */
    protected const HARI_RINGKASAN = 14;

    public function index(Request $request): View
    {
        $q = (string) $request->query('q', '');
        $tanggal = $this->tanggalSah($request->query('tanggal'));

        $logs = LogAktivitas::query()
            ->cari($q)
            ->when($tanggal !== null, fn ($kueri) => $kueri->whereDate('created_at', $tanggal))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Ringkasan per hari mengikuti kata kunci, tetapi TIDAK mengikuti
        // filter tanggal — kalau ikut, daftar hari akan tinggal satu baris
        // dan navigasi antar hari menjadi tidak mungkin.
        $perHari = LogAktivitas::query()
            ->cari($q)
            ->selectRaw('DATE(created_at) as hari, COUNT(*) as jumlah')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderByDesc(DB::raw('DATE(created_at)'))
            ->limit(self::HARI_RINGKASAN)
            ->get();

        return view('admin.log.index', [
            'activeAdmin' => 'admin.log',
            'logs' => $logs,
            'q' => $q,
            'tanggal' => $tanggal,
            'perHari' => $perHari,
        ]);
    }

    /**
     * Ubah parameter ?tanggal= menjadi "Y-m-d", atau null bila kosong/tidak sah.
     *
     * Parameter datang dari URL, jadi bisa berisi apa saja ("abc",
     * "2026-13-45"). Nilai yang tidak sah diperlakukan sebagai "tanpa filter"
     * alih-alih melempar galat 500.
     */
    protected function tanggalSah(mixed $nilai): ?string
    {
        $nilai = trim((string) $nilai);

        if ($nilai === '') {
            return null;
        }

        try {
            $tanggal = Carbon::createFromFormat('Y-m-d', $nilai);
        } catch (\Throwable) {
            return null;
        }

        // createFromFormat meloloskan tanggal "2026-02-31" dengan menggeser
        // bulannya; pastikan hasil format ulangnya sama dengan kiriman.
        if ($tanggal === false || $tanggal->format('Y-m-d') !== $nilai) {
            return null;
        }

        return $nilai;
    }
}
