<?php

use App\Http\Controllers\Admin\AkunController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\JenisSuratController;
use App\Http\Controllers\Admin\KontenController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\PengajuanController as AdminPengajuanController;
use App\Http\Controllers\Admin\ProfilController;
use App\Http\Controllers\Admin\RingkasanController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PengajuanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman publik
|--------------------------------------------------------------------------
*/

Route::get('/', [PageController::class, 'beranda'])->name('beranda');
Route::get('/profil', [PageController::class, 'profil'])->name('profil');
Route::get('/informasi', [PageController::class, 'informasi'])->name('informasi');
Route::get('/potensi', [PageController::class, 'potensi'])->name('potensi');
Route::get('/kontak', [PageController::class, 'kontak'])->name('kontak');

Route::get('/pelayanan', [PageController::class, 'pelayanan'])->name('pelayanan');
Route::get('/pelayanan/{jenisSurat}', [PageController::class, 'detailLayanan'])->name('layanan.detail');

Route::get('/pengajuan', [PengajuanController::class, 'create'])->name('pengajuan.create');
Route::post('/pengajuan/{jenisSurat}', [PengajuanController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('pengajuan.store');
Route::get('/pengajuan/berhasil/{pengajuan}', [PengajuanController::class, 'berhasil'])
    ->whereNumber('pengajuan')
    ->name('pengajuan.berhasil');

Route::get('/kebijakan-privasi', [PageController::class, 'kebijakanPrivasi'])->name('privasi.kebijakan');
Route::get('/pemberitahuan-data', [PageController::class, 'pemberitahuanData'])->name('privasi.pemberitahuan');

/*
|--------------------------------------------------------------------------
| Autentikasi admin
|--------------------------------------------------------------------------
*/

Route::get('/admin/login', [AuthController::class, 'showLogin'])
    ->middleware('guest')
    ->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])
    ->middleware(['guest', 'throttle:6,1'])
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
    Route::get('/pengajuan/{pengajuan}/dokumen/{dokumen}', [AdminPengajuanController::class, 'unduhDokumen'])
        ->whereNumber(['pengajuan', 'dokumen'])
        ->name('admin.pengajuan.dokumen');

    Route::get('/jenis-surat', [JenisSuratController::class, 'index'])->name('admin.jenis-surat.index');
    Route::get('/jenis-surat/{jenisSurat}/edit', [JenisSuratController::class, 'edit'])->name('admin.jenis-surat.edit');
    Route::put('/jenis-surat/{jenisSurat}', [JenisSuratController::class, 'update'])->name('admin.jenis-surat.update');

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
});
