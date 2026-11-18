<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\DataDesa;
use App\Models\JenisSurat;
use App\Models\KontenWebsite;
use App\Models\PerangkatDesa;
use App\Models\PotensiDesa;
use App\Models\StatistikDesa;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Halaman publik. Profil desa diambil otomatis oleh layout lewat
 * App\Support\SiteProfile.
 *
 * Semua konten yang dapat diubah perangkat desa (statistik, berita, potensi,
 * perangkat) dibaca dari database, bukan ditulis di dalam Blade.
 */
class PageController extends Controller
{
    /** Jumlah berita yang tampil pada blok informasi beranda. */
    private const BERITA_BERANDA = 4;

    /** Jumlah potensi yang tampil pada beranda. */
    private const POTENSI_BERANDA = 3;

    /** Jumlah agenda terdekat pada beranda. */
    private const AGENDA_BERANDA = 3;

    /** Jumlah pengumuman ringkas pada beranda. */
    private const PENGUMUMAN_BERANDA = 2;

    /** Jumlah potensi per seksi pada halaman Potensi. */
    private const POTENSI_HALAMAN = 12;

    public function beranda(): View
    {
        return $this->halaman('pages.beranda', [
            'statistiks' => StatistikDesa::query()->aktif()->terurut()->get(),
            'beritaUtama' => Berita::query()->terbit()->where('kategori', 'berita')
                ->latest('terbit_at')->first(),
            'beritaSamping' => Berita::query()->terbit()->where('kategori', 'berita')
                ->latest('terbit_at')->skip(1)->take(self::BERITA_BERANDA - 1)->get(),
            'pengumumans' => Berita::query()->terbit()->where('kategori', 'pengumuman')
                ->latest('terbit_at')->take(self::PENGUMUMAN_BERANDA)->get(),
            'agendas' => Berita::query()->terbit()->where('kategori', 'agenda')
                ->latest('terbit_at')->take(self::AGENDA_BERANDA)->get(),
            'potensis' => PotensiDesa::query()->aktif()->terurut()->take(self::POTENSI_BERANDA)->get(),
            'layananRingkas' => JenisSurat::query()->aktif()->terurut()->withCount('persyaratan')->take(4)->get(),
            'kepalaDesa' => $this->kepalaDesa(),
        ]);
    }

    public function profil(): View
    {
        return $this->halaman('pages.profil', [
            'perangkats' => PerangkatDesa::query()->aktif()->terurut()->get(),
            'kepalaDesa' => $this->kepalaDesa(),
            'statistiks' => StatistikDesa::query()->aktif()->terurut()->get(),
            // Data tabel dari dokumen PROFIL desa.docx, dikelompokkan agar
            // view cukup mengambil kelompok yang dibutuhkannya.
            'dataDesa' => DataDesa::query()->terurut()->get()->groupBy('kelompok'),
        ]);
    }

    /**
     * Perangkat desa yang menjabat Kepala Desa.
     *
     * Pencocokan jabatan memakai LOWER(...) LIKE agar tidak bergantung pada
     * dialek database: `LIKE` di PostgreSQL peka huruf besar/kecil, sehingga
     * jabatan yang ditulis "kepala desa" (huruf kecil) akan membuat blok
     * sambutan Kepala Desa hilang dari beranda dan profil setelah situs
     * dipindahkan ke PostgreSQL — padahal di SQLite tampak normal.
     */
    protected function kepalaDesa(): ?PerangkatDesa
    {
        return PerangkatDesa::query()
            ->aktif()
            ->terurut()
            ->where(function ($q) {
                \App\Support\Pencarian::cocok($q, 'jabatan', 'kepala desa');
            })
            ->first();
    }

    /** Kategori yang boleh dipakai sebagai filter halaman Informasi. */
    private const KATEGORI_INFORMASI = ['semua', 'berita', 'agenda', 'pengumuman'];

