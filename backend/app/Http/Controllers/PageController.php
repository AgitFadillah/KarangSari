<?php

namespace App\Http\Controllers;

use App\Models\JenisSurat;
use App\Models\KontenWebsite;
use Illuminate\Contracts\View\View;

/**
 * Halaman publik statis. Profil desa diambil otomatis oleh layout
 * lewat App\Support\SiteProfile.
 */
class PageController extends Controller
{
    public function beranda(): View
    {
        return $this->halaman('pages.beranda');
    }

    public function profil(): View
    {
        return $this->halaman('pages.profil');
    }

    public function informasi(): View
    {
        return $this->halaman('pages.informasi');
    }

    public function potensi(): View
    {
        return $this->halaman('pages.potensi');
    }

    public function kontak(): View
    {
        return $this->halaman('pages.kontak');
    }

    public function kebijakanPrivasi(): View
    {
        return $this->halaman('pages.privasi.kebijakan');
    }

    public function pemberitahuanData(): View
    {
        return $this->halaman('pages.privasi.pemberitahuan');
    }

    /** Katalog jenis surat yang diambil dari konfigurasi layanan desa. */
    public function pelayanan(): View
    {
        $jenisSurats = JenisSurat::query()
            ->aktif()
            ->terurut()
            ->withCount('persyaratan')
            ->get();

        return $this->halaman('pages.pelayanan.index', [
            'jenisSurats' => $jenisSurats,
        ]);
    }

    /** Detail satu layanan; $jenisSurat sudah di-resolve route binding (aktif saja). */
    public function detailLayanan(JenisSurat $jenisSurat): View
    {
        $jenisSurat->load('persyaratan');

        return $this->halaman('pages.pelayanan.show', [
            'jenisSurat' => $jenisSurat,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function halaman(string $view, array $data = []): View
    {
        $nav = match ($view) {
            'pages.beranda' => 'beranda',
            'pages.profil' => 'profil',
            'pages.informasi' => 'informasi',
            'pages.potensi' => 'potensi',
            'pages.kontak' => 'kontak',
            'pages.pelayanan.index', 'pages.pelayanan.show' => 'pelayanan',
            default => null,
        };

        return view($view, [
            'activeNav' => $nav,
            'kontens' => KontenWebsite::query()->terbit()->latest('updated_at')->get(),
        ] + $data);
    }
}
