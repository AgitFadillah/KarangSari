<?php

namespace App\Providers;

use App\Models\JenisSurat;
use App\Support\SiteProfile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Profil desa dipakai hampir semua view (judul halaman, brand, footer).
        // Diberikan lewat composer agar @section('title', ...) di baris atas view
        // tidak mendahului @php di layout, dan tidak query saat boot.
        View::composer('*', function ($view) {
            if (! array_key_exists('profil', $view->getData()) || $view->getData()['profil'] === null) {
                $view->with('profil', SiteProfile::current());
            }
        });

        // Partial topbar memakai $activeNav; default null agar view yang tidak
        // menyetelnya tetap aman dirender.
        View::composer('layouts.partials.topbar', function ($view) {
            $view->with('activeNav', $view->getData()['activeNav'] ?? null);
        });

        // Route model binding publik /pelayanan/{jenisSurat} memakai slug dan
        // hanya membuka jenis surat yang aktif.
        Route::bind('jenisSurat', function (string $value) {
            return JenisSurat::query()->aktif()->where('slug', $value)->firstOrFail();
        });
    }
}