    /**
     * Halaman Informasi dengan penyaring kategori.
     *
     * Pil "Semua / Berita / Agenda / Pengumuman" sebelumnya hanya mengandalkan
     * JavaScript: judul seksi tetap tampil kosong dan banner sorotan tidak ikut
     * tersaring, sehingga terlihat seperti tombol yang tidak bekerja. Kini
     * penyaringan dilakukan di server lewat ?kategori=… agar hasilnya utuh
     * (bagian kosong tidak dirender) dan tetap bekerja tanpa JavaScript.
     */
    public function informasi(Request $request): View
    {
        $kategori = strtolower(trim((string) $request->query('kategori', 'semua')));

        if (! in_array($kategori, self::KATEGORI_INFORMASI, true)) {
            $kategori = 'semua';
        }

        $perlu = fn (string $nama): bool => $kategori === 'semua' || $kategori === $nama;

        // Jumlah tiap kategori dihitung terpisah agar angka pada pil tetap
        // benar meski isi kategori lain tidak dimuat.
        $jumlah = Berita::query()->terbit()
            ->selectRaw('kategori, count(*) as jumlah')
            ->groupBy('kategori')
            ->pluck('jumlah', 'kategori');

        $jumlahKategori = [
            'berita' => (int) ($jumlah['berita'] ?? 0),
            'agenda' => (int) ($jumlah['agenda'] ?? 0),
            'pengumuman' => (int) ($jumlah['pengumuman'] ?? 0),
        ];
        $jumlahKategori['semua'] = array_sum($jumlahKategori);

        // Berita sorotan tampil sebagai banner besar paling atas, tetapi
        // hanya bila kategorinya sesuai dengan filter yang sedang aktif.
        $sorotan = Berita::query()->terbit()->sorotan()
            ->when($kategori !== 'semua', fn ($q) => $q->where('kategori', $kategori))
            ->latest('terbit_at')->first();

        return $this->halaman('pages.informasi', [
            'aktifKategori' => $kategori,
            'jumlahKategori' => $jumlahKategori,
            'sorotan' => $sorotan,
            // Berita yang sudah tampil sebagai banner sorotan dikeluarkan dari
            // daftar, supaya judul yang sama tidak muncul dua kali di halaman.
            'beritas' => $perlu('berita')
                ? Berita::query()->terbit()->where('kategori', 'berita')
                    ->when($sorotan, fn ($q) => $q->whereKeyNot($sorotan->getKey()))
                    ->latest('terbit_at')->take(12)->get()
                : collect(),
            'pengumumans' => $perlu('pengumuman')
                ? Berita::query()->terbit()->where('kategori', 'pengumuman')
                    ->latest('terbit_at')->take(12)->get()
                : collect(),
            'agendas' => $perlu('agenda')
                ? Berita::query()->terbit()->where('kategori', 'agenda')
                    ->latest('terbit_at')->take(12)->get()
                : collect(),
        ]);
    }

    public function potensi(): View
    {
        return $this->halaman('pages.potensi', [
            'potensis' => PotensiDesa::query()->aktif()->terurut()->take(self::POTENSI_HALAMAN)->get(),
            'statistiks' => StatistikDesa::query()->aktif()->terurut()->get(),
        ]);
    }

    public function kontak(): View
    {
        return $this->halaman('pages.kontak', [
            // Jadwal per hari diurai dari kolom "Jam pelayanan" di menu
            // Profil & Kontak, supaya kartu jam pelayanan tidak lagi ditulis
            // mati (dulu selalu "Sabtu–Minggu: Tutup").
            'jadwalLayanan' => \App\Support\SiteProfile::current()->jadwalTampil(),
        ]);
    }

    public function kebijakanPrivasi(): View
    {
        return $this->halaman('pages.privasi.kebijakan');
    }

    public function pemberitahuanData(): View
    {
        return $this->halaman('pages.privasi.pemberitahuan');
    }

    /** Panduan warga: alur pengajuan sampai pengambilan surat. */
    public function panduan(): View
    {
        return $this->halaman('pages.panduan');
    }

    /**
     * Detail satu berita/pengumuman/agenda.
     *
     * Route binding memakai kolom slug. Draf (belum terbit) tidak boleh
     * terbaca lewat URL langsung, jadi query dibatasi scope terbit().
     */
    public function detailInformasi(Berita $berita): View
    {
        abort_unless($berita->isTerbit(), 404);

        // Tautan "lainnya" pada akhir artikel, dibatasi kategori yang sama.
        $terkait = Berita::query()
            ->terbit()
            ->where('kategori', $berita->kategori)
            ->whereKeyNot($berita->getKey())
            ->latest('terbit_at')
            ->take(3)
            ->get();

        return $this->halaman('pages.informasi-detail', [
            'berita' => $berita,
            'terkait' => $terkait,
        ]);
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
            'pages.informasi-detail' => 'informasi',
            'pages.potensi' => 'potensi',
            'pages.kontak' => 'kontak',
            'pages.pelayanan.index', 'pages.pelayanan.show' => 'pelayanan',
            // Panduan punya menu sendiri; tanpa baris ini item Panduan tidak
            // pernah ditandai aktif.
            'pages.panduan' => 'panduan',
            default => null,
        };

        return view($view, [
            'activeNav' => $nav,
            'kontens' => KontenWebsite::query()->terbit()->latest('updated_at')->get(),
        ] + $data);
    }
}
