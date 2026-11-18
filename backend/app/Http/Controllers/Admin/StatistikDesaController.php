<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\StatistikDesa;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Kelola angka "Sekilas Desa" di beranda (penduduk, KK, luas wilayah, dusun).
 * Nilai disimpan sebagai teks agar format seperti "1.284" tetap dapat ditulis.
 */
class StatistikDesaController extends Controller
{
    public function index(): View
    {
        abort_unless(request()->user()->role->bolehKelolaKonten(), 403);

        return view('admin.statistik.index', [
            'activeAdmin' => 'admin.statistik.index',
            'statistiks' => StatistikDesa::query()->terurut()->get(),
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()->role->bolehKelolaKonten(), 403);

        return view('admin.statistik.form', [
            'activeAdmin' => 'admin.statistik.index',
            'statistik' => new StatistikDesa(['is_aktif' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaKonten(), 403);

        $data = $this->validasi($request);

        $statistik = StatistikDesa::create([
            'label' => $data['label'],
            'nilai' => $data['nilai'],
            'satuan' => $data['satuan'] ?? null,
            'urutan' => $data['urutan'] ?? 0,
            'is_aktif' => $request->boolean('is_aktif'),
        ]);

        LogAktivitas::catat(
            tindakan: 'Menambahkan statistik desa',
            subjek: $statistik->label,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.statistik.index')
            ->with('status', 'Statistik desa berhasil ditambahkan.');
    }

    public function edit(StatistikDesa $statistik): View
    {
        abort_unless(request()->user()->role->bolehKelolaKonten(), 403);

        return view('admin.statistik.form', [
            'activeAdmin' => 'admin.statistik.index',
            'statistik' => $statistik,
        ]);
    }

    public function update(Request $request, StatistikDesa $statistik): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaKonten(), 403);

        $data = $this->validasi($request);

        $statistik->update([
            'label' => $data['label'],
            'nilai' => $data['nilai'],
            'satuan' => $data['satuan'] ?? null,
            'urutan' => $data['urutan'] ?? 0,
            'is_aktif' => $request->boolean('is_aktif'),
        ]);

        LogAktivitas::catat(
            tindakan: 'Memperbarui statistik desa',
            subjek: $statistik->label,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.statistik.index')
            ->with('status', 'Statistik desa berhasil diperbarui.');
    }

    public function destroy(Request $request, StatistikDesa $statistik): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaKonten(), 403);

        $label = $statistik->label;
        $statistik->delete();

        LogAktivitas::catat(
            tindakan: 'Menghapus statistik desa',
            subjek: $label,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.statistik.index')
            ->with('status', 'Statistik desa berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validasi(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'nilai' => ['required', 'string', 'max:30'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_aktif' => ['nullable', 'boolean'],
        ], [
            'label.required' => 'Label statistik wajib diisi.',
            'nilai.required' => 'Nilai statistik wajib diisi.',
            'urutan.integer' => 'Urutan harus berupa angka.',
        ]);
    }
}
