<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KontenWebsite;
use App\Models\LogAktivitas;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KontenController extends Controller
{
    public function index(): View
    {
        abort_unless(request()->user()->role->bolehKelolaKonten(), 403);

        return view('admin.konten.index', [
            'activeAdmin' => 'admin.konten.index',
            'kontens' => KontenWebsite::query()->with('diperbaruiOleh')->orderBy('bagian')->get(),
        ]);
    }

    public function edit(KontenWebsite $konten): View
    {
        abort_unless(request()->user()->role->bolehKelolaKonten(), 403);

        return view('admin.konten.edit', [
            'activeAdmin' => 'admin.konten.index',
            'konten' => $konten,
        ]);
    }

    public function update(Request $request, KontenWebsite $konten): RedirectResponse
    {
        abort_unless($request->user()->role->bolehKelolaKonten(), 403);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'isi' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', Rule::in([KontenWebsite::DRAF, KontenWebsite::TERBIT])],
        ]);

        $konten->update([
            'judul' => $data['judul'],
            'isi' => $data['isi'] ?? null,
            'status' => $data['status'],
            'diperbarui_oleh' => $request->user()->id,
        ]);

        LogAktivitas::catat(
            tindakan: $data['status'] === KontenWebsite::TERBIT
                ? 'Menerbitkan konten website'
                : 'Menyimpan konten sebagai draf',
            subjek: $konten->judul,
            tingkat: LogAktivitas::SUKSES,
        );

        return redirect()
            ->route('admin.konten.edit', $konten)
            ->with('status', 'Konten tersimpan.');
    }
}
