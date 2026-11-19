<?php

use App\Http\Controllers\Admin\AkunController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BeritaController;
use App\Http\Controllers\Admin\JenisSuratController;
use App\Http\Controllers\Admin\KontenController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\PengajuanController as AdminPengajuanController;
use App\Http\Controllers\Admin\PerangkatDesaController;
use App\Http\Controllers\Admin\PotensiDesaController;
use App\Http\Controllers\Admin\ProfilController;
use App\Http\Controllers\Admin\RingkasanController;
use App\Http\Controllers\Admin\StatistikDesaController;
use App\Http\Controllers\CekStatusController;
use App\Http\Controllers\GambarSosialController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman publik
|--------------------------------------------------------------------------
*/

Route::get('/', [PageController::class, 'beranda'])->name('beranda');
Route::get('/profil', [PageController::class, 'profil'])->name('profil');
Route::get('/informasi', [PageController::class, 'informasi'])->name('informasi');
Route::get('/informasi/{berita:slug}', [PageController::class, 'detailInformasi'])
    ->name('informasi.detail');
Route::get('/potensi', [PageController::class, 'potensi'])->name('potensi');
Route::get('/kontak', [PageController::class, 'kontak'])->name('kontak');

Route::get('/pelayanan', [PageController::class, 'pelayanan'])->name('pelayanan');
Route::get('/pelayanan/{jenisSurat}', [PageController::class, 'detailLayanan'])->name('layanan.detail');

Route::get('/pengajuan', [PengajuanController::class, 'create'])->name('pengajuan.create');
Route::post('/pengajuan/{jenisSurat}', [PengajuanController::class, 'store'])
    ->middleware('throttle:pengajuan.store')
    ->name('pengajuan.store');
Route::get('/pengajuan/berhasil/{pengajuan}', [PengajuanController::class, 'berhasil'])
    ->whereNumber('pengajuan')
    ->name('pengajuan.berhasil');

// Unduh surat jadi (.docx) oleh warga, memakai nomor referensi sebagai token
// akses. Hanya tersedia setelah surat selesai diproses.
Route::get('/pengajuan/surat/{nomorReferensi}', [PengajuanController::class, 'unduhSurat'])
    ->middleware('throttle:pengajuan.surat')
    ->name('pengajuan.surat');

// Pratinjau surat resmi desa untuk formulir warga: mengembalikan teks surat
// yang sudah terisi isian, sehingga pratinjau di layar sama dengan berkas
// Word yang nanti diunduh.
//
// Pratinjau dipanggil otomatis setiap warga mengetik, jadi ia memakai kuota
// sendiri yang longgar. Tanpa kunci terpisah, panggilan ini akan menghabiskan
// kuota halaman revisi dan membuat warga melihat "429 Too Many Requests".
Route::post('/pengajuan/pratinjau/{jenisSurat}', [PengajuanController::class, 'pratinjauResmi'])
    ->middleware('throttle:pengajuan.pratinjau')
    ->name('pengajuan.pratinjau');

// Revisi pengajuan setelah admin meminta perbaikan. Nomor referensi menjadi
// token akses; halaman hanya terbuka bila statusnya "Perlu Perbaikan".
//
// Membuka halaman dan mengirim perbaikan memakai kuota BERBEDA: warga sering
// membuka halaman revisi berkali-kali (refresh, kembali dari panduan) sebelum
// akhirnya mengirim, dan itu tidak boleh memblokir pengiriman perbaikannya.
Route::get('/pengajuan/revisi/{nomorReferensi}', [PengajuanController::class, 'revisi'])
    ->middleware('throttle:pengajuan.revisi')
    ->name('pengajuan.revisi');
Route::post('/pengajuan/revisi/{nomorReferensi}', [PengajuanController::class, 'simpanRevisi'])
    ->middleware('throttle:pengajuan.revisi.simpan')
    ->name('pengajuan.revisi.simpan');

// Cek status: warga memasukkan nomor referensi. Throttle mencegah orang
// menebak-nebak nomor referensi milik warga lain.
Route::get('/cek-status', [CekStatusController::class, 'form'])->name('cek-status.form');
Route::post('/cek-status', [CekStatusController::class, 'cari'])
    ->middleware('throttle:cek-status.cari')
    ->name('cek-status.cari');

// Status langsung untuk perangkat yang sudah terbukti berhak (baru mengirim
// pengajuan atau sudah memasukkan kode). Tanpa ini, warga yang baru selesai
// mengirim tetap harus menempel kode lagi hanya untuk melihat statusnya.
Route::get('/cek-status/status-saya/{nomorReferensi}', [CekStatusController::class, 'status'])
    ->middleware('throttle:cek-status.cari')
    ->name('cek-status.saya');

