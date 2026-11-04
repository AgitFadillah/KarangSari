<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPengajuan;
use App\Http\Controllers\Controller;
use App\Models\JenisSurat;
use App\Models\KontenWebsite;
use App\Models\Pengajuan;
use Illuminate\Contracts\View\View;

class RingkasanController extends Controller
{
    public function index(): View
    {
        $hitung = Pengajuan::query()
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $statusPerluTindakan = StatusPengajuan::perluTindakan();

        $total = (int) $hitung->sum();

        $perluTindakan = 0;
        foreach ($statusPerluTindakan as $status) {
            $perluTindakan += (int) $hitung->get($status->value, 0);
        }

        $dalamProses = 0;
        foreach ([StatusPengajuan::Disetujui, StatusPengajuan::SedangDiproses, StatusPengajuan::SiapDiambil] as $status) {
            $dalamProses += (int) $hitung->get($status->value, 0);
        }

        $selesai = (int) $hitung->get(StatusPengajuan::Selesai->value, 0);

        $sebaran = collect(StatusPengajuan::cases())->map(fn (StatusPengajuan $s) => [
            'status' => $s,
            'jumlah' => (int) $hitung->get($s->value, 0),
            'persen' => $total > 0 ? round(((int) $hitung->get($s->value, 0)) / $total * 100) : 0,
        ]);

        $sapaan = match (true) {
            now()->hour < 11 => 'Selamat pagi',
            now()->hour < 15 => 'Selamat siang',
            now()->hour < 19 => 'Selamat sore',
            default => 'Selamat malam',
        };

        return view('admin.ringkasan', [
            'activeAdmin' => 'admin.ringkasan',
            'sapaan' => $sapaan,
            'total' => $total,
            'perluTindakan' => $perluTindakan,
            'jumlahMenunggu' => (int) $hitung->get(StatusPengajuan::MenungguVerifikasi->value, 0),
            'jumlahPerbaikan' => (int) $hitung->get(StatusPengajuan::PerluPerbaikan->value, 0),
            'dalamProses' => $dalamProses,
            'selesai' => $selesai,
            'sebaran' => $sebaran,
            'terbaru' => Pengajuan::query()->with('jenisSurat')->latest()->limit(5)->get(),
            'jenisSuratAktif' => JenisSurat::query()->aktif()->count(),
            'jenisSuratTotal' => JenisSurat::query()->count(),
            'kontenTerbit' => KontenWebsite::query()->terbit()->count(),
            'kontenTotal' => KontenWebsite::query()->count(),
        ]);
    }
}