// Pemulihan kode akses: warga yang lupa/kehilangan kode tidak perlu membuat
// pengajuan baru atau menelepon kantor desa. Cukup buktikan dengan tanggal
// lahir yang tercatat pada pengajuan.
Route::get('/cek-status/lupa-kode', [CekStatusController::class, 'lupaForm'])
    ->name('cek-status.lupa.form');
Route::post('/cek-status/lupa-kode', [CekStatusController::class, 'lupa'])
    ->middleware('throttle:cek-status.lupa')
    ->name('cek-status.lupa');

Route::get('/kebijakan-privasi', [PageController::class, 'kebijakanPrivasi'])->name('privasi.kebijakan');
Route::get('/pemberitahuan-data', [PageController::class, 'pemberitahuanData'])->name('privasi.pemberitahuan');

// Panduan warga: alur pengajuan, cek status, dan revisi.
Route::get('/panduan', [PageController::class, 'panduan'])->name('panduan');

// Gambar pratinjau tautan (Open Graph) yang dipakai WhatsApp/media sosial.
// Dibuat dinamis dari logo desa supaya ikut berubah saat admin mengganti logo.
Route::get('/gambar-sosial.png', [GambarSosialController::class, 'logo'])->name('gambar-sosial');

// Berkas bantu mesin pencari. Hanya halaman publik yang didaftarkan.
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

/*
|--------------------------------------------------------------------------
| Autentikasi admin
|--------------------------------------------------------------------------
*/

Route::get('/admin/login', [AuthController::class, 'showLogin'])
    ->middleware('guest')
    ->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])
    ->middleware(['guest', 'throttle:admin.login.store'])
    ->name('admin.login.store');
Route::post('/admin/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('admin.logout');

/*
|--------------------------------------------------------------------------
| Dashboard admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [RingkasanController::class, 'index'])->name('admin.ringkasan');

    Route::get('/pengajuan', [AdminPengajuanController::class, 'index'])->name('admin.pengajuan.index');
    Route::get('/pengajuan/{pengajuan}', [AdminPengajuanController::class, 'show'])
        ->whereNumber('pengajuan')
        ->name('admin.pengajuan.show');
    Route::patch('/pengajuan/{pengajuan}/status', [AdminPengajuanController::class, 'updateStatus'])
        ->whereNumber('pengajuan')
        ->name('admin.pengajuan.status');
    Route::put('/pengajuan/{pengajuan}/nomor-surat', [AdminPengajuanController::class, 'simpanNomorSurat'])
        ->whereNumber('pengajuan')
        ->name('admin.pengajuan.nomor-surat');
    Route::get('/pengajuan/{pengajuan}/dokumen/{dokumen}', [AdminPengajuanController::class, 'unduhDokumen'])
        ->whereNumber(['pengajuan', 'dokumen'])
        ->name('admin.pengajuan.dokumen');
    Route::patch('/pengajuan/{pengajuan}/dokumen/{dokumen}', [AdminPengajuanController::class, 'verifikasiDokumen'])
        ->whereNumber(['pengajuan', 'dokumen'])
        ->name('admin.pengajuan.dokumen.status');

    // Surat jadi dalam format Word asli desa (.docx), untuk diunduh petugas.
    Route::get('/pengajuan/{pengajuan}/surat-word', [AdminPengajuanController::class, 'unduhSuratWord'])
        ->whereNumber('pengajuan')
        ->name('admin.pengajuan.surat-word');

    Route::get('/jenis-surat', [JenisSuratController::class, 'index'])->name('admin.jenis-surat.index');
    Route::get('/jenis-surat/create', [JenisSuratController::class, 'create'])->name('admin.jenis-surat.create');
    Route::post('/jenis-surat', [JenisSuratController::class, 'store'])->name('admin.jenis-surat.store');
    Route::get('/jenis-surat/{jenisSurat}/edit', [JenisSuratController::class, 'edit'])->name('admin.jenis-surat.edit');
    Route::put('/jenis-surat/{jenisSurat}', [JenisSuratController::class, 'update'])->name('admin.jenis-surat.update');
    Route::patch('/jenis-surat/{jenisSurat}/aktif', [JenisSuratController::class, 'toggleAktif'])->name('admin.jenis-surat.toggle');
    Route::delete('/jenis-surat/{jenisSurat}', [JenisSuratController::class, 'destroy'])->name('admin.jenis-surat.destroy');

    // Konten dinamis: berita/agenda, potensi, statistik beranda, perangkat desa.
    Route::get('/berita', [BeritaController::class, 'index'])->name('admin.berita.index');
    Route::get('/berita/create', [BeritaController::class, 'create'])->name('admin.berita.create');
    Route::post('/berita', [BeritaController::class, 'store'])->name('admin.berita.store');
    Route::get('/berita/{berita}/edit', [BeritaController::class, 'edit'])
        ->whereNumber('berita')
        ->name('admin.berita.edit');
    Route::put('/berita/{berita}', [BeritaController::class, 'update'])
        ->whereNumber('berita')
        ->name('admin.berita.update');
    Route::delete('/berita/{berita}', [BeritaController::class, 'destroy'])
        ->whereNumber('berita')
        ->name('admin.berita.destroy');

    Route::get('/potensi', [PotensiDesaController::class, 'index'])->name('admin.potensi.index');
    Route::get('/potensi/create', [PotensiDesaController::class, 'create'])->name('admin.potensi.create');
    Route::post('/potensi', [PotensiDesaController::class, 'store'])->name('admin.potensi.store');
    Route::get('/potensi/{potensi}/edit', [PotensiDesaController::class, 'edit'])
        ->whereNumber('potensi')
        ->name('admin.potensi.edit');
    Route::put('/potensi/{potensi}', [PotensiDesaController::class, 'update'])
        ->whereNumber('potensi')
        ->name('admin.potensi.update');
    Route::delete('/potensi/{potensi}', [PotensiDesaController::class, 'destroy'])
        ->whereNumber('potensi')
        ->name('admin.potensi.destroy');

    Route::get('/statistik', [StatistikDesaController::class, 'index'])->name('admin.statistik.index');
    Route::get('/statistik/create', [StatistikDesaController::class, 'create'])->name('admin.statistik.create');
    Route::post('/statistik', [StatistikDesaController::class, 'store'])->name('admin.statistik.store');
    Route::get('/statistik/{statistik}/edit', [StatistikDesaController::class, 'edit'])
        ->whereNumber('statistik')
        ->name('admin.statistik.edit');
    Route::put('/statistik/{statistik}', [StatistikDesaController::class, 'update'])
        ->whereNumber('statistik')
        ->name('admin.statistik.update');
    Route::delete('/statistik/{statistik}', [StatistikDesaController::class, 'destroy'])
        ->whereNumber('statistik')
        ->name('admin.statistik.destroy');

    Route::get('/perangkat', [PerangkatDesaController::class, 'index'])->name('admin.perangkat.index');
    Route::get('/perangkat/create', [PerangkatDesaController::class, 'create'])->name('admin.perangkat.create');
    Route::post('/perangkat', [PerangkatDesaController::class, 'store'])->name('admin.perangkat.store');
    Route::get('/perangkat/{perangkat}/edit', [PerangkatDesaController::class, 'edit'])
        ->whereNumber('perangkat')
        ->name('admin.perangkat.edit');
    Route::put('/perangkat/{perangkat}', [PerangkatDesaController::class, 'update'])
        ->whereNumber('perangkat')
        ->name('admin.perangkat.update');
    Route::delete('/perangkat/{perangkat}', [PerangkatDesaController::class, 'destroy'])
        ->whereNumber('perangkat')
        ->name('admin.perangkat.destroy');

    Route::get('/konten', [KontenController::class, 'index'])->name('admin.konten.index');
    Route::get('/konten/{konten}/edit', [KontenController::class, 'edit'])->name('admin.konten.edit');
    Route::put('/konten/{konten}', [KontenController::class, 'update'])->name('admin.konten.update');

    Route::get('/profil', [ProfilController::class, 'edit'])->name('admin.profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('admin.profil.update');

    Route::get('/akun', [AkunController::class, 'index'])->name('admin.akun.index');
    Route::post('/akun', [AkunController::class, 'store'])->name('admin.akun.store');
    Route::put('/akun/{user}', [AkunController::class, 'update'])->name('admin.akun.update');
    Route::put('/akun/{user}/sandi', [AkunController::class, 'resetPassword'])->name('admin.akun.sandi');

    Route::get('/log', [LogController::class, 'index'])->name('admin.log');

    // Panduan admin: alur verifikasi, dokumen, status, dan pengelolaan konten.
    Route::get('/panduan', fn () => view('admin.panduan', ['activeAdmin' => 'admin.panduan']))
        ->name('admin.panduan');
});
